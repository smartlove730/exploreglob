<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
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
            ->with(['app', 'page'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'last_activity' => AutomationPostLog::query()->latest('created_at')->value('created_at'),
        ];

        return view('admin.automations.index', compact('configs', 'queueStats'));
    }

    public function create()
    {
        return view('admin.automations.create', [
            'apps' => FacebookApp::query()->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $this->assertAuthorizedAppAndPage((int) $data['app_id'], (int) $data['page_id']);
        $data['user_id'] = Auth::id();

        AutomationConfig::create($data);

        return redirect()->route('admin.automations.index')->with('success', 'Automation config created successfully.');
    }

    public function edit(AutomationConfig $automation)
    {
        abort_unless($automation->user_id === Auth::id(), 403);

        return view('admin.automations.edit', [
            'automation' => $automation,
            'apps' => FacebookApp::query()->where('is_active', true)->orderBy('name')->get(),
            'pages' => $this->pagesForUser(),
        ]);
    }

    public function update(Request $request, AutomationConfig $automation): RedirectResponse
    {
        abort_unless($automation->user_id === Auth::id(), 403);

        $data = $this->validateData($request);
        $this->assertAuthorizedAppAndPage((int) $data['app_id'], (int) $data['page_id']);
        $automation->update($data);

        return redirect()->route('admin.automations.index')->with('success', 'Automation config updated successfully.');
    }

    public function destroy(AutomationConfig $automation): RedirectResponse
    {
        abort_unless($automation->user_id === Auth::id(), 403);

        $automation->delete();

        return redirect()->route('admin.automations.index')->with('success', 'Automation config deleted successfully.');
    }

    public function toggle(AutomationConfig $automation): RedirectResponse
    {
        abort_unless($automation->user_id === Auth::id(), 403);

        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', 'Automation status updated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'nullable|string|max:255',
            'prompt' => 'required|string|max:5000',
            'drive_link' => 'required|url|max:4096',
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
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
            ->where('is_active', true)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->orderBy('page_name')
            ->get();
    }

    private function assertAuthorizedAppAndPage(int $appId, int $pageId): void
    {
        $validPage = FacebookPage::query()
            ->whereKey($pageId)
            ->where('facebook_app_id', $appId)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->exists();

        abort_unless($validPage, 422, 'Selected app/page is not authorized for this user.');
    }
}
