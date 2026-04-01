<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Services\MetaPostService;
use App\Services\MetaVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishPostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [30, 120, 300, 600];

    public function __construct(public int $facebookPostId)
    {
    }

    public function handle(MetaPostService $metaPostService, MetaVideoService $metaVideoService): void
    {
        $post = FacebookPost::query()->with(['page.facebookAccount'])->find($this->facebookPostId);

        if (!$post || !$post->page) {
            return;
        }

        if ($post->status === FacebookPost::STATUS_PUBLISHED) {
            return;
        }

        $platforms = collect($post->platforms ?: ['facebook'])
            ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram', 'google_business'], true))
            ->values()
            ->all();

        if (empty($platforms)) {
            $platforms = ['facebook'];
        }

        $pendingPlatforms = $this->resolvePendingPlatforms($post, $platforms);

        if (empty($pendingPlatforms)) {
            $post->update([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => $post->posted_at ?: now(),
                'last_error' => null,
            ]);

            return;
        }

        $post->forceFill([
            'status' => FacebookPost::STATUS_PROCESSING,
            'last_error' => null,
        ])->save();

        $responses = is_array($post->response_json) ? $post->response_json : [];

        try {
            if (($post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO) {
                if (in_array('google_business', $pendingPlatforms, true)) {
                    throw new \RuntimeException('Google Business does not support video posting.');
                }

                $videoResult = $this->publishVideo($post, $metaVideoService, $pendingPlatforms);
                $responses = array_merge($responses, $videoResult);
            } else {
                $publishResult = $metaPostService->publish(
                    $post->page,
                    (string) $post->message,
                    $post->image_url,
                    $pendingPlatforms,
                    $post->google_location_id
                );

                $responses = array_merge($responses, (array) ($publishResult['response_json'] ?? []));
                $post->facebook_post_id = $post->facebook_post_id ?: ($publishResult['facebook_post_id'] ?? null);
                $post->instagram_media_id = $post->instagram_media_id ?: ($publishResult['instagram_media_id'] ?? null);
                $post->google_post_name = $post->google_post_name ?: ($publishResult['google_post_name'] ?? null);
            }

            $post->forceFill([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => now(),
                'attempts' => (int) $post->attempts + 1,
                'last_error' => null,
                'response_json' => $responses,
            ])->save();
        } catch (Throwable $exception) {
            Log::error('Queued post publish failed', [
                'facebook_post_id' => $post->id,
                'user_id' => $post->user_id,
                'page_id' => $post->page_id,
                'platforms' => $pendingPlatforms,
                'attempts' => (int) $post->attempts + 1,
                'error' => $exception->getMessage(),
            ]);

            $post->forceFill([
                'status' => FacebookPost::STATUS_FAILED,
                'attempts' => (int) $post->attempts + 1,
                'last_error' => $exception->getMessage(),
                'response_json' => array_merge($responses, ['error' => $exception->getMessage()]),
            ])->save();

            throw $exception;
        }
    }

    private function resolvePendingPlatforms(FacebookPost $post, array $platforms): array
    {
        return collect($platforms)
            ->filter(function (string $platform) use ($post) {
                return match ($platform) {
                    'facebook' => empty($post->facebook_post_id),
                    'instagram' => empty($post->instagram_media_id),
                    'google_business' => empty($post->google_post_name),
                    default => false,
                };
            })
            ->values()
            ->all();
    }

    private function publishVideo(FacebookPost $post, MetaVideoService $metaVideoService, array $platforms): array
    {
        if (!$post->video_url) {
            throw new \RuntimeException('Video post is missing a public video URL.');
        }

        $responses = [];

        foreach ($platforms as $platform) {
            if ($platform === 'google_business') {
                continue;
            }

            if ($platform === 'facebook') {
                $responses['facebook'] = $metaVideoService->postToFacebookVideo($post->page, $post->video_url, $post->message);
                $post->facebook_post_id = $post->facebook_post_id ?: (data_get($responses, 'facebook.id') ?: data_get($responses, 'facebook.post_id'));
                continue;
            }

            $responses['instagram'] = $metaVideoService->postToInstagramVideo($post->page, $post->video_url, $post->message);
            $post->instagram_media_id = $post->instagram_media_id ?: data_get($responses, 'instagram.publish_response.id');
        }

        return $responses;
    }
}
