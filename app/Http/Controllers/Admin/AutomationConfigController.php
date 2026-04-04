<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\DriveApiKey;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\User;
use App\Jobs\RunAutomationJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutomationConfigController extends Controller
{
    public function index()
    {
        $configs = AutomationConfig::query()
            ->ownedBy(Auth::user())
            ->with(['app', 'page', 'driveApiKey'])
            ->latest()
            ->paginate(20);

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

        return view('admin.automations.index', compact('configs', 'queueStats', 'inProgressLogs'));
    }

    public function create()
    {
        return view('admin.automations.create', [
            'apps' => $this->availableApps(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedAppId' => $this->resolveSelectedAppId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->applyDefaultAppSelection($this->validateData($request));
        $pageIds = collect($data['page_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $this->assertAuthorizedAppAndPages((int) $data['app_id'], $pageIds);
        $this->assertDriveKeyIsActive((int) $data['drive_api_key_id']);
        $data['user_id'] = Auth::id();

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
            'apps' => $this->availableApps(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedAppId' => $this->resolveSelectedAppId(),
        ]);
    }

    public function update(Request $request, AutomationConfig $automation): RedirectResponse
    {
        $this->authorizeAutomation($automation);

        $data = $this->applyDefaultAppSelection($this->validateData($request));
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

    private function availableApps()
    {
        if (Auth::user()?->isAdmin()) {
            return FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();
        }

        return FacebookApp::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_admin', true)->orWhere('role', User::ROLE_ADMIN))
            ->orderBy('name')
            ->get();
    }

    private function resolveSelectedAppId(): int
    {
        return (int) $this->availableApps()->first()?->id;
    }

    private function applyDefaultAppSelection(array $data): array
    {
        if (Auth::user()?->isAdmin()) {
            return $data;
        }

        $appId = $this->resolveSelectedAppId();
        abort_unless($appId > 0, 422, 'No active admin Facebook app is configured.');
        $data['app_id'] = $appId;

        return $data;
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
}
