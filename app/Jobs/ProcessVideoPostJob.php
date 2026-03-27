<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Services\MetaVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessVideoPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $facebookPostId)
    {
    }

    public function handle(MetaVideoService $metaVideoService): void
    {
        $post = FacebookPost::with('page')->find($this->facebookPostId);

        if (!$post || !$post->page || $post->media_type !== FacebookPost::MEDIA_TYPE_VIDEO || !$post->video_url) {
            return;
        }

        $platforms = collect($post->platforms ?: ['facebook'])
            ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram'], true))
            ->values()
            ->all();

        if (empty($platforms)) {
            $platforms = ['facebook'];
        }

        $responses = [];

        try {
            foreach ($platforms as $platform) {
                if ($platform === 'facebook') {
                    $responses['facebook'] = $metaVideoService->postToFacebookVideo($post->page, $post->video_url, $post->message);
                    continue;
                }

                $responses['instagram'] = $metaVideoService->postToInstagramVideo($post->page, $post->video_url, $post->message);
            }

            $post->update([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => now(),
                'attempts' => $post->attempts + 1,
                'facebook_post_id' => data_get($responses, 'facebook.id') ?: data_get($responses, 'facebook.post_id'),
                'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
                'response_json' => $responses,
            ]);
        } catch (Throwable $exception) {
            Log::error('Video social post failed', [
                'facebook_post_id' => $post->id,
                'platforms' => $platforms,
                'responses' => $responses,
                'error' => $exception->getMessage(),
            ]);

            $post->update([
                'status' => FacebookPost::STATUS_FAILED,
                'attempts' => $post->attempts + 1,
                'response_json' => [
                    'partial_responses' => $responses,
                    'error' => $exception->getMessage(),
                ],
            ]);

            throw $exception;
        }
    }
}
