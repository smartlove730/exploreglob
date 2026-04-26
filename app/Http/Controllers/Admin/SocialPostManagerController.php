<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSocialPostDeletionJob;
use App\Models\FacebookPage;
use App\Models\SocialPostDeletionJob;
use App\Models\SyncedSocialPost;
use App\Services\MetaPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SocialPostManagerController extends Controller
{
    public function __construct(private readonly MetaPostService $metaPostService)
    {
    }

    public function index()
    {
        return view('admin.facebook.manage-posts', [
            'pages' => FacebookPage::query()
                ->ownedBy(Auth::user())
                ->where('is_active', true)
                ->orderBy('page_name')
                ->get(['id', 'page_name']),
        ]);
    }

    public function syncPosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_ids' => ['required', 'array', 'min:1'],
            'page_ids.*' => ['required', 'integer'],
        ]);

        $pages = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->whereIn('id', $data['page_ids'])
            ->where('is_active', true)
            ->get();

        $syncedCount = 0;
        $errors = [];

        foreach ($pages as $page) {
            try {
                $facebookPosts = $this->metaPostService->fetchFacebookPagePosts($page);
                $syncedCount += $this->upsertSyncedPosts($page, $facebookPosts);
            } catch (\Throwable $exception) {
                $errors[] = "{$page->page_name} (Facebook): {$exception->getMessage()}";
            }

            try {
                $instagramPosts = $this->metaPostService->fetchInstagramPosts($page);
                $syncedCount += $this->upsertSyncedPosts($page, $instagramPosts);
            } catch (\Throwable $exception) {
                $errors[] = "{$page->page_name} (Instagram): {$exception->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sync completed. {$syncedCount} record(s) inserted/updated.",
            'errors' => $errors,
        ]);
    }

    public function listPosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['required', 'integer'],
            'platform' => ['nullable', 'in:facebook,instagram'],
            'search' => ['nullable', 'string', 'max:255'],
            'external_post_id' => ['nullable', 'string', 'max:255'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
        ]);

        $query = SyncedSocialPost::query()
            ->where('user_id', Auth::id())
            ->with('page:id,page_name');

        if (!empty($data['page_ids'])) {
            $query->whereIn('facebook_page_id', $data['page_ids']);
        }

        if (!empty($data['platform'])) {
            $query->where('platform', $data['platform']);
        }

        if (!empty($data['search'])) {
            $search = trim((string) $data['search']);
            $query->where(function ($inner) use ($search) {
                $inner->where('content', 'like', "%{$search}%")
                    ->orWhere('external_post_id', 'like', "%{$search}%");
            });
        }

        if (!empty($data['external_post_id'])) {
            $query->where('external_post_id', 'like', '%'.trim((string) $data['external_post_id']).'%');
        }

        if (!empty($data['created_from'])) {
            $query->whereDate('external_created_at', '>=', $data['created_from']);
        }

        if (!empty($data['created_to'])) {
            $query->whereDate('external_created_at', '<=', $data['created_to']);
        }

        $posts = $query
            ->orderByRaw('COALESCE(external_created_at, updated_at, created_at) DESC')
            ->limit(1000)
            ->get();

        return response()->json([
            'success' => true,
            'posts' => $posts->map(fn (SyncedSocialPost $post) => [
                'id' => $post->id,
                'external_post_id' => $post->external_post_id,
                'platform' => $post->platform,
                'page_id' => $post->facebook_page_id,
                'page_name' => $post->page?->page_name,
                'content' => $post->content,
                'media_preview_url' => $post->media_preview_url,
                'created_time' => optional($post->external_created_at)->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function deletePosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['required', 'integer'],
        ]);

        $posts = SyncedSocialPost::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $data['post_ids'])
            ->get();

        $jobs = [];

        DB::transaction(function () use ($posts, &$jobs) {
            foreach ($posts as $post) {
                $existing = SocialPostDeletionJob::query()
                    ->where('synced_social_post_id', $post->id)
                    ->whereIn('status', [SocialPostDeletionJob::STATUS_PENDING, SocialPostDeletionJob::STATUS_PROCESSING])
                    ->first();

                if ($existing) {
                    $jobs[] = $existing;
                    continue;
                }

                $latestScheduledAt = SocialPostDeletionJob::query()
                    ->whereIn('status', [SocialPostDeletionJob::STATUS_PENDING, SocialPostDeletionJob::STATUS_PROCESSING])
                    ->lockForUpdate()
                    ->max('scheduled_for');

                $nextRunAt = $latestScheduledAt
                    ? Carbon::parse($latestScheduledAt)->addMinutes(2)
                    : now();

                if ($nextRunAt->lt(now())) {
                    $nextRunAt = now();
                }

                $job = SocialPostDeletionJob::create([
                    'user_id' => Auth::id(),
                    'facebook_page_id' => $post->facebook_page_id,
                    'synced_social_post_id' => $post->id,
                    'platform' => $post->platform,
                    'external_post_id' => $post->external_post_id,
                    'post_created_at' => $post->external_created_at,
                    'content_preview' => $post->content,
                    'media_preview_url' => $post->media_preview_url,
                    'status' => SocialPostDeletionJob::STATUS_PENDING,
                    'scheduled_for' => $nextRunAt,
                    'meta' => [
                        'synced_social_post_id' => $post->id,
                    ],
                ]);

                ProcessSocialPostDeletionJob::dispatch($job->id)
                    ->onQueue('social-deletions')
                    ->delay($nextRunAt);

                $jobs[] = $job;
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($jobs).' deletion job(s) scheduled.',
            'jobs' => collect($jobs)->map(fn (SocialPostDeletionJob $job) => [
                'id' => $job->id,
                'synced_social_post_id' => $job->synced_social_post_id,
                'status' => $job->status,
                'scheduled_for' => optional($job->scheduled_for)->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function statuses(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['success' => true, 'jobs' => []]);
        }

        $jobs = SocialPostDeletionJob::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $ids)
            ->get();

        return response()->json([
            'success' => true,
            'jobs' => $jobs->map(fn (SocialPostDeletionJob $job) => [
                'id' => $job->id,
                'synced_social_post_id' => $job->synced_social_post_id,
                'status' => $job->status,
                'error_message' => $job->error_message,
                'processed_at' => optional($job->processed_at)->toDateTimeString(),
            ])->values(),
        ]);
    }

    private function upsertSyncedPosts(FacebookPage $page, \Illuminate\Support\Collection $posts): int
    {
        $changes = 0;

        foreach ($posts as $post) {
            SyncedSocialPost::updateOrCreate(
                [
                    'facebook_page_id' => $page->id,
                    'platform' => $post['platform'],
                    'external_post_id' => $post['external_post_id'],
                ],
                [
                    'user_id' => Auth::id(),
                    'content' => $post['content'] ?? null,
                    'media_preview_url' => $post['media_preview_url'] ?? null,
                    'permalink' => $post['permalink'] ?? null,
                    'external_created_at' => $post['created_time'] ?? null,
                    'last_synced_at' => now(),
                ]
            );

            $changes++;
        }

        return $changes;
    }
}
