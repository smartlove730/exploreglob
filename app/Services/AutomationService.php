<?php

namespace App\Services;

use App\Jobs\ProcessAutomationQueueItemJob;
use App\Models\AutomationQueueItem;
use App\Models\AutomationRule;
use App\Models\AutomationRunLog;
use App\Models\DriveApiKey;
use App\Models\FacebookPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutomationService
{
    public const MAX_DAILY_POSTS_PER_PAGE = 3;

    public function __construct(private readonly DriveService $driveService)
    {
    }

    public function queueRule(AutomationRule $rule, bool $force = false): array
    {
        return Cache::lock("automation:rule:{$rule->id}:queue", 120)->block(5, function () use ($rule, $force): array {
            $rule = $rule->fresh();
            if (!$rule || $rule->status !== AutomationRule::STATUS_ACTIVE) {
                return ['queued' => 0, 'skipped' => 0, 'failed' => 0];
            }

            if (!$force && $rule->next_run_at && $rule->next_run_at->isFuture()) {
                return ['queued' => 0, 'skipped' => 1, 'failed' => 0];
            }

            $pages = FacebookPage::query()
                ->whereIn('id', $rule->page_ids ?: [])
                ->where('facebook_app_id', $rule->app_id)
                ->where('is_active', true)
                ->get();

            $mediaItems = $this->resolveMediaItems($rule);
            if ($pages->isEmpty() || $mediaItems->isEmpty()) {
                $this->log($rule, null, null, 'skipped', 'No active pages or media were available.');

                return ['queued' => 0, 'skipped' => 1, 'failed' => 0];
            }

            $queued = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($pages as $page) {
                $postedSourceIds = AutomationQueueItem::query()
                    ->where('automation_rule_id', $rule->id)
                    ->where('page_id', $page->id)
                    ->pluck('source_id')
                    ->toArray();

                $availableMedia = $mediaItems->reject(fn ($media) => in_array($media['source_id'], $postedSourceIds))->values();

                $availableSlots = $this->getAvailableScheduleTimes($rule, $page, max(1, count($availableMedia)));
                $remainingSlots = count($availableSlots);
                
                if ($remainingSlots <= 0 || $availableMedia->isEmpty()) {
                    $skipped++;
                    $this->log($rule, null, $page->id, 'skipped', 'No available schedule slots or media for this page right now.');
                    continue;
                }

                $slotIndex = 0;
                foreach ($availableMedia->take($remainingSlots) as $media) {
                    try {
                        $scheduledFor = $availableSlots[$slotIndex++];
                        $caption = $this->buildCaption($rule, $media);

                        $item = AutomationQueueItem::query()->firstOrCreate(
                            [
                                'automation_rule_id' => $rule->id,
                                'page_id' => $page->id,
                                'source_id' => $media['source_id'],
                            ],
                            [
                                'user_id' => $rule->user_id,
                                'media_type' => $media['media_type'],
                                'media_url' => $media['media_url'],
                                'caption' => $caption,
                                'platforms' => $rule->platforms,
                                'status' => AutomationQueueItem::STATUS_QUEUED,
                                'scheduled_for' => $scheduledFor,
                            ]
                        );

                        if (!$item->wasRecentlyCreated) {
                            $skipped++;
                            continue;
                        }

                        ProcessAutomationQueueItemJob::dispatch($item->id)->delay($scheduledFor);
                        $queued++;
                        $this->log($rule, $item->id, $page->id, 'queued', 'Automation post queued.', ['scheduled_for' => $scheduledFor->toDateTimeString()]);
                    } catch (\Throwable $exception) {
                        $failed++;
                        $this->log($rule, null, $page->id, 'failed', $exception->getMessage());
                    }
                }
            }

            $rule->forceFill([
                'queued_count' => $rule->queued_count + $queued,
                'last_run_at' => now(),
                'next_run_at' => $this->nextRuleRunAt($rule),
            ])->save();

            return compact('queued', 'skipped', 'failed');
        });
    }

    public function dispatchDueRules(): int
    {
        $count = 0;

        AutomationRule::query()
            ->where('status', AutomationRule::STATUS_ACTIVE)
            ->chunkById(50, function (Collection $rules) use (&$count): void {
                foreach ($rules as $rule) {
                    $result = $this->queueRule($rule, true);
                    $count += $result['queued'];
                }
            });

        return $count;
    }

    public function getAvailableScheduleTimes(AutomationRule $rule, FacebookPage $page, int $limit): array
    {
        $timezone = $rule->timezone ?: config('app.timezone', 'UTC');
        $times = collect($rule->schedule_times ?: [now($timezone)->format('H:i')])->filter()->values();
        if ($times->isEmpty()) {
            return [];
        }

        $existingDates = AutomationQueueItem::query()
            ->where('automation_rule_id', $rule->id)
            ->where('page_id', $page->id)
            ->whereIn('status', [
                AutomationQueueItem::STATUS_QUEUED,
                AutomationQueueItem::STATUS_PROCESSING,
                AutomationQueueItem::STATUS_PUBLISHED,
            ])
            ->where('scheduled_for', '>=', now()->startOfDay())
            ->pluck('scheduled_for')
            ->map(fn($date) => Carbon::parse($date)->timezone($timezone)->format('Y-m-d H:i'))
            ->toArray();

        $usedPerDay = [];
        foreach ($existingDates as $ed) {
            $date = substr($ed, 0, 10);
            $usedPerDay[$date] = ($usedPerDay[$date] ?? 0) + 1;
        }

        $availableSlots = [];
        $candidateDay = now($timezone)->startOfDay();
        $dailyLimit = min(self::MAX_DAILY_POSTS_PER_PAGE, max(1, (int)$rule->daily_limit));

        while (count($availableSlots) < $limit) {
            $dateStr = $candidateDay->format('Y-m-d');
            $usedToday = $usedPerDay[$dateStr] ?? 0;

            if ($usedToday < $dailyLimit) {
                foreach ($times as $timeStr) {
                    [$hour, $minute] = array_map('intval', explode(':', $timeStr) + [0, 0]);
                    $slot = $candidateDay->copy()->setTime($hour, $minute);

                    if ($slot->isFuture()) {
                        $slotStr = $slot->format('Y-m-d H:i');
                        if (!in_array($slotStr, $existingDates)) {
                            $availableSlots[] = $slot->timezone(config('app.timezone', 'UTC'));
                            $usedToday++;
                            $existingDates[] = $slotStr;

                            if ($usedToday >= $dailyLimit || count($availableSlots) >= $limit) {
                                break;
                            }
                        }
                    }
                }
            }

            $candidateDay->addDay();
            if ($candidateDay->diffInDays(now($timezone)) > 365) {
                break;
            }
        }

        return $availableSlots;
    }

    private function resolveMediaItems(AutomationRule $rule): Collection
    {
        $payload = $rule->media_source_payload ?: [];

        if ($rule->media_source_type === 'urls') {
            return collect($payload['urls'] ?? [])
                ->map(fn (string $url) => [
                    'source_id' => sha1($url),
                    'media_url' => $url,
                    'media_type' => $this->guessMediaType($url),
                ])
                ->filter(fn (array $item) => $item['media_url'] !== '')
                ->values();
        }

        if ($rule->media_source_type === 'drive') {
            $driveApiKey = DriveApiKey::query()
                ->whereKey((int) ($payload['drive_api_key_id'] ?? 0))
                ->where('is_active', true)
                ->first();

            if (!$driveApiKey) {
                return collect();
            }

            $drivePayload = $this->driveService->fetchMediaFromDriveLink((string) ($payload['drive_link'] ?? ''), $driveApiKey);

            return collect($drivePayload['media'] ?? [])
                ->map(fn (array $item) => [
                    'source_id' => (string) ($item['id'] ?? sha1((string) ($item['download_url'] ?? $item['preview_url'] ?? Str::uuid()))),
                    'media_url' => (string) ($item['download_url'] ?? $item['preview_url'] ?? ''),
                    'media_type' => (string) ($item['type'] ?? $this->guessMediaType((string) ($item['download_url'] ?? ''))),
                ])
                ->filter(fn (array $item) => $item['media_url'] !== '')
                ->values();
        }

        return collect();
    }

    private function buildCaption(AutomationRule $rule, array $media): string
    {
        $captions = collect($rule->caption_templates ?: [''])->filter()->values();
        $hashtags = collect($rule->hashtag_templates ?: [])->filter()->values();

        $caption = (string) ($captions->get(abs(crc32((string) $media['source_id'])) % max(1, $captions->count())) ?? '');
        $hashtag = (string) ($hashtags->get(abs(crc32((string) $media['media_url'])) % max(1, $hashtags->count())) ?? '');

        return trim($caption."\n\n".$hashtag);
    }



    private function nextRuleRunAt(AutomationRule $rule): Carbon
    {
        $frequency = max(1, (int) $rule->post_frequency);
        $minutes = (int) floor(1440 / $frequency);

        return now()->addMinutes(max(15, $minutes));
    }

    private function guessMediaType(string $url): string
    {
        return preg_match('/\.(mp4|mov|m4v)(\?|$)/i', $url) ? 'video' : 'image';
    }

    private function log(AutomationRule $rule, ?int $itemId, ?int $pageId, string $status, string $message, array $context = []): void
    {
        AutomationRunLog::create([
            'automation_rule_id' => $rule->id,
            'automation_queue_item_id' => $itemId,
            'page_id' => $pageId,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
