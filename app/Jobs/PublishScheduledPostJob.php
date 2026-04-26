<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Models\ScheduledPost;
use App\Services\MetaPostService;
use App\Services\MetaVideoService;
use App\Services\PlanEnforcementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishScheduledPostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [30, 120, 300, 600];

    public function __construct(public int $scheduledPostId)
    {
    }

    public function handle(
        MetaPostService $metaPostService,
        MetaVideoService $metaVideoService,
        PlanEnforcementService $planEnforcementService,
    ): void {
        $scheduledPost = ScheduledPost::query()->with(['user', 'page.facebookAccount'])->find($this->scheduledPostId);

        if (!$scheduledPost || !$scheduledPost->user || !$scheduledPost->page) {
            return;
        }

        if ($scheduledPost->status === ScheduledPost::STATUS_PUBLISHED || $scheduledPost->status === ScheduledPost::STATUS_CANCELLED) {
            return;
        }

        if ((int) $scheduledPost->page->user_id !== (int) $scheduledPost->user_id
            || (int) data_get($scheduledPost, 'page.facebookAccount.user_id') !== (int) $scheduledPost->user_id) {
            $this->markFailed($scheduledPost, 'Scheduled post page ownership is invalid.');
            return;
        }

        $platforms = collect($scheduledPost->platforms)
            ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram'], true))
            ->values()
            ->all();

        if (empty($platforms)) {
            $platforms = ['facebook'];
        }

        try {
            $planEnforcementService->assertCanPost($scheduledPost->user, $platforms);
            $responses = [];

            if (($scheduledPost->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO) {
                foreach ($platforms as $platform) {
                    if ($platform === 'facebook') {
                        $responses['facebook'] = $metaVideoService->postToFacebookVideo(
                            $scheduledPost->page,
                            (string) $scheduledPost->video_url,
                            $scheduledPost->message
                        );
                        continue;
                    }

                    $responses['instagram'] = $metaVideoService->postToInstagramVideo(
                        $scheduledPost->page,
                        (string) $scheduledPost->video_url,
                        $scheduledPost->message
                    );
                }

                $result = [
                    'facebook_post_id' => data_get($responses, 'facebook.id') ?: data_get($responses, 'facebook.post_id'),
                    'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
                    'response_json' => $responses,
                ];
            } else {
                $result = $metaPostService->publish(
                    $scheduledPost->page,
                    $scheduledPost->message,
                    $scheduledPost->image_url,
                    $platforms
                );
            }

            $planEnforcementService->consumeQuota($scheduledPost->user);

            FacebookPost::create([
                'user_id' => $scheduledPost->user_id,
                'page_id' => $scheduledPost->page_id,
                'message' => $scheduledPost->message,
                'media_type' => $scheduledPost->media_type,
                'image_url' => $scheduledPost->image_url,
                'video_path' => $scheduledPost->video_path,
                'video_url' => $scheduledPost->video_url,
                'platforms' => $platforms,
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => now(),
                'facebook_post_id' => $result['facebook_post_id'] ?? null,
                'instagram_media_id' => $result['instagram_media_id'] ?? null,
                'response_json' => $result['response_json'] ?? null,
                'last_error' => null,
            ]);

            $scheduledPost->update([
                'status' => ScheduledPost::STATUS_PUBLISHED,
                'published_at' => now(),
                'last_error' => null,
                'attempts' => (int) $scheduledPost->attempts + 1,
                'response_json' => $result['response_json'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Log::error('Scheduled post publish failed', [
                'scheduled_post_id' => $scheduledPost->id,
                'user_id' => $scheduledPost->user_id,
                'page_id' => $scheduledPost->page_id,
                'attempts' => (int) $scheduledPost->attempts + 1,
                'error' => $exception->getMessage(),
            ]);

            $this->markFailed($scheduledPost, $exception->getMessage());
            throw $exception;
        }
    }

    private function markFailed(ScheduledPost $scheduledPost, string $message): void
    {
        $scheduledPost->update([
            'status' => ScheduledPost::STATUS_FAILED,
            'attempts' => (int) $scheduledPost->attempts + 1,
            'last_error' => $message,
            'response_json' => array_merge((array) $scheduledPost->response_json, ['error' => $message]),
        ]);
    }
}
