<?php

namespace App\Jobs;

use App\Models\SocialPostDeletionJob;
use App\Services\MetaPostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessSocialPostDeletionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [120, 240, 480, 960];

    public int $timeout = 120;

    public function __construct(public int $deletionJobId)
    {
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('social-post-deletions'))->expireAfter(600)];
    }

    public function handle(MetaPostService $metaPostService): void
    {
        $job = SocialPostDeletionJob::query()->with('page.facebookAccount')->find($this->deletionJobId);

        if (!$job || !$job->page) {
            return;
        }

        if ($job->status === SocialPostDeletionJob::STATUS_COMPLETED) {
            return;
        }

        $job->forceFill([
            'status' => SocialPostDeletionJob::STATUS_PROCESSING,
            'attempts_count' => $job->attempts_count + 1,
            'error_message' => null,
        ])->save();

        try {
            if ($job->platform === 'facebook') {
                $metaPostService->deleteFacebookPost($job->page, $job->external_post_id);
            }

            if ($job->platform === 'instagram') {
                $metaPostService->deleteInstagramMedia($job->page, $job->external_post_id);
            }
        } catch (Throwable $exception) {
            if ($this->isMissingObjectError($exception)) {
                $job->forceFill([
                    'status' => SocialPostDeletionJob::STATUS_COMPLETED,
                    'processed_at' => now(),
                    'error_message' => null,
                ])->save();

                return;
            }

            throw $exception;
        }

        $job->forceFill([
            'status' => SocialPostDeletionJob::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        $job = SocialPostDeletionJob::query()->find($this->deletionJobId);

        if (!$job) {
            return;
        }

        $job->forceFill([
            'status' => SocialPostDeletionJob::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ])->save();
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
