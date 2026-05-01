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
use Illuminate\Support\Collection;
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
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['required', 'integer'],
            'platform' => ['nullable', 'in:facebook,instagram'],
        ]);

        $pages = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->when(!empty($data['page_ids']), fn ($query) => $query->whereIn('id', $data['page_ids']))
            ->where('is_active', true)
            ->get();

        if ($pages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active Facebook pages are available. Connect and sync a page first.',
            ], 422);
        }

        $stats = [
            'loaded' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $errors = [];
        $platforms = empty($data['platform']) ? ['facebook', 'instagram'] : [$data['platform']];

        foreach ($pages as $page) {
            if (in_array('facebook', $platforms, true)) {
                try {
                    $pageStats = $this->upsertSyncedPosts($page, $this->metaPostService->fetchFacebookPagePosts($page));
                    $stats = $this->mergeStats($stats, $pageStats);
                } catch (\Throwable $exception) {
                    $stats['failed']++;
                    $errors[] = "{$page->page_name} (Facebook): {$exception->getMessage()}";
                }
            }

            if (in_array('instagram', $platforms, true)) {
                try {
                    $pageStats = $this->upsertSyncedPosts($page, $this->metaPostService->fetchInstagramPosts($page));
                    $stats = $this->mergeStats($stats, $pageStats);
                } catch (\Throwable $exception) {
                    $stats['failed']++;
                    $errors[] = "{$page->page_name} (Instagram): {$exception->getMessage()}";
                }
            }
        }

        $message = "Loaded {$stats['loaded']} post(s). {$stats['created']} new, {$stats['updated']} updated, {$stats['skipped']} skipped.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'stats' => $stats,
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
            'deletion_status' => ['nullable', 'in:ready,pending,processing,failed,completed'],
        ]);

        $query = SyncedSocialPost::query()
            ->where('user_id', Auth::id())
            ->with([
                'page:id,page_name',
                'latestDeletionJob:social_post_deletion_jobs.id,social_post_deletion_jobs.synced_social_post_id,social_post_deletion_jobs.status,social_post_deletion_jobs.error_message,social_post_deletion_jobs.scheduled_for,social_post_deletion_jobs.processed_at,social_post_deletion_jobs.updated_at',
            ]);

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
                    ->orWhere('external_post_id', 'like', "%{$search}%")
                    ->orWhereHas('page', fn ($pageQuery) => $pageQuery->where('page_name', 'like', "%{$search}%"));
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

        if (!empty($data['deletion_status'])) {
            $posts = $posts->filter(function (SyncedSocialPost $post) use ($data) {
                $status = $post->latestDeletionJob?->status ?? 'ready';

                return $status === $data['deletion_status'];
            })->values();
        }

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
                'permalink' => $post->permalink,
                'created_time' => optional($post->external_created_at)->toDateTimeString(),
                'last_synced_at' => optional($post->last_synced_at)->toDateTimeString(),
                'deletion_status' => $post->latestDeletionJob?->status ?? 'ready',
                'deletion_job_id' => $post->latestDeletionJob?->id,
                'deletion_error' => $post->latestDeletionJob?->error_message,
                'deletion_scheduled_for' => optional($post->latestDeletionJob?->scheduled_for)->toDateTimeString(),
                'deletion_processed_at' => optional($post->latestDeletionJob?->processed_at)->toDateTimeString(),
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

        $result = $this->scheduleDeletionJobs($posts);

        return response()->json([
            'success' => true,
            'message' => "{$result['queued']} deletion job(s) queued. {$result['skipped']} skipped.",
            'queued' => $result['queued'],
            'skipped' => $result['skipped'],
            'jobs' => collect($result['jobs'])->map(fn (SocialPostDeletionJob $job) => [
                'id' => $job->id,
                'synced_social_post_id' => $job->synced_social_post_id,
                'status' => $job->status,
                'scheduled_for' => optional($job->scheduled_for)->toDateTimeString(),
            ])->values(),
        ]);
    }

    public function retryFailed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['nullable', 'array'],
            'post_ids.*' => ['required', 'integer'],
        ]);

        $posts = SyncedSocialPost::query()
            ->where('user_id', Auth::id())
            ->when(!empty($data['post_ids']), fn ($query) => $query->whereIn('id', $data['post_ids']))
            ->whereHas('latestDeletionJob', fn ($query) => $query->where('status', SocialPostDeletionJob::STATUS_FAILED))
            ->get();

        $result = $this->scheduleDeletionJobs($posts, true);

        return response()->json([
            'success' => true,
            'message' => "{$result['queued']} failed deletion job(s) queued for retry. {$result['skipped']} skipped.",
            'queued' => $result['queued'],
            'skipped' => $result['skipped'],
            'jobs' => collect($result['jobs'])->map(fn (SocialPostDeletionJob $job) => [
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

    private function upsertSyncedPosts(FacebookPage $page, Collection $posts): array
    {
        $stats = [
            'loaded' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($posts as $post) {
            $record = SyncedSocialPost::firstOrNew(
                [
                    'facebook_page_id' => $page->id,
                    'platform' => $post['platform'],
                    'external_post_id' => $post['external_post_id'],
                ]
            );

            $record->fill([
                'user_id' => Auth::id(),
                'content' => $post['content'] ?? null,
                'media_preview_url' => $post['media_preview_url'] ?? null,
                'permalink' => $post['permalink'] ?? null,
                'external_created_at' => $post['created_time'] ?? null,
            ]);

            if (!$record->exists) {
                $stats['created']++;
            } elseif ($record->isDirty(['content', 'media_preview_url', 'permalink', 'external_created_at'])) {
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }

            $record->last_synced_at = now();
            $record->save();
            $stats['loaded']++;
        }

        return $stats;
    }

    private function mergeStats(array $base, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            $base[$key] = ($base[$key] ?? 0) + (int) $value;
        }

        return $base;
    }

    private function scheduleDeletionJobs(Collection $posts, bool $failedOnly = false): array
    {
        $jobs = [];
        $queued = 0;
        $skipped = 0;

        DB::transaction(function () use ($posts, $failedOnly, &$jobs, &$queued, &$skipped) {
            foreach ($posts as $post) {
                $existing = SocialPostDeletionJob::query()
                    ->where('facebook_page_id', $post->facebook_page_id)
                    ->where('platform', $post->platform)
                    ->where('external_post_id', $post->external_post_id)
                    ->lockForUpdate()
                    ->first();

                if ($existing && in_array($existing->status, [SocialPostDeletionJob::STATUS_PENDING, SocialPostDeletionJob::STATUS_PROCESSING], true)) {
                    $jobs[] = $existing;
                    $skipped++;
                    continue;
                }

                if ($existing && $existing->status === SocialPostDeletionJob::STATUS_COMPLETED) {
                    $jobs[] = $existing;
                    $skipped++;
                    continue;
                }

                if ($failedOnly && (!$existing || $existing->status !== SocialPostDeletionJob::STATUS_FAILED)) {
                    $skipped++;
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

                $payload = [
                    'user_id' => Auth::id(),
                    'facebook_page_id' => $post->facebook_page_id,
                    'synced_social_post_id' => $post->id,
                    'platform' => $post->platform,
                    'external_post_id' => $post->external_post_id,
                    'post_created_at' => $post->external_created_at,
                    'content_preview' => $post->content,
                    'media_preview_url' => $post->media_preview_url,
                    'status' => SocialPostDeletionJob::STATUS_PENDING,
                    'error_message' => null,
                    'attempts_count' => 0,
                    'scheduled_for' => $nextRunAt,
                    'processed_at' => null,
                    'meta' => [
                        'synced_social_post_id' => $post->id,
                        'retry' => (bool) $existing,
                    ],
                ];

                if ($existing) {
                    $existing->update($payload);
                    $job = $existing->fresh();
                } else {
                    $job = SocialPostDeletionJob::create($payload);
                }

                ProcessSocialPostDeletionJob::dispatch($job->id)
                    ->onQueue('social-deletions')
                    ->delay($nextRunAt);

                $jobs[] = $job;
                $queued++;
            }
        });

        return [
            'jobs' => $jobs,
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }
}
