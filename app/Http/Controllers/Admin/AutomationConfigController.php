<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationQueueItem;
use App\Models\AutomationRule;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Services\AutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomationConfigController extends Controller
{
    public function __construct(private readonly AutomationService $automationService)
    {
    }

    public function index()
    {
        $rules = AutomationRule::query()
            ->ownedBy(Auth::user())
            ->with('app')
            ->latest()
            ->paginate(20);

        $pages = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $rules->getCollection()->flatMap(fn (AutomationRule $rule) => $rule->page_ids ?: [])->unique())
            ->get(['id', 'page_name'])
            ->keyBy('id');

        $queueItems = AutomationQueueItem::query()
            ->ownedBy(Auth::user())
            ->with(['rule:id,name', 'page:id,page_name'])
            ->latest()
            ->limit(40)
            ->get();

        return view('admin.automations.index', compact('rules', 'pages', 'queueItems'));
    }

    public function create()
    {
        return view('admin.automations.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $rule = AutomationRule::create($data + [
            'user_id' => Auth::id(),
            'status' => AutomationRule::STATUS_ACTIVE,
            'daily_limit' => min(AutomationService::MAX_DAILY_POSTS_PER_PAGE, (int) $data['daily_limit']),
        ]);

        $result = $this->automationService->queueRule($rule, true);

        return redirect()
            ->route('admin.automations.index')
            ->with('success', "Automation created. {$result['queued']} post(s) queued.");
    }

    public function edit(AutomationRule $automation)
    {
        $this->authorizeRule($automation);

        return view('admin.automations.edit', $this->formData() + ['automation' => $automation]);
    }

    public function update(Request $request, AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $data = $this->validated($request);

        $automation->update($data + [
            'daily_limit' => min(AutomationService::MAX_DAILY_POSTS_PER_PAGE, (int) $data['daily_limit']),
        ]);

        return redirect()->route('admin.automations.index')->with('success', 'Automation updated.');
    }

    public function destroy(AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $automation->delete();

        return back()->with('success', 'Automation deleted.');
    }

    public function pause(AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $automation->update([
            'status' => AutomationRule::STATUS_PAUSED,
            'paused_at' => now(),
        ]);

        return back()->with('success', 'Automation paused.');
    }

    public function resume(AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $automation->update([
            'status' => AutomationRule::STATUS_ACTIVE,
            'paused_at' => null,
            'stopped_at' => null,
            'next_run_at' => now(),
        ]);

        return back()->with('success', 'Automation resumed.');
    }

    public function stop(AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $automation->update([
            'status' => AutomationRule::STATUS_STOPPED,
            'stopped_at' => now(),
        ]);

        $automation->queueItems()
            ->where('status', AutomationQueueItem::STATUS_QUEUED)
            ->update([
                'status' => AutomationQueueItem::STATUS_CANCELLED,
                'completed_at' => now(),
                'last_error' => 'Automation stopped by user.',
            ]);

        return back()->with('success', 'Automation stopped and queued posts cancelled.');
    }

    public function queueNow(AutomationRule $automation): RedirectResponse
    {
        $this->authorizeRule($automation);
        $result = $this->automationService->queueRule($automation, true);

        return back()->with('success', "{$result['queued']} post(s) queued. {$result['skipped']} skipped.");
    }

    private function formData(): array
    {
        return [
            'apps' => FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'pages' => FacebookPage::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('page_name')->get(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'driveFolders' => DriveFolder::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function driveFolders(Request $request): JsonResponse
    {
        $request->validate([
            'drive_api_key_id' => ['required', 'integer'],
        ]);

        $folders = DriveFolder::query()
            ->ownedBy(Auth::user())
            ->where('drive_api_key_id', (int) $request->input('drive_api_key_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'folder_url']);

        return response()->json($folders);
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'schedule_times' => collect($request->input('schedule_times', []))
                ->map(fn ($time) => trim((string) $time))
                ->filter()
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'app_id' => ['required', 'integer', 'exists:facebook_apps,id'],
            'page_ids' => ['required', 'array', 'min:1'],
            'page_ids.*' => ['required', 'integer', 'exists:facebook_pages,id'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['required', 'string', 'in:facebook,instagram'],
            'media_source_type' => ['required', 'string', 'in:urls,drive'],
            'media_urls' => ['nullable', 'string', 'max:60000'],
            'drive_link' => ['nullable', 'string', 'max:4096'],
            'drive_api_key_id' => ['nullable', 'integer', 'exists:drive_api_keys,id'],
            'post_frequency' => ['required', 'integer', 'min:1', 'max:3'],
            'schedule_times' => ['required', 'array', 'min:1', 'max:3'],
            'schedule_times.*' => ['required', 'date_format:H:i'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:3'],
            'caption_templates' => ['required', 'string', 'max:60000'],
            'hashtag_templates' => ['nullable', 'string', 'max:20000'],
        ]);

        $pageCount = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $data['page_ids'])
            ->where('facebook_app_id', (int) $data['app_id'])
            ->where('is_active', true)
            ->count();

        abort_unless($pageCount === count(array_unique($data['page_ids'])), 422, 'Selected pages are not valid for this app.');

        $payload = $data['media_source_type'] === 'drive'
            ? [
                'drive_link' => $data['drive_link'] ?? '',
                'drive_api_key_id' => (int) ($data['drive_api_key_id'] ?? 0),
            ]
            : [
                'urls' => collect(preg_split('/\R+/', (string) ($data['media_urls'] ?? '')))
                    ->map(fn (string $url) => trim($url))
                    ->filter()
                    ->values()
                    ->all(),
            ];

        abort_if($data['media_source_type'] === 'drive' && (empty($payload['drive_link']) || empty($payload['drive_api_key_id'])), 422, 'Drive automations require a Drive link and account.');
        abort_if($data['media_source_type'] === 'urls' && empty($payload['urls']), 422, 'Add at least one media URL.');

        return [
            'name' => $data['name'],
            'app_id' => (int) $data['app_id'],
            'page_ids' => array_values(array_unique(array_map('intval', $data['page_ids']))),
            'platforms' => array_values(array_unique($data['platforms'])),
            'media_source_type' => $data['media_source_type'],
            'media_source_payload' => $payload,
            'post_frequency' => (int) $data['post_frequency'],
            'schedule_times' => array_values($data['schedule_times']),
            'timezone' => config('app.timezone', 'UTC'),
            'daily_limit' => (int) $data['daily_limit'],
            'caption_templates' => $this->lines($data['caption_templates']),
            'hashtag_templates' => $this->lines($data['hashtag_templates'] ?? ''),
        ];
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\R+/', $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function authorizeRule(AutomationRule $automation): void
    {
        abort_if(!Auth::user()?->isAdmin() && $automation->user_id !== Auth::id(), 403);
    }
}
