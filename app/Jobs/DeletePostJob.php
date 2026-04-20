<?php

namespace App\Jobs;

use App\Models\FacebookPost;
use App\Services\MetaPostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeletePostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [30, 120, 300];

    public int $timeout = 120;

    public function __construct(public int $facebookPostId)
    {
    }

    public function handle(MetaPostService $metaPostService): void
    {
        $post = FacebookPost::query()->with('page.facebookAccount')->find($this->facebookPostId);

        if (!$post || !$post->page) {
            return;
        }

        if ($post->deletion_status === FacebookPost::DELETION_STATUS_SUCCESS || $post->deleted_at !== null) {
            return;
        }

        $post->forceFill([
            'deletion_status' => FacebookPost::DELETION_STATUS_PENDING,
            'last_error' => null,
        ])->save();

        $result = [
            'facebook' => $this->deleteFacebook($metaPostService, $post),
            'instagram' => $this->deleteInstagram($metaPostService, $post),
        ];

        $responses = is_array($post->response_json) ? $post->response_json : [];
        $responses['deletion'] = $result;

        $hasFailure = collect($result)->contains(fn (array $platformResult) => $platformResult['status'] === 'failed');

        if ($hasFailure) {
            $post->forceFill([
                'deletion_status' => FacebookPost::DELETION_STATUS_FAILED,
                'last_error' => collect($result)
                    ->pluck('error')
                    ->filter()
                    ->implode(' | ') ?: 'One or more delete requests failed.',
                'response_json' => $responses,
            ])->save();

            throw new \RuntimeException('Post deletion failed for one or more platforms.');
        }

        $post->forceFill([
            'deletion_status' => FacebookPost::DELETION_STATUS_SUCCESS,
            'deleted_at' => now(),
            'last_error' => null,
            'response_json' => $responses,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $post = FacebookPost::query()->find($this->facebookPostId);

        if (!$post || $post->deletion_status === FacebookPost::DELETION_STATUS_SUCCESS) {
            return;
        }

        $post->forceFill([
            'deletion_status' => FacebookPost::DELETION_STATUS_FAILED,
            'last_error' => $exception->getMessage(),
        ])->save();

        Log::error('Queued post deletion failed', [
            'facebook_post_id' => $this->facebookPostId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function deleteFacebook(MetaPostService $metaPostService, FacebookPost $post): array
    {
        if (!$post->facebook_post_id) {
            return ['status' => 'skipped', 'reason' => 'missing_facebook_post_id'];
        }

        try {
            $metaPostService->deleteFacebookPost($post->page, $post->facebook_post_id);

            return ['status' => 'success'];
        } catch (Throwable $exception) {
            if ($this->isMissingObjectError($exception)) {
                return ['status' => 'success', 'reason' => 'already_deleted'];
            }

            return ['status' => 'failed', 'error' => $exception->getMessage()];
        }
    }

    private function deleteInstagram(MetaPostService $metaPostService, FacebookPost $post): array
    {
        if (!$post->instagram_media_id) {
            return ['status' => 'skipped', 'reason' => 'missing_instagram_media_id'];
        }

        try {
            $metaPostService->deleteInstagramMedia($post->page, $post->instagram_media_id);

            return ['status' => 'success'];
        } catch (Throwable $exception) {
            if ($this->isMissingObjectError($exception)) {
                return ['status' => 'success', 'reason' => 'already_deleted'];
            }

            return ['status' => 'failed', 'error' => $exception->getMessage()];
        }
    }

    private function isMissingObjectError(Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'does not exist')
            || str_contains($message, 'unknown object')
            || str_contains($message, 'cannot be loaded due to missing permissions')
            || str_contains($message, 'unsupported delete request');
    }
}
