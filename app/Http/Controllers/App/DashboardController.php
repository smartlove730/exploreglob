<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\FacebookAccount;
use App\Models\GoogleAccount;
use App\Models\ScheduledPost;
use App\Services\PlanEnforcementService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private readonly PlanEnforcementService $planEnforcementService)
    {
    }

    public function index()
    {
        $user = Auth::user();

        $scheduledCount = ScheduledPost::query()
            ->ownedBy($user)
            ->whereIn('status', [ScheduledPost::STATUS_PENDING, ScheduledPost::STATUS_PROCESSING])
            ->count();

        $publishedCount = ScheduledPost::query()
            ->ownedBy($user)
            ->where('status', ScheduledPost::STATUS_PUBLISHED)
            ->count();

        $failedCount = ScheduledPost::query()
            ->ownedBy($user)
            ->where('status', ScheduledPost::STATUS_FAILED)
            ->count();

        $subscription = $this->planEnforcementService->getActiveSubscription($user);
        $plan = $subscription?->plan;
        $usage = (int) ($subscription?->posts_used ?? 0);
        $limit = (int) ($plan?->post_limit ?? 0);
        $remaining = max(0, $limit - $usage);
        $usagePercent = $limit > 0 ? min(100, (int) round(($usage / $limit) * 100)) : 0;

        $facebookConnected = FacebookAccount::query()->ownedBy($user)->exists();
        $googleConnected = GoogleAccount::query()->ownedBy($user)->exists();

        return view('app.dashboard', [
            'scheduledCount' => $scheduledCount,
            'publishedCount' => $publishedCount,
            'failedCount' => $failedCount,
            'subscription' => $subscription,
            'plan' => $plan,
            'usage' => $usage,
            'limit' => $limit,
            'remaining' => $remaining,
            'usagePercent' => $usagePercent,
            'facebookConnected' => $facebookConnected,
            'googleConnected' => $googleConnected,
        ]);
    }
}
