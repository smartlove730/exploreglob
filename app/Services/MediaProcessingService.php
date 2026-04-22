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

    public function markPosted(string $fileId): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_POSTED);
    }

    public function markSkipped(string $fileId): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_SKIPPED);
    }

    public function markFailed(string $fileId): void
    {
        $this->markStatus($fileId, AutomationProcessedMedia::STATUS_FAILED);
    }

    private function markStatus(string $fileId, string $status): void
    {
        if ($fileId === '') {
            return;
        }

        AutomationProcessedMedia::query()
            ->where('file_id', $fileId)
            ->update(['status' => $status]);
    }
}
