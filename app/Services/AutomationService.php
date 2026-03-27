<?php

namespace App\Services;

use App\Models\AutomationConfig;
use App\Models\AutomationPostLog;
use App\Models\FacebookPage;
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

    public function runAllAutomations(?int $automationConfigId = null): void
    {
        Cache::lock('automation:run-all', 300)->block(5, function () use ($automationConfigId) {
            $configs = AutomationConfig::query()
                ->with('page.facebookAccount')
                ->where('is_active', true)
                ->when($automationConfigId, fn ($query) => $query->whereKey($automationConfigId))
                ->get();

            foreach ($configs as $config) {
                $this->runSingleAutomation($config);
                sleep(2); // soft rate-limit between external API calls
            }
        });
    }

    private function runSingleAutomation(AutomationConfig $config): void
    {
        if (!$this->canRunNow($config)) {
            $this->logSkipped($config, 'Skipped due to schedule or post limit constraints.');

            return;
        }

        try {
            $drivePayload = $this->driveService->fetchImagesFromDriveLink($config->drive_link);
            $folderId = (string) ($drivePayload['folder_id'] ?? '');
            $images = collect($drivePayload['images'] ?? []);

            $unusedImage = $this->resolveUnusedImage($config, $images);

            if (!$unusedImage) {
                $this->logSkipped($config, 'No unused images available in Drive folder.');

                return;
            }

            $imageUrl = (string) ($unusedImage['download_url'] ?? $unusedImage['preview_url'] ?? '');
            $caption = $this->aiCaptionService->generateCaption($config->prompt, $imageUrl);
            $platforms = $this->normalizePlatforms($config->platforms);

            /** @var FacebookPage|null $page */
            $page = $config->page;
            if (!$page || !$page->is_active) {
                $this->logFailed($config, $unusedImage, $platforms, 'Selected page is missing or inactive.');

                return;
            }

            $result = $this->metaPostService->publish($page, $caption, $imageUrl, $platforms);

            DB::transaction(function () use ($config, $unusedImage, $platforms, $result, $caption, $imageUrl, $folderId) {
                AutomationPostLog::create([
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
                ]);

                $config->forceFill(['last_run_at' => now()])->save();
            });
        } catch (Throwable $exception) {
            Log::error('Automation failed', [
                'automation_config_id' => $config->id,
                'error' => $exception->getMessage(),
            ]);

            $this->logFailed($config, null, $this->normalizePlatforms($config->platforms), $exception->getMessage());
        }
    }

    private function canRunNow(AutomationConfig $config): bool
    {
        $todayCount = AutomationPostLog::query()
            ->where('automation_config_id', $config->id)
            ->where('status', 'success')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= max(1, (int) $config->post_limit_per_day)) {
            return false;
        }

        if (!$config->last_run_at) {
            return true;
        }

        $runsPerDay = max(1, (int) $config->runs_per_day);
        $minIntervalMinutes = (int) floor(1440 / $runsPerDay);

        return $config->last_run_at->lte(now()->subMinutes(max(1, $minIntervalMinutes)));
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

    private function logSkipped(AutomationConfig $config, string $message): void
    {
        AutomationPostLog::create([
            'automation_config_id' => $config->id,
            'page_id' => $config->page_id,
            'status' => 'skipped',
            'message' => $message,
            'posted_at' => Carbon::now(),
        ]);
    }

    private function logFailed(AutomationConfig $config, ?array $image, array $platforms, string $message): void
    {
        AutomationPostLog::create([
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
}
