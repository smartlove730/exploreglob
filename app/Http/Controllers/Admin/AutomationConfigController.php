<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\DriveApiKey;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\PlanEnforcementService;
use App\Services\InstagramService;
use RuntimeException;

class AutomationConfigController extends Controller
{
    public function __construct(
        private readonly PlanEnforcementService $planEnforcementService,
        private readonly InstagramService $instagramService
    )
    {
    }

    public function index()
    {
        $configs = AutomationConfig::query()
            ->ownedBy(Auth::user())
            ->with(['app', 'page', 'driveApiKey'])
            ->latest()
            ->paginate(20);

        $instagramUsernamesByPageId = [];
        foreach ($configs as $config) {
            $page = $config->page;
            if (!$page || isset($instagramUsernamesByPageId[$page->id])) {
                continue;
            }

            if (!$page->instagram_business_account_id) {
                $instagramUsernamesByPageId[$page->id] = null;
                continue;
            }

            try {
                $instagramUsernamesByPageId[$page->id] = $this->instagramService->fetchInstagramUsername(
                    $page->instagram_business_account_id,
                    $page->page_access_token
                );
            } catch (RuntimeException) {
                $instagramUsernamesByPageId[$page->id] = null;
            }
        }

        $configs->getCollection()->transform(function (AutomationConfig $config) use ($instagramUsernamesByPageId) {
            $pageId = $config->page?->id;
            $username = $pageId ? ($instagramUsernamesByPageId[$pageId] ?? null) : null;
            $config->setAttribute('instagram_display_name', $username ? '@'.Str::ltrim($username, '@') : 'Instagram not connected');

            return $config;
        });

        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'last_activity' => AutomationPostLog::query()->ownedBy(Auth::user())->latest('created_at')->value('created_at'),
        ];

        return view('admin.automations.index', compact('configs', 'queueStats'));
    }

    public function create()
    {
        return view('admin.automations.create', [
            'apps' => FacebookApp::query()->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $pageIds = collect($data['page_ids'] ?? [($data['page_id'] ?? null)])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        abort_if($pageIds->isEmpty(), 422, 'Select at least one page.');
        $this->assertDriveKeyIsActive((int) $data['drive_api_key_id']);
        $this->planEnforcementService->assertCanCreateAutomation(Auth::user(), $pageIds->count());
        $baseData = collect($data)->except(['page_ids'])->all();
        $baseData['user_id'] = Auth::id();

        foreach ($pageIds as $pageId) {
            $this->assertAuthorizedAppAndPage((int) $data['app_id'], $pageId);
            AutomationConfig::create(array_merge($baseData, ['page_id' => $pageId]));
        }

        return redirect()->route('admin.automations.index')->with('success', $pageIds->count().' automation config(s) created successfully.');
    }

    public function edit(AutomationConfig $automation)
    {
        $this->authorizeAutomation($automation);

        return view('admin.automations.edit', [
            'automation' => $automation,
            'apps' => FacebookApp::query()->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AutomationConfig $automation): RedirectResponse
    {
        $this->authorizeAutomation($automation);

        $data = $this->validateData($request);
        $pageIds = collect($data['page_ids'] ?? [($data['page_id'] ?? null)])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        abort_if($pageIds->isEmpty(), 422, 'Select at least one page.');
        $this->assertDriveKeyIsActive((int) $data['drive_api_key_id']);
        $baseData = collect($data)->except(['page_ids', 'page_id'])->all();

        $primaryPageId = (int) $pageIds->first();
        $this->assertAuthorizedAppAndPage((int) $data['app_id'], $primaryPageId);
        $automation->update(array_merge($baseData, ['page_id' => $primaryPageId]));

        $createdCount = 0;
        $additional = max(0, $pageIds->count() - 1);
        if ($additional > 0) {
            $this->planEnforcementService->assertCanCreateAutomation(Auth::user(), $additional);
        }
        foreach ($pageIds->slice(1) as $pageId) {
            $this->assertAuthorizedAppAndPage((int) $data['app_id'], (int) $pageId);
            AutomationConfig::create(array_merge($baseData, [
                'user_id' => Auth::id(),
                'page_id' => (int) $pageId,
            ]));
            $createdCount++;
        }

        return redirect()->route('admin.automations.index')->with('success', $createdCount > 0
            ? "Automation updated and {$createdCount} additional config(s) created."
            : 'Automation config updated successfully.');
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

    private function authorizeAutomation(AutomationConfig $automation): void
    {
        if (!Auth::user()?->isAdmin() && $automation->user_id !== Auth::id()) {
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
            'page_ids' => 'nullable|array|min:1',
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

    private function assertAuthorizedAppAndPage(int $appId, int $pageId): void
    {
        $validPage = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereKey($pageId)
            ->where('facebook_app_id', $appId)
            ->exists();

        abort_unless($validPage, 422, 'Selected app/page is not authorized for this user.');
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
