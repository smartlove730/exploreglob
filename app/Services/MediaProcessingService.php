<?php

namespace App\Services;

use App\Models\AutomationProcessedMedia;
use Illuminate\Support\Facades\DB;

class MediaProcessingService
{
    public function shouldProcess(string $fileId): bool
    {
        if ($fileId === '') {
            return false;
        }

        return !AutomationProcessedMedia::query()->where('file_id', $fileId)->exists();
    }

    public function reserveForProcessing(int $automationId, string $fileId, ?string $folderId): bool
    {
        if ($fileId === '') {
            return false;
        }

        return DB::transaction(function () use ($automationId, $fileId, $folderId): bool {
            $inserted = AutomationProcessedMedia::query()->insertOrIgnore([
                'automation_id' => $automationId,
                'file_id' => $fileId,
                'folder_id' => $folderId ?: null,
                'status' => AutomationProcessedMedia::STATUS_PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $inserted === 1;
        });
    }

    public function markPosted(string $fileId, array $platforms = []): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_POSTED, null, $platforms);
    }

    public function markSkipped(string $fileId, ?string $reason = null, array $platforms = []): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_SKIPPED, $reason, $platforms);
    }

    public function markFailed(string $fileId, ?string $reason = null, array $platforms = []): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_FAILED, $reason, $platforms);
    }

    private function markStatus(string $fileId, string $status, ?string $reason = null, array $platforms = []): void
    {
        if ($fileId === '') {
            return;
        }

        AutomationProcessedMedia::query()
            ->where('file_id', $fileId)
            ->update([
                'status' => $status,
                'platform' => empty($platforms) ? null : implode(',', array_values(array_unique($platforms))),
                'last_error' => $reason ? mb_strimwidth($reason, 0, 2000, '...') : null,
                'failed_at' => $status === AutomationProcessedMedia::STATUS_FAILED ? now() : null,
            ]);
    }
}
