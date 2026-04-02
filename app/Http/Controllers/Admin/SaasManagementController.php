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
use Illuminate\Validation\Rule;

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

    public function storePlan(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);
        $data['slug'] = Str::slug((string) $data['slug']);

        $plan = Plan::create($data);
        app(ActivityLogService::class)->log('admin.plan.created', $request->user(), ['plan_id' => $plan->id]);

        return back()->with('success', "Plan {$plan->name} created.");
    }

    public function updatePlan(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request, $plan);
        $data['slug'] = Str::slug((string) $data['slug']);

        if ((float) $data['price'] !== (float) $plan->price || $data['interval'] !== $plan->interval || strtoupper((string) $data['currency']) !== strtoupper((string) $plan->currency)) {
            $data['razorpay_plan_id'] = null;
        }

        $plan->update($data);
        app(ActivityLogService::class)->log('admin.plan.updated', $request->user(), ['plan_id' => $plan->id]);

        return back()->with('success', "Plan {$plan->name} updated.");
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

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'interval' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'post_limit' => ['required', 'integer', 'min:1'],
            'posts_per_day_limit' => ['nullable', 'integer', 'min:1'],
            'posts_per_week_limit' => ['nullable', 'integer', 'min:1'],
            'posts_per_month_limit' => ['nullable', 'integer', 'min:1'],
            'automation_limit' => ['nullable', 'integer', 'min:1'],
            'connected_apps_limit' => ['nullable', 'integer', 'min:1'],
            'synced_pages_limit' => ['nullable', 'integer', 'min:1'],
            'facebook_enabled' => ['nullable', 'boolean'],
            'instagram_enabled' => ['nullable', 'boolean'],
            'google_business_enabled' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'currency' => strtoupper((string) $request->input('currency')),
            'facebook_enabled' => $request->boolean('facebook_enabled'),
            'instagram_enabled' => $request->boolean('instagram_enabled'),
            'google_business_enabled' => $request->boolean('google_business_enabled'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
