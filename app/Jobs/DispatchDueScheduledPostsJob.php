<?php

namespace App\Jobs;

use App\Models\ScheduledPost;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchDueScheduledPostsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        ScheduledPost::query()
            ->where('status', ScheduledPost::STATUS_PENDING)
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get()
            ->each(function (ScheduledPost $scheduledPost): void {
                $updated = ScheduledPost::query()
                    ->whereKey($scheduledPost->id)
                    ->where('status', ScheduledPost::STATUS_PENDING)
                    ->update([
                        'status' => ScheduledPost::STATUS_PROCESSING,
                        'last_error' => null,
                    ]);

                if ($updated === 1) {
                    PublishScheduledPostJob::dispatch($scheduledPost->id);
                }
            });
    }
}
