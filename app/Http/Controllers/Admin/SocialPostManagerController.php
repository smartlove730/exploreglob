<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSocialPostDeletionJob;
use App\Models\FacebookPage;
use App\Models\SocialPostDeletionJob;
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

    public function fetchPosts(Request $request): JsonResponse
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

        $posts = collect();
        $errors = [];

        foreach ($pages as $page) {
            try {
                $posts = $posts->merge($this->metaPostService->fetchFacebookPagePosts($page));
            } catch (\Throwable $exception) {
                $errors[] = "{$page->page_name} (Facebook): {$exception->getMessage()}";
            }

            try {
                $posts = $posts->merge($this->metaPostService->fetchInstagramPosts($page));
            } catch (\Throwable $exception) {
                $errors[] = "{$page->page_name} (Instagram): {$exception->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'posts' => $posts->sortByDesc('created_time')->values()->all(),
            'errors' => $errors,
        ]);
    }

    public function deletePosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'posts' => ['required', 'array', 'min:1'],
            'posts.*.platform' => ['required', 'in:facebook,instagram'],
            'posts.*.external_post_id' => ['required', 'string'],
            'posts.*.page_id' => ['required', 'integer'],
            'posts.*.content' => ['nullable', 'string'],
            'posts.*.media_preview_url' => ['nullable', 'string'],
            'posts.*.created_time' => ['nullable', 'string'],
        ]);

        $jobs = [];

        DB::transaction(function () use (&$jobs, $data) {
            foreach ($data['posts'] as $postPayload) {
                $page = FacebookPage::query()
                    ->ownedBy(Auth::user())
                    ->whereKey((int) $postPayload['page_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$page) {
                    continue;
                }

                $existing = SocialPostDeletionJob::query()
                    ->where('facebook_page_id', $page->id)
                    ->where('platform', $postPayload['platform'])
                    ->where('external_post_id', $postPayload['external_post_id'])
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

                $base = $latestScheduledAt ? Carbon::parse($latestScheduledAt) : now();
                $nextRunAt = $base->greaterThan(now()) ? $base->addMinutes(2) : now()->addMinutes(2);

                $job = SocialPostDeletionJob::create([
                    'user_id' => Auth::id(),
                    'facebook_page_id' => $page->id,
                    'platform' => $postPayload['platform'],
                    'external_post_id' => $postPayload['external_post_id'],
                    'post_created_at' => $postPayload['created_time'] ?? null,
                    'content_preview' => $postPayload['content'] ?? null,
                    'media_preview_url' => $postPayload['media_preview_url'] ?? null,
                    'status' => SocialPostDeletionJob::STATUS_PENDING,
                    'scheduled_for' => $nextRunAt,
                    'meta' => $postPayload,
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
                'platform' => $job->platform,
                'external_post_id' => $job->external_post_id,
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
                'status' => $job->status,
                'error_message' => $job->error_message,
                'platform' => $job->platform,
                'external_post_id' => $job->external_post_id,
                'scheduled_for' => optional($job->scheduled_for)->toDateTimeString(),
                'processed_at' => optional($job->processed_at)->toDateTimeString(),
            ])->values(),
        ]);
    }
}
