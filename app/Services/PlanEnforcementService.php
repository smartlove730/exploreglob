<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Arr;
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
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED])
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
    }

    public function consumeQuota(User $user, int $units = 1): void
    {
        if ($user->isAdmin()) {
            return;
        }

        DB::transaction(function () use ($user, $units) {
            $subscription = Subscription::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED])
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
}
