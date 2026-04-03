<?php

namespace App\Services;

use App\Jobs\RunAutomationJob;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\DriveImagePost;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\PostImage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationService
{
    public function __construct(
        private readonly DriveService $driveService,
        private readonly AiCaptionService $aiCaptionService,
        private readonly MetaPostService $metaPostService,
    ) {
    }

    public function scheduleAutomations(?int $automationConfigId = null, bool $forceRun = false): Collection
    {
        $configs = AutomationConfig::query()
            ->with(['page', 'driveApiKey'])
            ->where('is_active', true)
            ->when($automationConfigId, fn ($query) => $query->whereKey($automationConfigId))
            ->get();

        $nextRunAt = now();
        $scheduledLogs = collect();

        foreach ($configs as $config) {
            $nextRunAt = $nextRunAt->copy()->addMinutes(random_int(10, 120));
            $message = 'Scheduled for '.$nextRunAt->toDateTimeString().'.';

            $log = AutomationPostLog::create([
                'user_id' => $config->user_id,
                'automation_config_id' => $config->id,
                'page_id' => $config->page_id,
                'status' => 'scheduled',
                'message' => $message,
                'scheduled_for' => $nextRunAt,
                'posted_at' => $nextRunAt,
                'caption' => null,
                'platforms' => $this->normalizePlatforms($config->platforms),
                'response_json' => [
                    'automation_name' => $config->name,
                    'drive_link' => $config->drive_link,
                    'prompt' => $config->prompt,
                ],
            ]);

            RunAutomationJob::dispatch($config->id, $forceRun, $log->id)->delay($nextRunAt);
            $scheduledLogs->push($log);
        }

        return $scheduledLogs;
    }

    public function runAllAutomations(?int $automationConfigId = null, bool $forceRun = false, ?int $automationLogId = null): void
    {
        Cache::lock('automation:run-all', 300)->block(5, function () use ($automationConfigId, $forceRun, $automationLogId) {
            $configs = AutomationConfig::query()
                ->with(['page.facebookAccount', 'driveApiKey'])
                ->where('is_active', true)
                ->when($automationConfigId, fn ($query) => $query->whereKey($automationConfigId))
                ->get();

            foreach ($configs as $config) {
                $this->runSingleAutomation($config, $forceRun, $automationLogId);
                sleep(2); // soft rate-limit between external API calls
            }
        });
    }

    private function runSingleAutomation(AutomationConfig $config, bool $forceRun = false, ?int $automationLogId = null): void
    {
        $this->markLogInProgress($automationLogId);

        $blockReason = $this->getRunBlockReason($config, $forceRun);
        if ($blockReason !== null) {
            $this->logSkipped($config, $blockReason, $automationLogId);

            return;
        }

        try {
            if (!$config->driveApiKey || !$config->driveApiKey->is_active) {
                $this->logFailed($config, null, $this->normalizePlatforms($config->platforms), 'No active Google Drive key selected.', $automationLogId);

                return;
            }

            $drivePayload = $this->driveService->fetchImagesFromDriveLink($config->drive_link, $config->driveApiKey);
            $folderId = (string) ($drivePayload['folder_id'] ?? '');
            $images = collect($drivePayload['images'] ?? []);
            $platforms = $this->normalizePlatforms($config->platforms);

            $unusedImage = $this->resolveUnusedImage($config, $images);

            if (!$unusedImage) {
                $this->logSkipped($config, 'No unused images available in Drive folder.', $automationLogId);

                return;
            }

            $imageUrl = (string) ($unusedImage['download_url'] ?? $unusedImage['preview_url'] ?? '');
            if (in_array('instagram', $platforms, true)) {
                $imageUrl = $this->driveService->prepareInstagramEligibleImage($unusedImage, $config->driveApiKey);
            }

            $caption = $this->aiCaptionService->generateCaption($config->prompt, $imageUrl);

            /** @var FacebookPage|null $page */
            $page = $config->page;
            if (!$page || !$page->is_active) {
                $this->logFailed($config, $unusedImage, $platforms, 'Selected page is missing or inactive.', $automationLogId);

                return;
            }

            $result = $this->metaPostService->publish($page, $caption, $imageUrl, $platforms);

            DB::transaction(function () use ($config, $unusedImage, $platforms, $result, $caption, $imageUrl, $folderId, $automationLogId) {
                $facebookPost = FacebookPost::create([
                    'user_id' => $config->user_id,
                    'page_id' => $config->page_id,
                    'message' => $caption,
                    'image_url' => $imageUrl,
                    'platforms' => $platforms,
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $result['facebook_post_id'] ?? null,
                    'instagram_media_id' => $result['instagram_media_id'] ?? null,
                    'response_json' => $result['response_json'] ?? null,
                ]);

                PostImage::create([
                    'user_id' => $config->user_id,
                    'post_id' => $facebookPost->id,
                    'image_path' => $this->resolveImagePathForHistory($imageUrl),
                ]);

                DriveImagePost::create([
                    'user_id' => $config->user_id,
                    'page_id' => $config->page_id,
                    'drive_file_id' => (string) ($unusedImage['id'] ?? ''),
                    'drive_folder_id' => $folderId,
                    'image_url' => $imageUrl,
                    'caption' => $caption,
                    'platforms' => $platforms,
                    'facebook_post_id' => $result['facebook_post_id'] ?? null,
                    'instagram_media_id' => $result['instagram_media_id'] ?? null,
                    'response_json' => $result['response_json'] ?? null,
                    'posted_at' => now(),
                ]);

                $this->logSuccess($config, [
                    'user_id' => $config->user_id,
                    'automation_config_id' => $config->id,
                    'page_id' => $config->page_id,
                    'drive_file_id' => (string) ($unusedImage['id'] ?? ''),
                    'drive_file_name' => (string) ($unusedImage['name'] ?? null),
                    'image_url' => $imageUrl,
                    'caption' => $caption,
                    'platforms' => $platforms,
                    'facebook_post_id' => $result['facebook_post_id'] ?? null,
                    'instagram_media_id' => $result['instagram_media_id'] ?? null,
                    'status' => 'success',
                    'message' => 'Posted successfully',
                    'response_json' => [
                        'meta_response' => $result['response_json'] ?? [],
                        'drive_folder_id' => $folderId,
                    ],
                    'posted_at' => now(),
                ], $automationLogId);

                $config->forceFill(['last_run_at' => now()])->save();
            });
        } catch (Throwable $exception) {
            Log::error('Automation failed', [
                'automation_config_id' => $config->id,
                'error' => $exception->getMessage(),
            ]);

            $this->logFailed($config, null, $this->normalizePlatforms($config->platforms), $exception->getMessage(), $automationLogId);
        }
    }

    private function resolveImagePathForHistory(string $imageUrl): string
    {
        $path = (string) parse_url($imageUrl, PHP_URL_PATH);
        $storagePrefix = '/storage/';

        if ($path !== '' && str_contains($path, $storagePrefix)) {
            return ltrim(substr($path, strpos($path, $storagePrefix) + strlen($storagePrefix)), '/');
        }

        return $imageUrl;
    }

    private function getRunBlockReason(AutomationConfig $config, bool $forceRun = false): ?string
    {
        if ($forceRun) {
            return null;
        }

        $todayCount = AutomationPostLog::query()
            ->where('automation_config_id', $config->id)
            ->where('status', 'success')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= max(1, (int) $config->post_limit_per_day)) {
            return "Skipped: daily post limit reached ({$todayCount}/{$config->post_limit_per_day}).";
        }

        if (!$config->last_run_at) {
            return null;
        }

        $runsPerDay = max(1, (int) $config->runs_per_day);
        $minIntervalMinutes = (int) floor(1440 / $runsPerDay);
        $nextRunAt = $config->last_run_at->copy()->addMinutes(max(1, $minIntervalMinutes));

        if ($nextRunAt->isFuture()) {
            return 'Skipped: next run available at '.$nextRunAt->toDateTimeString().'.';
        }

        return null;
    }

    private function resolveUnusedImage(AutomationConfig $config, Collection $images): ?array
    {
        $usedIds = AutomationPostLog::query()
            ->where('automation_config_id', $config->id)
            ->whereNotNull('drive_file_id')
            ->pluck('drive_file_id')
            ->all();

        return $images
            ->filter(fn ($image) => !in_array((string) ($image['id'] ?? ''), $usedIds, true))
            ->values()
            ->first();
    }

    private function normalizePlatforms(string $platform): array
    {
        return match ($platform) {
            'facebook' => ['facebook'],
            'instagram' => ['instagram'],
            default => ['facebook', 'instagram'],
        };
    }

    private function logSkipped(AutomationConfig $config, string $message, ?int $automationLogId = null): void
    {
        $this->upsertLog($automationLogId, [
            'user_id' => $config->user_id,
            'automation_config_id' => $config->id,
            'page_id' => $config->page_id,
            'status' => 'skipped',
            'message' => $message,
            'posted_at' => Carbon::now(),
        ]);
    }

    private function logFailed(AutomationConfig $config, ?array $image, array $platforms, string $message, ?int $automationLogId = null): void
    {
        $this->upsertLog($automationLogId, [
            'user_id' => $config->user_id,
            'automation_config_id' => $config->id,
            'page_id' => $config->page_id,
            'drive_file_id' => (string) ($image['id'] ?? ''),
            'drive_file_name' => (string) ($image['name'] ?? null),
            'image_url' => (string) ($image['download_url'] ?? $image['preview_url'] ?? ''),
            'platforms' => $platforms,
            'status' => 'failed',
            'message' => $message,
            'posted_at' => now(),
        ]);
    }

    private function logSuccess(AutomationConfig $config, array $payload, ?int $automationLogId = null): void
    {
        $this->upsertLog($automationLogId, $payload + [
            'user_id' => $config->user_id,
            'automation_config_id' => $config->id,
            'page_id' => $config->page_id,
        ]);
    }

    private function markLogInProgress(?int $automationLogId): void
    {
        if (!$automationLogId) {
            return;
        }

        AutomationPostLog::query()
            ->whereKey($automationLogId)
            ->update([
                'status' => 'in_progress',
                'message' => 'Automation started.',
                'started_at' => now(),
            ]);
    }

    private function upsertLog(?int $automationLogId, array $payload): void
    {
        $payload['completed_at'] = now();

        if (!$automationLogId) {
            AutomationPostLog::create($payload);

            return;
        }

        AutomationPostLog::query()
            ->whereKey($automationLogId)
            ->update($payload);
    }
}
