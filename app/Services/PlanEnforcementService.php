<?php

namespace App\Services;

use App\Models\AutomationConfig;
use App\Models\FacebookAccount;
use App\Models\FacebookPage;
use App\Models\ScheduledPost;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanEnforcementService
{
    public function getActiveSubscription(User $user): ?Subscription
    {
        if ($user->isAdmin()) {
            return null;
        }

        return Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>=', now());
            })
            ->latest('id')
            ->first();
    }

    public function assertCanPost(User $user, array $platforms, int $units = 1): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $subscription = $this->getActiveSubscription($user);

        if (!$subscription || !$subscription->plan) {
            throw ValidationException::withMessages([
                'subscription' => 'An active subscription is required to publish posts.',
            ]);
        }

        $plan = $subscription->plan;

        $normalized = collect($platforms)
            ->map(fn ($platform) => trim((string) $platform))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($normalized as $platform) {
            $enabled = match ($platform) {
                'facebook' => (bool) $plan->facebook_enabled,
                'instagram' => (bool) $plan->instagram_enabled,
                'google_business' => (bool) $plan->google_business_enabled,
                default => true,
            };

            if (!$enabled) {
                throw ValidationException::withMessages([
                    'platforms' => ucfirst(str_replace('_', ' ', $platform)).' posting is not enabled for your current plan.',
                ]);
            }
        }

        $remaining = max(0, (int) $plan->post_limit - (int) $subscription->posts_used);

        if ($remaining < max(1, $units)) {
            throw ValidationException::withMessages([
                'post_limit' => 'Post limit exceeded for your current subscription period.',
            ]);
        }

        $this->assertPostWindowLimit($user, 'posts_per_day_limit', now()->startOfDay(), 'daily');
        $this->assertPostWindowLimit($user, 'posts_per_week_limit', now()->startOfWeek(), 'weekly');
        $this->assertPostWindowLimit($user, 'posts_per_month_limit', now()->startOfMonth(), 'monthly');
    }

    public function assertCanCreateAutomation(User $user, int $newAutomations = 1): void
    {
        $this->assertSimpleCountLimit(
            user: $user,
            count: AutomationConfig::query()->where('user_id', $user->id)->count(),
            limitKey: 'automation_limit',
            units: $newAutomations,
            message: 'Automation config limit reached for your current plan.'
        );
    }

    public function assertCanConnectApps(User $user, int $newConnections = 1): void
    {
        $this->assertSimpleCountLimit(
            user: $user,
            count: FacebookAccount::query()->where('user_id', $user->id)->count(),
            limitKey: 'connected_apps_limit',
            units: $newConnections,
            message: 'Connected app limit reached for your current plan.'
        );
    }

    public function assertCanSyncPages(User $user, int $incomingPages): void
    {
        $this->assertSimpleCountLimit(
            user: $user,
            count: FacebookPage::query()->where('user_id', $user->id)->count(),
            limitKey: 'synced_pages_limit',
            units: max(0, $incomingPages),
            message: 'Synced page limit reached for your current plan.'
        );
    }

    public function consumeQuota(User $user, int $units = 1): void
    {
        if ($user->isAdmin()) {
            return;
        }

        DB::transaction(function () use ($user, $units) {
            $subscription = Subscription::query()
                ->where('user_id', $user->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->where(function ($query) {
                    $query->whereNull('current_period_end')
                        ->orWhere('current_period_end', '>=', now());
                })
                ->with('plan')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$subscription || !$subscription->plan) {
                throw ValidationException::withMessages([
                    'subscription' => 'No active subscription found for quota consumption.',
                ]);
            }

            $nextUsage = (int) $subscription->posts_used + max(1, $units);
            if ($nextUsage > (int) $subscription->plan->post_limit) {
                throw ValidationException::withMessages([
                    'post_limit' => 'Post limit exceeded for your current subscription period.',
                ]);
            }

            $subscription->update(['posts_used' => $nextUsage]);
        });
    }

    private function assertPostWindowLimit(User $user, string $limitKey, \DateTimeInterface $since, string $label): void
    {
        $limit = $this->resolvePlanLimit($user, $limitKey);
        if ($limit <= 0) {
            return;
        }

        $count = ScheduledPost::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->count();

        if ($count >= $limit) {
            throw ValidationException::withMessages([
                'post_limit' => "Your {$label} post limit ({$limit}) has been reached.",
            ]);
        }
    }

    private function assertSimpleCountLimit(User $user, int $count, string $limitKey, int $units, string $message): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $limit = $this->resolvePlanLimit($user, $limitKey);
        if ($limit <= 0) {
            return;
        }

        if (($count + max(0, $units)) > $limit) {
            throw ValidationException::withMessages([
                $limitKey => $message,
            ]);
        }
    }

    private function resolvePlanLimit(User $user, string $limitKey): int
    {
        $subscription = $this->getActiveSubscription($user);
        $plan = $subscription?->plan;

        return $plan ? $plan->configuredLimit($limitKey) : 0;
    }
}
