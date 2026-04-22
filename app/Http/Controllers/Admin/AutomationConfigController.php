<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\DriveApiKey;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Jobs\RunAutomationJob;
use App\Services\AutomationService;
use App\Services\InstagramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AutomationConfigController extends Controller
{
    public function __construct(private readonly InstagramService $instagramService)
    {
    }

    public function index()
    {
        $configs = AutomationConfig::query()
            ->ownedBy(Auth::user())
            ->with(['app', 'page', 'driveApiKey'])
            ->latest()
            ->paginate(20);

        $instagramUsernames = $this->resolveInstagramUsernames($configs->getCollection());

        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'last_activity' => AutomationPostLog::query()->ownedBy(Auth::user())->latest('created_at')->value('created_at'),
        ];

        $inProgressLogs = AutomationPostLog::query()
            ->ownedBy(Auth::user())
            ->with(['automationConfig', 'page'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderByRaw('COALESCE(scheduled_for, created_at) asc')
            ->limit(25)
            ->get();

        $noPostableContentWarning = AutomationPostLog::query()
            ->ownedBy(Auth::user())
            ->where('message', 'like', AutomationService::NO_POSTABLE_MEDIA_MESSAGE.'%')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        return view('admin.automations.index', compact('configs', 'queueStats', 'inProgressLogs', 'instagramUsernames', 'noPostableContentWarning'));
    }

    public function create()
    {
        return view('admin.automations.create', [
            'apps' => FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedDriveApiKeyId' => (int) old('drive_api_key_id', $this->resolvePreferredDriveApiKeyId()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $pageIds = collect($data['page_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $this->assertAuthorizedAppAndPages((int) $data['app_id'], $pageIds);
        $this->assertDriveKeyIsActive((int) $data['drive_api_key_id']);
        $data['user_id'] = Auth::id();

        // Enforce automation limit from the user's active plan
        $user = Auth::user();
        if ($user && !$user->isAdmin()) {
            $activeSubscription = \App\Models\Subscription::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_AUTHENTICATED])
                ->with('plan')
                ->latest('id')
                ->first();

            if ($activeSubscription && $activeSubscription->plan) {
                $currentCount = AutomationConfig::where('user_id', $user->id)->count();
                $limit = (int) $activeSubscription->plan->automation_limit;

                if (($currentCount + count($pageIds)) > $limit) {
                    return redirect()->route('admin.automations.index')
                        ->with('error', "Automation limit reached. Your plan allows {$limit} automation(s). You currently have {$currentCount}.");
                }
            }
        }

        $created = 0;
        foreach ($pageIds as $pageId) {
            AutomationConfig::create($data + ['page_id' => $pageId]);
            $created++;
        }

        return redirect()->route('admin.automations.index')->with('success', $created.' automation config(s) created successfully.');
    }

    public function edit(AutomationConfig $automation)
    {
        $this->authorizeAutomation($automation);

        return view('admin.automations.edit', [
            'automation' => $automation,
            'apps' => FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedDriveApiKeyId' => (int) old('drive_api_key_id', $automation->drive_api_key_id),
        ]);
    }

    public function update(Request $request, AutomationConfig $automation): RedirectResponse
    {
        $this->authorizeAutomation($automation);

        $data = $this->validateData($request);
        $pageIds = collect($data['page_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $this->assertAuthorizedAppAndPages((int) $data['app_id'], $pageIds);
        $this->assertDriveKeyIsActive((int) $data['drive_api_key_id']);

        $baseData = collect($data)->except(['page_ids', 'page_id'])->all();

        $primaryPageId = array_shift($pageIds);
        $automation->update($baseData + ['page_id' => $primaryPageId]);

        $created = 0;
        foreach ($pageIds as $pageId) {
            $exists = AutomationConfig::query()
                ->ownedBy(Auth::user())
                ->where('app_id', (int) $data['app_id'])
                ->where('page_id', $pageId)
                ->exists();

            if ($exists) {
                continue;
            }

            AutomationConfig::create($baseData + [
                'user_id' => Auth::id(),
                'page_id' => $pageId,
            ]);
            $created++;
        }

        return redirect()->route('admin.automations.index')->with('success', 'Automation config updated successfully.'.($created > 0 ? " {$created} additional config(s) created for other selected pages." : ''));
    }

    public function destroy(AutomationConfig $automation): RedirectResponse
    {
        $this->authorizeAutomation($automation);

        $automation->delete();

        return redirect()->route('admin.automations.index')->with('success', 'Automation config deleted successfully.');
    }

    public function toggle(AutomationConfig $automation): RedirectResponse
    {
        $this->authorizeAutomation($automation);

        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', 'Automation status updated.');
    }

    public function cancelExecution(AutomationPostLog $execution): RedirectResponse
    {
        $this->authorizeExecution($execution);

        if (!in_array($execution->status, ['scheduled', 'in_progress'], true)) {
            return back()->with('error', 'Only scheduled or in-progress executions can be deleted.');
        }

        $execution->update([
            'status' => 'cancelled',
            'message' => 'Execution cancelled by user.',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Execution deleted successfully.');
    }

    public function executeNow(AutomationPostLog $execution): RedirectResponse
    {
        $this->authorizeExecution($execution);

        if ($execution->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled executions can be run immediately.');
        }

        $execution->update([
            'status' => 'scheduled',
            'message' => 'Execution promoted to run immediately.',
            'scheduled_for' => now(),
            'started_at' => null,
            'completed_at' => null,
        ]);

        RunAutomationJob::dispatch($execution->automation_config_id, true, $execution->id);

        return back()->with('success', 'Execution queued to run immediately.');
    }

    public function bulkExecuteNow(Request $request): RedirectResponse
    {
        $executionIds = collect($request->validate([
            'execution_ids' => ['required', 'array', 'min:1'],
            'execution_ids.*' => ['required', 'integer', 'distinct'],
        ])['execution_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $executions = AutomationPostLog::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $executionIds)
            ->where('status', 'scheduled')
            ->get();

        if ($executions->isEmpty()) {
            return back()->with('error', 'No scheduled executions were selected.');
        }

        $nextRunAt = now();
        foreach ($executions as $execution) {
            $nextRunAt = $nextRunAt->copy()->addMinutes(random_int(1, 2));

            $execution->update([
                'status' => 'scheduled',
                'message' => 'Execution promoted to run immediately.',
                'scheduled_for' => $nextRunAt,
                'started_at' => null,
                'completed_at' => null,
            ]);

            RunAutomationJob::dispatch($execution->automation_config_id, true, $execution->id)
                ->delay($nextRunAt);
        }

        return back()->with('success', $executions->count().' execution(s) queued to run immediately.');
    }

    public function bulkCancelExecutions(Request $request): RedirectResponse
    {
        $executionIds = collect($request->validate([
            'execution_ids' => ['required', 'array', 'min:1'],
            'execution_ids.*' => ['required', 'integer', 'distinct'],
        ])['execution_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $affected = AutomationPostLog::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $executionIds)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->update([
                'status' => 'cancelled',
                'message' => 'Execution cancelled by user.',
                'completed_at' => now(),
            ]);

        if ($affected === 0) {
            return back()->with('error', 'No scheduled or in-progress executions were selected.');
        }

        return back()->with('success', $affected.' execution(s) deleted successfully.');
    }

    private function authorizeAutomation(AutomationConfig $automation): void
    {
        if (!Auth::user()?->isAdmin() && $automation->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function authorizeExecution(AutomationPostLog $execution): void
    {
        if (!Auth::user()?->isAdmin() && $execution->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'nullable|string|max:255',
            'prompt' => 'required|string|max:5000',
            'drive_link' => 'required|url|max:4096',
            'drive_api_key_id' => 'required|integer|exists:drive_api_keys,id',
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
            'platforms' => 'required|string|in:facebook,instagram,both',
            'runs_per_day' => 'required|integer|min:1|max:24',
            'post_limit_per_day' => 'required|integer|min:1|max:100',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function pagesForUser()
    {
        return FacebookPage::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderBy('page_name')
            ->get();
    }

    private function assertAuthorizedAppAndPages(int $appId, array $pageIds): void
    {
        $validPagesCount = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $pageIds)
            ->where('facebook_app_id', $appId)
            ->count();

        abort_unless($validPagesCount === count($pageIds), 422, 'Selected app/pages are not authorized for this user.');
    }

    private function assertDriveKeyIsActive(int $driveApiKeyId): void
    {
        $active = DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->whereKey($driveApiKeyId)
            ->where('is_active', true)
            ->exists();

        abort_unless($active, 422, 'Selected Google Drive API key is inactive.');
    }

    private function resolveInstagramUsernames($configs): array
    {
        $usernamesByPageId = [];
        $pages = $configs
            ->pluck('page')
            ->filter()
            ->unique('id');

        foreach ($pages as $page) {
            if (!$page->instagram_business_account_id) {
                $usernamesByPageId[$page->id] = null;
                continue;
            }

            try {
                $username = $this->instagramService->fetchInstagramUsername(
                    (string) $page->instagram_business_account_id,
                    (string) $page->page_access_token
                );
                $usernamesByPageId[$page->id] = $username;
            } catch (Throwable $exception) {
                $usernamesByPageId[$page->id] = null;
            }
        }

        return $usernamesByPageId;
    }

    private function resolvePreferredDriveApiKeyId(): int
    {
        if (!$this->supportsDriveOauthColumns()) {
            return (int) DriveApiKey::query()
                ->ownedBy(Auth::user())
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->value('id');
        }

        $oauthDriveKeyId = (int) DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNotNull('oauth_access_token')
                    ->orWhereNotNull('oauth_refresh_token');
            })
            ->orderByDesc('updated_at')
            ->value('id');

        if ($oauthDriveKeyId > 0) {
            return $oauthDriveKeyId;
        }

        return (int) DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('id');
    }

    private function supportsDriveOauthColumns(): bool
    {
        return Schema::hasColumns('drive_api_keys', [
            'oauth_access_token',
            'oauth_refresh_token',
        ]);
    }
}
