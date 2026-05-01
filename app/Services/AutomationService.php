<?php

namespace App\Services;

use App\Jobs\RunAutomationJob;
use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\DriveImagePost;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\PostedMedia;
use App\Models\PostImage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AutomationService
{
    private const STALE_IN_PROGRESS_MINUTES = 20;

    public function __construct(
        private readonly DriveService $driveService,
        private readonly GoogleDriveService $googleDriveService,
        private readonly GoogleService $googleService,
        private readonly MediaProcessingService $mediaProcessingService,
        private readonly AiCaptionService $aiCaptionService,
        private readonly MetaPostService $metaPostService,
        private readonly MetaVideoService $metaVideoService,
    ) {
    }

    public function scheduleAutomations(?int $automationConfigId = null, bool $forceRun = false, ?int $userId = null): Collection
    {
        $configs = AutomationConfig::query()
            ->with(['page', 'driveApiKey'])
            ->where('is_active', true)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($automationConfigId, fn ($query) => $query->whereKey($automationConfigId))
            ->get();

        $nextRunAt = now();
        $scheduledLogs = collect();

        foreach ($configs as $config) {
            Cache::lock('automation-post-lock-'.$config->id, 60)->get(function () use ($config, $forceRun, &$nextRunAt, $scheduledLogs): void {
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
            });
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

    public function markStaleInProgressLogsAsFailed(): int
    {
        $staleCutoff = now()->subMinutes(self::STALE_IN_PROGRESS_MINUTES);

        return AutomationPostLog::query()
            ->where('status', 'in_progress')
            ->whereNull('completed_at')
            ->where(function ($query) use ($staleCutoff) {
                $query->whereNotNull('started_at')->where('started_at', '<', $staleCutoff)
                    ->orWhere(function ($subQuery) use ($staleCutoff) {
                        $subQuery->whereNull('started_at')->where('updated_at', '<', $staleCutoff);
                    });
            })
            ->update([
                'status' => 'failed',
                'message' => 'Automation timed out before completion.',
                'completed_at' => now(),
            ]);
    }

    private function runSingleAutomation(AutomationConfig $config, bool $forceRun = false, ?int $automationLogId = null): void
    {
        Cache::lock("automation:config:{$config->id}", 1200)->block(3, function () use ($config, $forceRun, $automationLogId): void {
            $this->markLogInProgress($automationLogId);

            $blockReason = $this->getRunBlockReason($config, $forceRun);
            if ($blockReason !== null) {
                $this->logSkipped($config, $blockReason, $automationLogId);

                return;
            }

            try {
            if (!$config->driveApiKey || !$config->driveApiKey->is_active) {
                $this->logFailed($config, null, $this->normalizePlatforms($config->platforms), 'No active Google account connection selected.', $automationLogId);

                return;
            }

            $drivePayload = $this->driveService->fetchMediaFromDriveLink($config->drive_link, $config->driveApiKey);
            $folderId = (string) ($drivePayload['folder_id'] ?? '');
            $mediaItems = collect($drivePayload['media'] ?? []);
            $platforms = $this->normalizePlatforms($config->platforms);

            $mediaCandidates = $this->resolveUnusedMediaCandidates($config, $mediaItems, $platforms);

            if ($mediaCandidates->isEmpty()) {
                $this->logSkipped($config, 'Automation skipped: no eligible media found in the selected Drive folder.', $automationLogId);

                return;
            }

            /** @var FacebookPage|null $page */
            $page = $config->page;
            if (!$page || !$page->is_active) {
                $this->logFailed($config, null, $platforms, 'Selected page is missing or inactive.', $automationLogId);

                return;
            }

            $posted = false;
            $lastError = null;

            foreach ($mediaCandidates as $unusedMedia) {
                $attemptPlatforms = $platforms;
                $driveFileId = (string) ($unusedMedia['id'] ?? '');
                $mediaType = (string) ($unusedMedia['type'] ?? 'image');
                $mediaUrl = (string) ($unusedMedia['download_url'] ?? $unusedMedia['preview_url'] ?? '');
                $isReserved = $this->mediaProcessingService->reserveForProcessing($config->id, $driveFileId, $folderId !== '' ? $folderId : null);

                if (!$isReserved) {
                    continue;
                }

                if (!in_array($mediaType, ['image', 'video'], true)) {
                    $this->mediaProcessingService->markSkipped($driveFileId, 'Unsupported media type.', $attemptPlatforms);

                    continue;
                }

                try {
                    if ($mediaType === 'image' && in_array('instagram', $attemptPlatforms, true)) {
                        $mediaUrl = $this->driveService->prepareInstagramEligibleImage($unusedMedia, $config->driveApiKey);
                    }

                    if ($mediaType === 'video' && in_array('instagram', $attemptPlatforms, true)) {
                        // Re-host Drive videos to a stable public URL before handing off to Instagram processing.
                        $mediaUrl = $this->driveService->prepareInstagramEligibleVideo($unusedMedia, $config->driveApiKey);
                    }

                    $platformReservations = $this->reserveMediaPlatforms($config, $driveFileId, $attemptPlatforms);
                    $attemptPlatforms = $platformReservations['platforms_to_publish'];

                    if (empty($attemptPlatforms)) {
                        $this->mediaProcessingService->markSkipped($driveFileId, 'No unposted platforms available for selected media.', $attemptPlatforms);
                        continue;
                    }

                    $caption = $this->aiCaptionService->generateCaption($config->prompt, $mediaUrl);
                    $result = $mediaType === 'video'
                        ? $this->publishVideoAutomation($page, $caption, $mediaUrl, $attemptPlatforms)
                        : $this->metaPostService->publish($page, $caption, $mediaUrl, $attemptPlatforms);

                    DB::transaction(function () use ($config, $unusedMedia, $attemptPlatforms, $result, $caption, $mediaUrl, $folderId, $automationLogId, $mediaType) {
                $facebookPost = FacebookPost::create([
                    'user_id' => $config->user_id,
                    'page_id' => $config->page_id,
                    'message' => $caption,
                    'media_type' => $mediaType,
                    'image_url' => $mediaType === 'image' ? $mediaUrl : null,
                    'video_url' => $mediaType === 'video' ? $mediaUrl : null,
                    'platforms' => $attemptPlatforms,
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $result['facebook_post_id'] ?? null,
                    'instagram_media_id' => $result['instagram_media_id'] ?? null,
                    'response_json' => $result['response_json'] ?? null,
                ]);

                if ($mediaType === 'image') {
                    PostImage::create([
                        'user_id' => $config->user_id,
                        'post_id' => $facebookPost->id,
                        'image_path' => $this->resolveImagePathForHistory($mediaUrl),
                    ]);
                }

                DriveImagePost::create([
                    'user_id' => $config->user_id,
                    'page_id' => $config->page_id,
                    'drive_file_id' => (string) ($unusedMedia['id'] ?? ''),
                    'drive_folder_id' => $folderId,
                    'image_url' => $mediaUrl,
                    'caption' => $caption,
                    'platforms' => $attemptPlatforms,
                    'facebook_post_id' => $result['facebook_post_id'] ?? null,
                    'instagram_media_id' => $result['instagram_media_id'] ?? null,
                    'response_json' => $result['response_json'] ?? null,
                    'posted_at' => now(),
                ]);

                $this->logSuccess($config, [
                    'user_id' => $config->user_id,
                    'automation_config_id' => $config->id,
                    'page_id' => $config->page_id,
                    'drive_file_id' => (string) ($unusedMedia['id'] ?? ''),
                    'drive_file_name' => (string) ($unusedMedia['name'] ?? null),
                    'image_url' => $mediaUrl,
                    'caption' => $caption,
                    'platforms' => $attemptPlatforms,
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

                    $this->markMediaPlatformsPosted($config, $driveFileId, $attemptPlatforms, $result);
                    $this->mediaProcessingService->markPosted($driveFileId, $attemptPlatforms);
                    $this->moveDriveFileForPostStatus($config, $driveFileId, 'success');
                    $this->cleanupLocalAutomationMedia($mediaUrl);
                    $posted = true;

                    break;
                } catch (Throwable $mediaException) {
                    $lastError = $mediaException->getMessage();
                    if ($this->isOversizedDriveMediaError($lastError)) {
                        $this->mediaProcessingService->markSkipped($driveFileId, $lastError, $attemptPlatforms);

                        Log::warning('Automation media skipped due to oversized Drive file, moving to next file', [
                            'automation_config_id' => $config->id,
                            'drive_file_id' => $driveFileId,
                            'error' => $lastError,
                        ]);

                        continue;
                    }

                    if (!empty($driveFileId) && !empty($attemptPlatforms)) {
                        $this->markMediaPlatformsFailed($config, $driveFileId, $attemptPlatforms, $mediaException->getMessage());
                    }

                    if ($this->isSkippableMediaError($lastError)) {
                        $this->mediaProcessingService->markSkipped($driveFileId, $lastError, $attemptPlatforms);
                    } else {
                        $this->mediaProcessingService->markFailed($driveFileId, $lastError, $attemptPlatforms);
                        $this->moveDriveFileForPostStatus($config, $driveFileId, 'failed');
                    }

                    Log::warning('Automation media skipped due to failure', [
                        'automation_config_id' => $config->id,
                        'drive_file_id' => $driveFileId,
                        'error' => $mediaException->getMessage(),
                    ]);
                }
            }

            if (!$posted) {
                $this->logFailed(
                    $config,
                    null,
                    $this->normalizePlatforms($config->platforms),
                    'Automation failed: no eligible media could be posted from the selected Drive folder.'.($lastError ? ' Last error: '.$lastError : ''),
                    $automationLogId
                );
            }
            } catch (Throwable $exception) {
                if (isset($driveFileId) && isset($platforms) && !empty($driveFileId) && !empty($platforms)) {
                    $this->markMediaPlatformsFailed($config, $driveFileId, $platforms, $exception->getMessage());
                }

            Log::error('Automation failed', [
                'automation_config_id' => $config->id,
                'error' => $exception->getMessage(),
            ]);

            $this->logFailed($config, null, $this->normalizePlatforms($config->platforms), $exception->getMessage(), $automationLogId);
            }
        });
    }

    private function moveDriveFileForPostStatus(AutomationConfig $config, string $driveFileId, string $status): void
    {
        if ($driveFileId === '' || !$config->driveApiKey) {
            return;
        }

        try {
            $driveApiKey = $config->driveApiKey;
            if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
                $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            }

            $this->googleDriveService->moveFileBasedOnStatus(
                $driveFileId,
                $status,
                $driveApiKey->oauth_access_token,
                true
            );
        } catch (Throwable $exception) {
            Log::warning('Automation post status move to Drive folder failed.', [
                'automation_config_id' => $config->id,
                'drive_file_id' => $driveFileId,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
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

    private function cleanupLocalAutomationMedia(string $mediaUrl): void
    {
        $path = $this->resolveImagePathForHistory($mediaUrl);

        if ($path === '' || $path === $mediaUrl) {
            return;
        }

        if (!str_starts_with($path, 'automation/instagram/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
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

    private function resolveUnusedMediaCandidates(AutomationConfig $config, Collection $images, array $platforms): Collection
    {
        return $images
            ->filter(function ($image) use ($config, $platforms) {
                $driveFileId = (string) ($image['id'] ?? '');

                if ($driveFileId === '') {
                    return false;
                }

                if (!$this->mediaProcessingService->shouldProcess($driveFileId)) {
                    return false;
                }

                $postedAutomationPlatforms = PostedMedia::query()
                    ->where('automation_config_id', $config->id)
                    ->where('page_id', $config->page_id)
                    ->where('drive_file_id', $driveFileId)
                    ->whereIn('platform', $platforms)
                    ->where('status', PostedMedia::STATUS_POSTED)
                    ->pluck('platform')
                    ->all();

                $postedManualPlatforms = $this->resolvePreviouslyPublishedDrivePlatforms($config, $driveFileId, $platforms);
                $postedPlatforms = collect(array_merge($postedAutomationPlatforms, $postedManualPlatforms))
                    ->unique()
                    ->values()
                    ->all();

                return count($postedPlatforms) < count($platforms);
            })
            ->values();
    }


    private function resolvePreviouslyPublishedDrivePlatforms(AutomationConfig $config, string $driveFileId, array $platforms): array
    {
        if ($driveFileId === '' || empty($platforms)) {
            return [];
        }

        return DriveImagePost::query()
            ->where('user_id', $config->user_id)
            ->where('page_id', $config->page_id)
            ->where('drive_file_id', $driveFileId)
            ->whereNotNull('posted_at')
            ->get()
            ->flatMap(fn (DriveImagePost $record) => $record->platforms ?? [])
            ->filter(fn (string $platform) => in_array($platform, $platforms, true))
            ->unique()
            ->values()
            ->all();
    }

    private function isSkippableMediaError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'too large')
            || str_contains($normalized, 'unsupported')
            || str_contains($normalized, 'no eligible media');
    }

    private function isOversizedDriveMediaError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'google drive file is too large to process on this server')
            || (str_contains($normalized, 'drive image preparation failed') && str_contains($normalized, 'too large'));
    }

    private function reserveMediaPlatforms(AutomationConfig $config, string $driveFileId, array $platforms): array
    {
        if ($driveFileId === '' || empty($platforms)) {
            return ['platforms_to_publish' => []];
        }

        $platformsToPublish = [];
        $now = now();
        $staleThreshold = $now->copy()->subMinutes(20);

        DB::transaction(function () use ($config, $driveFileId, $platforms, $now, $staleThreshold, &$platformsToPublish): void {
            $existingRecords = PostedMedia::query()
                ->where('automation_config_id', $config->id)
                ->where('page_id', $config->page_id)
                ->where('drive_file_id', $driveFileId)
                ->whereIn('platform', $platforms)
                ->lockForUpdate()
                ->get()
                ->keyBy('platform');

            $alreadyPublishedPlatforms = $this->resolvePreviouslyPublishedDrivePlatforms($config, $driveFileId, $platforms);

            foreach ($platforms as $platform) {
                /** @var PostedMedia|null $existing */
                $existing = $existingRecords->get($platform);

                if (in_array($platform, $alreadyPublishedPlatforms, true)) {
                    continue;
                }

                if ($existing && $existing->status === PostedMedia::STATUS_POSTED) {
                    continue;
                }

                if ($existing && $existing->status === PostedMedia::STATUS_PROCESSING && $existing->updated_at && $existing->updated_at->greaterThan($staleThreshold)) {
                    continue;
                }

                PostedMedia::query()->updateOrCreate(
                    [
                        'automation_config_id' => $config->id,
                        'user_id' => $config->user_id,
                        'page_id' => $config->page_id,
                        'drive_file_id' => $driveFileId,
                        'platform' => $platform,
                    ],
                    [
                        'status' => PostedMedia::STATUS_PROCESSING,
                        'reserved_at' => $now,
                        'posted_at' => null,
                        'last_error' => null,
                    ]
                );

                $platformsToPublish[] = $platform;
            }
        });

        return ['platforms_to_publish' => array_values(array_unique($platformsToPublish))];
    }

    private function markMediaPlatformsPosted(AutomationConfig $config, string $driveFileId, array $platforms, array $result): void
    {
        if ($driveFileId === '' || empty($platforms)) {
            return;
        }

        PostedMedia::query()
            ->where('automation_config_id', $config->id)
            ->where('page_id', $config->page_id)
            ->where('drive_file_id', $driveFileId)
            ->whereIn('platform', $platforms)
            ->update([
                'status' => PostedMedia::STATUS_POSTED,
                'posted_at' => now(),
                'last_error' => null,
                'response_json' => $result['response_json'] ?? null,
            ]);
    }

    private function markMediaPlatformsFailed(AutomationConfig $config, string $driveFileId, array $platforms, string $error): void
    {
        PostedMedia::query()
            ->where('automation_config_id', $config->id)
            ->where('page_id', $config->page_id)
            ->where('drive_file_id', $driveFileId)
            ->whereIn('platform', $platforms)
            ->where('status', PostedMedia::STATUS_PROCESSING)
            ->update([
                'status' => PostedMedia::STATUS_FAILED,
                'last_error' => mb_strimwidth($error, 0, 1000, '...'),
            ]);
    }

    private function normalizePlatforms(string $platform): array
    {
        return match ($platform) {
            'facebook' => ['facebook'],
            'instagram' => ['instagram'],
            default => ['facebook', 'instagram'],
        };
    }

    private function publishVideoAutomation(FacebookPage $page, string $caption, string $videoUrl, array $platforms): array
    {
        $responses = [];

        foreach ($platforms as $platform) {
            if ($platform === 'facebook') {
                $responses['facebook'] = $this->metaVideoService->postToFacebookVideo($page, $videoUrl, $caption);
                continue;
            }

            if ($platform === 'instagram') {
                $responses['instagram'] = $this->metaVideoService->postToInstagramVideo($page, $videoUrl, $caption);
            }
        }

        return [
            'facebook_post_id' => data_get($responses, 'facebook.id') ?: data_get($responses, 'facebook.post_id'),
            'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
            'response_json' => $responses,
        ];
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
