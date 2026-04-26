<?php

namespace App\Jobs;

use App\Models\FacebookPage;
use App\Models\ScheduledPost;
use App\Models\ScheduledPostImport;
use App\Models\User;
use App\Models\UserMedia;
use App\Services\PlanEnforcementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessScheduledPostCsvImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    private const MAX_ROWS = 5000;

    public function __construct(public int $importId)
    {
    }

    public function handle(PlanEnforcementService $planEnforcementService): void
    {
        $import = ScheduledPostImport::query()->find($this->importId);
        if (!$import) {
            return;
        }

        /** @var User|null $user */
        $user = User::query()->find($import->user_id);
        if (!$user) {
            $import->update(['status' => ScheduledPostImport::STATUS_FAILED, 'last_error' => 'Import user not found.', 'finished_at' => now()]);
            return;
        }

        $import->update(['status' => ScheduledPostImport::STATUS_PROCESSING, 'last_error' => null]);

        $errors = [];
        $total = 0;
        $success = 0;

        try {
            $stream = Storage::disk('local')->readStream($import->source_file_path);
            if (!is_resource($stream)) {
                throw new \RuntimeException('Unable to read CSV file.');
            }

            $header = null;
            while (($row = fgetcsv($stream)) !== false) {
                if ($header === null) {
                    $header = $this->normalizeHeader($row);
                    continue;
                }

                $total++;
                if ($total > self::MAX_ROWS) {
                    $errors[] = ['row' => $total + 1, 'error' => 'Import row limit exceeded (max '.self::MAX_ROWS.' rows).'];
                    break;
                }
                $payload = $this->rowToPayload($header, $row);
                $validationError = $this->validateRow($payload, $user, $planEnforcementService);
                if ($validationError !== null) {
                    $errors[] = ['row' => $total + 1, 'error' => $validationError];
                    continue;
                }

                ScheduledPost::create([
                    'user_id' => $user->id,
                    'page_id' => (int) $payload['page_id'],
                    'message' => (string) ($payload['message'] ?? $payload['caption']),
                    'media_type' => $payload['media_type'] ?? 'image',
                    'image_url' => $payload['image_url'] ?? null,
                    'video_path' => null,
                    'video_url' => $payload['video_url'] ?? null,
                    'platforms' => $this->parsePlatforms((string) ($payload['platforms'] ?? 'facebook')),
                    'scheduled_for' => Carbon::parse((string) $payload['scheduled_for']),
                    'status' => ScheduledPost::STATUS_PENDING,
                    'last_error' => null,
                ]);

                $success++;
            }

            fclose($stream);

            $errorReportPath = null;
            if (!empty($errors)) {
                $errorReportPath = 'imports/scheduled-posts/errors-'.$import->id.'-'.now()->timestamp.'.csv';
                $contents = "row,error\n";
                foreach ($errors as $error) {
                    $contents .= $error['row'].',"'.str_replace('"', '""', $error['error'])."\"\n";
                }
                Storage::disk('local')->put($errorReportPath, $contents);
            }

            $import->update([
                'status' => ScheduledPostImport::STATUS_COMPLETED,
                'total_rows' => $total,
                'success_rows' => $success,
                'failed_rows' => count($errors),
                'error_report_path' => $errorReportPath,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Scheduled post CSV import failed', ['import_id' => $import->id, 'error' => $exception->getMessage()]);
            $import->update([
                'status' => ScheduledPostImport::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
            throw $exception;
        }
    }

    private function normalizeHeader(array $header): array
    {
        return collect($header)->map(fn ($value) => strtolower(trim((string) $value)))->all();
    }

    private function rowToPayload(array $header, array $row): array
    {
        $payload = [];
        foreach ($header as $i => $key) {
            $payload[$key] = isset($row[$i]) ? trim((string) $row[$i]) : null;
        }

        return $payload;
    }

    private function validateRow(array &$payload, User $user, PlanEnforcementService $planEnforcementService): ?string
    {
        $message = (string) ($payload['message'] ?? $payload['caption'] ?? '');
        if ($message === '') {
            return 'message/caption is required';
        }

        if (empty($payload['scheduled_for'])) {
            return 'scheduled_for is required';
        }

        try {
            $scheduledFor = Carbon::parse((string) $payload['scheduled_for']);
        } catch (\Throwable) {
            return 'scheduled_for is not a valid datetime';
        }

        if ($scheduledFor->lte(now())) {
            return 'scheduled_for must be in the future';
        }

        $pageId = (int) ($payload['page_id'] ?? 0);
        if ($pageId <= 0) {
            return 'page_id is required';
        }

        $page = FacebookPage::query()->ownedBy($user)->whereKey($pageId)->where('is_active', true)->first();
        if (!$page) {
            return 'page_id is invalid or not owned by user';
        }

        $platforms = $this->parsePlatforms((string) ($payload['platforms'] ?? 'facebook'));
        if (empty($platforms)) {
            return 'platforms is required';
        }

        $mediaType = strtolower((string) ($payload['media_type'] ?? 'image'));
        if (!in_array($mediaType, ['image', 'video'], true)) {
            return 'media_type must be image or video';
        }
        $payload['media_type'] = $mediaType;


        if (!empty($payload['media_id'])) {
            $media = UserMedia::query()->ownedBy($user)->whereKey((int) $payload['media_id'])->first();
            if (!$media) {
                return 'media_id is invalid or not owned by user';
            }

            if ($media->type === UserMedia::TYPE_VIDEO) {
                $payload['video_url'] = $media->public_url;
                $payload['media_type'] = 'video';
            } else {
                $payload['image_url'] = $media->public_url;
            }
        }

        if ($payload['media_type'] === 'video' && empty($payload['video_url'])) {
            return 'video_url or video media_id is required for video posts';
        }

        if ($payload['media_type'] === 'image' && in_array('instagram', $platforms, true) && empty($payload['image_url'])) {
            return 'image_url or image media_id is required for instagram image posts';
        }

        try {
            $planEnforcementService->assertCanPost($user, $platforms);
        } catch (\Throwable $exception) {
            return 'plan restriction: '.$exception->getMessage();
        }

        return null;
    }

    private function parsePlatforms(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($platform) => trim(strtolower($platform)))
            ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram'], true))
            ->unique()
            ->values()
            ->all();
    }
}
