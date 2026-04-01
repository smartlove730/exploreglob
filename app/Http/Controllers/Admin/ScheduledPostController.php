<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Models\ScheduledPost;
use App\Services\PlanEnforcementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ScheduledPostController extends Controller
{
    public function __construct(private readonly PlanEnforcementService $planEnforcementService)
    {
    }

    public function index()
    {
        $scheduledPosts = ScheduledPost::query()
            ->ownedBy(Auth::user())
            ->with('page')
            ->latest('scheduled_for')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $scheduledPosts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $this->planEnforcementService->assertCanPost(Auth::user(), $platforms);

        ScheduledPost::create([
            'user_id' => Auth::id(),
            'page_id' => $page->id,
            'message' => $data['message'],
            'media_type' => $data['media_type'] ?? 'image',
            'image_url' => $data['image_url'] ?? null,
            'video_path' => $data['video_path'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'platforms' => $platforms,
            'scheduled_for' => $data['scheduled_for'],
            'status' => ScheduledPost::STATUS_PENDING,
        ]);

        return back()->with('success', 'Post scheduled successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $scheduledPost = ScheduledPost::query()->ownedBy(Auth::user())->findOrFail($id);
        if ($scheduledPost->status === ScheduledPost::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['status' => 'Published scheduled posts cannot be edited.']);
        }

        $data = $this->validatePayload($request);
        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $this->planEnforcementService->assertCanPost(Auth::user(), $platforms);

        $scheduledPost->update([
            'page_id' => $page->id,
            'message' => $data['message'],
            'media_type' => $data['media_type'] ?? 'image',
            'image_url' => $data['image_url'] ?? null,
            'video_path' => $data['video_path'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'platforms' => $platforms,
            'scheduled_for' => $data['scheduled_for'],
            'status' => ScheduledPost::STATUS_PENDING,
            'last_error' => null,
            'response_json' => null,
        ]);

        return back()->with('success', 'Scheduled post updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $scheduledPost = ScheduledPost::query()->ownedBy(Auth::user())->findOrFail($id);

        if ($scheduledPost->status === ScheduledPost::STATUS_PUBLISHED) {
            return back()->with('error', 'Published scheduled posts cannot be deleted.');
        }

        $scheduledPost->update(['status' => ScheduledPost::STATUS_CANCELLED]);

        return back()->with('success', 'Scheduled post cancelled.');
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'media_type' => 'nullable|string|in:image,video',
            'image_url' => 'nullable|url|max:2048',
            'video_path' => 'nullable|string|max:2048',
            'video_url' => 'nullable|url|max:2048',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram,google_business',
            'scheduled_for' => 'required|date|after:now',
        ]);

        if (($data['media_type'] ?? 'image') === 'video' && empty($data['video_url'])) {
            throw ValidationException::withMessages(['video_url' => 'Video URL is required for scheduled video posts.']);
        }

        if (in_array('instagram', $data['platforms'], true) && ($data['media_type'] ?? 'image') === 'image' && empty($data['image_url'])) {
            throw ValidationException::withMessages(['image_url' => 'Instagram requires an image URL for scheduled posts.']);
        }

        return $data;
    }

    private function resolveAuthorizedPage(int $appId, int $pageId): ?FacebookPage
    {
        return FacebookPage::query()->ownedBy(Auth::user())
            ->whereKey($pageId)
            ->where('facebook_app_id', $appId)
            ->where('is_active', true)
            ->first();
    }
}
