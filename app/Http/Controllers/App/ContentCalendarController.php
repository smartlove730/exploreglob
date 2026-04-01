<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\FacebookPage;
use App\Models\ScheduledPost;
use App\Models\UserMedia;
use App\Services\PlanEnforcementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ContentCalendarController extends Controller
{
    public function __construct(private readonly PlanEnforcementService $planEnforcementService)
    {
    }

    public function index()
    {
        $pages = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderBy('page_name')
            ->get(['id', 'facebook_app_id', 'page_name']);
        $mediaItems = UserMedia::query()
            ->ownedBy(Auth::user())
            ->latest()
            ->get(['id', 'type', 'original_name', 'path']);

        return view('app.calendar.index', [
            'pages' => $pages,
            'mediaItems' => $mediaItems,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $query = ScheduledPost::query()
            ->ownedBy(Auth::user())
            ->with('page:id,page_name');

        if ($request->filled('start')) {
            $query->where('scheduled_for', '>=', $request->date('start'));
        }

        if ($request->filled('end')) {
            $query->where('scheduled_for', '<=', $request->date('end'));
        }

        $events = $query->orderBy('scheduled_for')->get()->map(function (ScheduledPost $post) {
            return [
                'id' => $post->id,
                'title' => mb_strimwidth($post->message, 0, 50, '…'),
                'start' => optional($post->scheduled_for)->toIso8601String(),
                'allDay' => false,
                'extendedProps' => [
                    'message' => $post->message,
                    'status' => $post->status,
                    'page_id' => $post->page_id,
                    'page_name' => $post->page?->page_name,
                    'platforms' => $post->platforms ?? [],
                    'media_type' => $post->media_type,
                    'image_url' => $post->image_url,
                    'video_url' => $post->video_url,
                    'last_error' => $post->last_error,
                ],
            ];
        })->values();

        return response()->json(['events' => $events]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $page = $this->resolveAuthorizedPage((int) $data['page_id']);

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is invalid.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $this->planEnforcementService->assertCanPost(Auth::user(), $platforms);
        $resolved = $this->resolveMediaSelection($data);

        ScheduledPost::create([
            'user_id' => Auth::id(),
            'page_id' => $page->id,
            'message' => $data['message'],
            'media_type' => $data['media_type'] ?? 'image',
            'image_url' => $resolved['image_url'],
            'video_path' => null,
            'video_url' => $resolved['video_url'],
            'platforms' => $platforms,
            'scheduled_for' => $data['scheduled_for'],
            'status' => ScheduledPost::STATUS_PENDING,
            'last_error' => null,
        ]);

        return back()->with('success', 'Scheduled post created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $post = ScheduledPost::query()->ownedBy(Auth::user())->findOrFail($id);

        if ($post->status === ScheduledPost::STATUS_PUBLISHED) {
            return back()->with('error', 'Published posts cannot be edited.');
        }

        $data = $this->validatePayload($request);
        $page = $this->resolveAuthorizedPage((int) $data['page_id']);

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is invalid.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $this->planEnforcementService->assertCanPost(Auth::user(), $platforms);
        $resolved = $this->resolveMediaSelection($data);

        $post->update([
            'page_id' => $page->id,
            'message' => $data['message'],
            'media_type' => $data['media_type'] ?? 'image',
            'image_url' => $resolved['image_url'],
            'video_url' => $resolved['video_url'],
            'platforms' => $platforms,
            'scheduled_for' => $data['scheduled_for'],
            'status' => ScheduledPost::STATUS_PENDING,
            'response_json' => null,
            'last_error' => null,
        ]);

        return back()->with('success', 'Scheduled post updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $post = ScheduledPost::query()->ownedBy(Auth::user())->findOrFail($id);

        if ($post->status === ScheduledPost::STATUS_PUBLISHED) {
            return back()->with('error', 'Published posts cannot be deleted.');
        }

        $post->update(['status' => ScheduledPost::STATUS_CANCELLED]);

        return back()->with('success', 'Scheduled post cancelled.');
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'media_type' => 'nullable|string|in:image,video',
            'image_url' => 'nullable|url|max:2048',
            'video_url' => 'nullable|url|max:2048',
            'media_id' => 'nullable|integer|exists:user_media,id',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram,google_business',
            'scheduled_for' => 'required|date|after:now',
        ]);

        if (($data['media_type'] ?? 'image') === 'video' && empty($data['video_url'])) {
            throw ValidationException::withMessages(['video_url' => 'Video URL is required for video posts.']);
        }

        if (in_array('instagram', $data['platforms'], true) && ($data['media_type'] ?? 'image') === 'image' && empty($data['image_url'])) {
            throw ValidationException::withMessages(['image_url' => 'Instagram requires an image URL for image posts.']);
        }

        return $data;
    }

    private function resolveAuthorizedPage(int $pageId): ?FacebookPage
    {
        return FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereKey($pageId)
            ->where('is_active', true)
            ->first();
    }

    private function resolveMediaSelection(array $data): array
    {
        $imageUrl = $data['image_url'] ?? null;
        $videoUrl = $data['video_url'] ?? null;
        $mediaId = (int) ($data['media_id'] ?? 0);

        if ($mediaId > 0) {
            $selected = UserMedia::query()->ownedBy(Auth::user())->whereKey($mediaId)->first();
            if ($selected) {
                if ($selected->type === UserMedia::TYPE_VIDEO) {
                    $videoUrl = $selected->public_url;
                } else {
                    $imageUrl = $selected->public_url;
                }
            }
        }

        return [
            'image_url' => $imageUrl,
            'video_url' => $videoUrl,
        ];
    }
}
