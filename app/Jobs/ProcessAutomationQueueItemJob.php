<?php

namespace App\Jobs;

use App\Models\AutomationQueueItem;
use App\Models\AutomationRule;
use App\Models\AutomationRunLog;
use App\Models\FacebookPost;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessAutomationQueueItemJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 180, 300];

    public function __construct(public int $queueItemId)
    {
    }

    public function handle(): void
    {
        $item = AutomationQueueItem::query()->with(['rule', 'page'])->find($this->queueItemId);
        if (!$item || !$item->rule || !$item->page) {
            return;
        }

        Cache::lock('automation:daily:page:'.$item->page_id.':'.now()->toDateString(), 60)->block(5, function () use ($item): void {
            DB::transaction(function () use ($item): void {
                $item = AutomationQueueItem::query()->lockForUpdate()->with(['rule', 'page'])->find($item->id);
                if (!$item || $item->status !== AutomationQueueItem::STATUS_QUEUED) {
                    return;
                }

                if ($item->rule->status !== AutomationRule::STATUS_ACTIVE) {
                    $item->update([
                        'status' => AutomationQueueItem::STATUS_CANCELLED,
                        'completed_at' => now(),
                        'last_error' => 'Automation is paused or stopped.',
                    ]);
                    $this->log($item, 'cancelled', 'Queue item cancelled because automation is not active.');

                    return;
                }

                $dailyLimit = min(3, max(1, (int) $item->rule->daily_limit));
                $usedToday = AutomationQueueItem::query()
                    ->where('page_id', $item->page_id)
                    ->whereIn('status', [AutomationQueueItem::STATUS_PROCESSING, AutomationQueueItem::STATUS_PUBLISHED])
                    ->whereDate('scheduled_for', now()->toDateString())
                    ->count();

                if ($usedToday >= $dailyLimit) {
                    $item->update([
                        'status' => AutomationQueueItem::STATUS_SKIPPED,
                        'completed_at' => now(),
                        'last_error' => "Daily page limit reached ({$dailyLimit}/{$dailyLimit}).",
                    ]);
                    $this->log($item, 'skipped', 'Daily limit reached before processing.');

                    return;
                }

                $post = FacebookPost::create([
                    'user_id' => $item->user_id,
                    'page_id' => $item->page_id,
                    'message' => $item->caption,
                    'media_type' => $item->media_type,
                    'image_url' => $item->media_type === FacebookPost::MEDIA_TYPE_IMAGE ? $item->media_url : null,
                    'video_url' => $item->media_type === FacebookPost::MEDIA_TYPE_VIDEO ? $item->media_url : null,
                    'platforms' => $item->platforms ?: ['facebook'],
                    'status' => FacebookPost::STATUS_PENDING,
                    'scheduled_at' => now(),
                    'response_json' => ['automation_queue_item_id' => $item->id],
                ]);

                $item->update([
                    'facebook_post_id' => $post->id,
                    'status' => AutomationQueueItem::STATUS_PROCESSING,
                    'started_at' => now(),
                    'attempts' => $item->attempts + 1,
                    'last_error' => null,
                ]);

                PublishPostJob::dispatch($post->id);
                $this->log($item, 'processing', 'Post handed to the social publishing queue.', ['facebook_post_id' => $post->id]);
            });
        });
    }

    public function failed(\Throwable $exception): void
    {
        $item = AutomationQueueItem::query()->with('rule')->find($this->queueItemId);
        if (!$item) {
            return;
        }

        $item->update([
            'status' => AutomationQueueItem::STATUS_FAILED,
            'last_error' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
        $item->rule?->increment('failed_count');
        $this->log($item, 'failed', $exception->getMessage());
    }

    private function log(AutomationQueueItem $item, string $status, string $message, array $context = []): void
    {
        AutomationRunLog::create([
            'automation_rule_id' => $item->automation_rule_id,
            'automation_queue_item_id' => $item->id,
            'page_id' => $item->page_id,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
