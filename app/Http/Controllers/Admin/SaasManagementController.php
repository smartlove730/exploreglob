<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\ScheduledPost;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaasManagementController extends Controller
{
    public function overview()
    {
        $stats = [
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
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
            ->where('role', User::ROLE_CUSTOMER)
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
}
