<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Services\FacebookGraphService;
use App\Services\InstagramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PublishFacebookPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $facebookPostId)
    {
    }

    public function handle(FacebookGraphService $facebookGraphService, InstagramService $instagramService): void
    {
        $post = FacebookPost::with('page')->find($this->facebookPostId);

        if (!$post || !$post->page) {
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
                    $responses['facebook'] = $facebookGraphService->publishToPage(
                        $post->page,
                        $post->message,
                        $post->image_url
                    );

                    continue;
                }

                if (!$post->image_url) {
                    throw new RuntimeException('Instagram publishing requires an HTTPS image URL.');
                }

                $responses['instagram'] = $instagramService->publishImageWithCaption(
                    $post->page,
                    $post->image_url,
                    $post->message,
                    3
                );
            }

            $post->update([
                'status' => FacebookPost::STATUS_POSTED,
                'posted_at' => now(),
                'attempts' => $post->attempts + 1,
                'response_json' => $responses,
            ]);
        } catch (Throwable $exception) {
            Log::error('Social post failed', [
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
