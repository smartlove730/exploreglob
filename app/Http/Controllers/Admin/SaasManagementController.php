<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\ScheduledPost;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaasManagementController extends Controller
{
    public function overview()
    {
        $stats = [
            'customers' => User::query()
                ->where('is_admin', false)
                ->where(function ($query) {
                    $query->where('role', User::ROLE_CUSTOMER)
                        ->orWhereNull('role');
                })
                ->count(),
            'admins' => User::query()->where('role', User::ROLE_ADMIN)->orWhere('is_admin', true)->count(),
            'plans' => Plan::query()->count(),
            'active_plans' => Plan::query()->where('is_active', true)->count(),
            'subscriptions_active' => Subscription::query()->where('status', Subscription::STATUS_ACTIVE)->count(),
            'subscriptions_cancelled' => Subscription::query()->where('status', Subscription::STATUS_CANCELLED)->count(),
            'posts_published' => ScheduledPost::query()->where('status', ScheduledPost::STATUS_PUBLISHED)->count(),
            'posts_failed' => ScheduledPost::query()->where('status', ScheduledPost::STATUS_FAILED)->count(),
        ];

        $recentSubscriptions = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.saas.overview', compact('stats', 'recentSubscriptions'));
    }

    public function users()
    {
        $users = User::query()
            ->where('is_admin', false)
            ->where(function ($query) {
                $query->where('role', User::ROLE_CUSTOMER)
                    ->orWhereNull('role');
            })
            ->withCount([
                'subscriptions',
                'googleAccounts',
                'facebookAccounts',
            ])
            ->with(['activeSubscription.plan'])
            ->latest()
            ->paginate(25);

        return view('admin.saas.users', compact('users'));
    }

    public function plans()
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('admin.saas.plans', compact('plans'));
    }

    public function togglePlan(Request $request, Plan $plan): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $plan->update(['is_active' => (bool) $request->boolean('is_active')]);
        app(ActivityLogService::class)->log('admin.plan.toggled', $request->user(), [
            'plan_id' => $plan->id,
            'is_active' => $plan->is_active,
        ]);

        return back()->with('success', "Plan {$plan->name} updated.");
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name,post_limit'])
            ->latest()
            ->paginate(30);

        return view('admin.saas.subscriptions', compact('subscriptions'));
    }

    public function toggleSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $nextStatus = $request->boolean('is_active')
            ? Subscription::STATUS_ACTIVE
            : Subscription::STATUS_CANCELLED;

        $subscription->update([
            'status' => $nextStatus,
        ]);

        app(ActivityLogService::class)->log('admin.subscription.toggled', $request->user(), [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'status' => $subscription->status,
        ]);

        return back()->with('success', "Subscription #{$subscription->id} updated to {$subscription->status}.");
    }

    public function verifyEmail(Request $request, User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('info', "{$user->name}'s email is already verified.");
        }

        $user->markEmailAsVerified();

        app(ActivityLogService::class)->log('admin.user.email_verified', $request->user(), [
            'verified_user_id' => $user->id,
            'verified_email' => $user->email,
        ]);

        return back()->with('success', "Email verified for {$user->name} ({$user->email}).");
    }

    // ── Plan CRUD ────────────────────────────────────────────────────

    public function createPlan()
    {
        return view('admin.saas.plans.create');
    }

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $request->validate($this->planRules());

        $data['slug'] = Str::slug($data['name']);

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (Plan::query()->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter++;
        }

        $data['facebook_enabled'] = $request->boolean('facebook_enabled');
        $data['instagram_enabled'] = $request->boolean('instagram_enabled');
        $data['google_business_enabled'] = $request->boolean('google_business_enabled');
        $data['is_active'] = $request->boolean('is_active');

        $plan = Plan::create($data);

        app(ActivityLogService::class)->log('admin.plan.created', $request->user(), [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);

        return redirect()->route('admin.saas.plans')->with('success', "Plan \"{$plan->name}\" created.");
    }

    public function editPlan(Plan $plan)
    {
        return view('admin.saas.plans.edit', compact('plan'));
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate($this->planRules());

        $data['facebook_enabled'] = $request->boolean('facebook_enabled');
        $data['instagram_enabled'] = $request->boolean('instagram_enabled');
        $data['google_business_enabled'] = $request->boolean('google_business_enabled');
        $data['is_active'] = $request->boolean('is_active');

        // Update slug only if name changed
        if ($plan->name !== $data['name']) {
            $data['slug'] = Str::slug($data['name']);
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Plan::query()->where('slug', $data['slug'])->where('id', '!=', $plan->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }
        }

        $plan->update($data);

        app(ActivityLogService::class)->log('admin.plan.updated', $request->user(), [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);

        return redirect()->route('admin.saas.plans')->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function destroyPlan(Request $request, Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', "Cannot delete plan \"{$plan->name}\" because it has active subscriptions.");
        }

        $planName = $plan->name;
        $planId = $plan->id;
        $plan->delete();

        app(ActivityLogService::class)->log('admin.plan.deleted', $request->user(), [
            'plan_id' => $planId,
            'plan_name' => $planName,
        ]);

        return redirect()->route('admin.saas.plans')->with('success', "Plan \"{$planName}\" deleted.");
    }

    private function planRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:INR,USD'],
            'interval' => ['required', 'string', 'in:monthly,yearly'],
            'post_limit' => ['required', 'integer', 'min:0'],
            'posts_per_day_limit' => ['nullable', 'integer', 'min:0'],
            'posts_per_week_limit' => ['nullable', 'integer', 'min:0'],
            'posts_per_month_limit' => ['nullable', 'integer', 'min:0'],
            'automation_limit' => ['nullable', 'integer', 'min:0'],
            'connected_apps_limit' => ['nullable', 'integer', 'min:0'],
            'synced_pages_limit' => ['nullable', 'integer', 'min:0'],
            'razorpay_plan_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
