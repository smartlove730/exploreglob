<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AutomationQueueItem;
use App\Models\AutomationRule;
use App\Models\FacebookAccount;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\GoogleAccount;
use App\Models\ScheduledPost;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $isAdmin = (bool) $user?->isAdmin();

        $userScope = fn (Builder $query): Builder => $isAdmin
            ? $query
            : $query->where($query->qualifyColumn('user_id'), $user?->id);

        $today = now()->startOfDay();
        $last14Days = collect(CarbonPeriod::create($today->copy()->subDays(13), $today))
            ->map(fn (Carbon $date) => $date->toDateString())
            ->values();

        $totalPostsCreated = FacebookPost::query()->tap($userScope)->count();
        $scheduledPosts = ScheduledPost::query()->tap($userScope)
            ->whereIn('status', [ScheduledPost::STATUS_PENDING, ScheduledPost::STATUS_PROCESSING])
            ->count();
        $postedSuccessfully = FacebookPost::query()->tap($userScope)
            ->where('status', FacebookPost::STATUS_PUBLISHED)
            ->count();
        $failedPosts = FacebookPost::query()->tap($userScope)
            ->where('status', FacebookPost::STATUS_FAILED)
            ->count()
            + ScheduledPost::query()->tap($userScope)->where('status', ScheduledPost::STATUS_FAILED)->count();

        $postsPerDay = $last14Days->map(fn (string $date) => [
            'label' => Carbon::parse($date)->format('M j'),
            'value' => FacebookPost::query()
                ->tap($userScope)
                ->whereDate('created_at', $date)
                ->count(),
        ]);

        $userGrowth = $last14Days->map(fn (string $date) => [
            'label' => Carbon::parse($date)->format('M j'),
            'value' => $isAdmin
                ? User::query()->whereDate('created_at', '<=', $date)->count()
                : 0,
        ]);

        $successVsFailed = [
            'success' => $postedSuccessfully,
            'failed' => $failedPosts,
            'scheduled' => $scheduledPosts,
        ];

        $connectedAccountStats = [
            'facebook' => FacebookAccount::query()->tap($userScope)->count(),
            'google' => GoogleAccount::query()->tap($userScope)->count(),
            'pages' => FacebookPage::query()->tap($userScope)->count(),
        ];

        $stats = [
            [
                'label' => $isAdmin ? 'Total Users' : 'Team Seats',
                'value' => $isAdmin ? User::query()->count() : 1,
                'hint' => $isAdmin ? 'Registered accounts' : 'Current workspace',
                'tone' => 'primary',
            ],
            [
                'label' => 'Active Users',
                'value' => $isAdmin
                    ? Subscription::query()
                        ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED])
                        ->distinct('user_id')
                        ->count('user_id')
                    : (Subscription::query()->where('user_id', $user?->id)->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_AUTHENTICATED])->exists() ? 1 : 0),
                'hint' => 'Active subscription users',
                'tone' => 'success',
            ],
            [
                'label' => 'Facebook Accounts',
                'value' => $connectedAccountStats['facebook'],
                'hint' => 'Connected Meta profiles',
                'tone' => 'facebook',
            ],
            [
                'label' => 'Google Accounts',
                'value' => $connectedAccountStats['google'],
                'hint' => 'Connected Google identities',
                'tone' => 'google',
            ],
            [
                'label' => 'Synced Pages',
                'value' => $connectedAccountStats['pages'],
                'hint' => 'Available social pages',
                'tone' => 'info',
            ],
            [
                'label' => 'Posts Created',
                'value' => $totalPostsCreated,
                'hint' => 'Manual and automated posts',
                'tone' => 'dark',
            ],
            [
                'label' => 'Scheduled Posts',
                'value' => $scheduledPosts,
                'hint' => 'Waiting or processing',
                'tone' => 'warning',
            ],
            [
                'label' => 'Posted Successfully',
                'value' => $postedSuccessfully,
                'hint' => 'Published social posts',
                'tone' => 'success',
            ],
            [
                'label' => 'Failed Posts',
                'value' => $failedPosts,
                'hint' => 'Need attention',
                'tone' => 'danger',
            ],
            [
                'label' => 'Running Automations',
                'value' => AutomationQueueItem::query()
                    ->tap($userScope)
                    ->whereIn('status', ['queued', 'processing'])
                    ->count(),
                'hint' => AutomationRule::query()->tap($userScope)->where('status', AutomationRule::STATUS_ACTIVE)->count().' active rules',
                'tone' => 'purple',
            ],
        ];

        $recentActivities = ActivityLog::query()
            ->when(!$isAdmin, fn (Builder $query) => $query->where('user_id', $user?->id))
            ->with('user:id,name,email')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'isAdmin' => $isAdmin,
            'stats' => $stats,
            'postsPerDay' => $postsPerDay,
            'successVsFailed' => $successVsFailed,
            'userGrowth' => $userGrowth,
            'connectedAccountStats' => $connectedAccountStats,
            'recentActivities' => $recentActivities,
        ]);
    }
}
