<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Services\FacebookGraphService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishFacebookPostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $facebookPostId)
    {
    }

    public function handle(FacebookGraphService $facebookGraphService): void
    {
        $post = FacebookPost::with('page')->find($this->facebookPostId);

        if (!$post || !$post->page) {
            return;
        }

        try {
            $response = $facebookGraphService->publishToPage(
                $post->page,
                $post->message,
                $post->image_url
            );

            $post->update([
                'status' => FacebookPost::STATUS_POSTED,
                'posted_at' => now(),
                'attempts' => $post->attempts + 1,
                'response_json' => $response,
            ]);
        } catch (Throwable $exception) {
            Log::error('Facebook post failed', [
                'facebook_post_id' => $post->id,
                'error' => $exception->getMessage(),
            ]);

            $post->update([
                'status' => FacebookPost::STATUS_FAILED,
                'attempts' => $post->attempts + 1,
                'response_json' => ['error' => $exception->getMessage()],
            ]);

            throw $exception;
        }
    }
}
