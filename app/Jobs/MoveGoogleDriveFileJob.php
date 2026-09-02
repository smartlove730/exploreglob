<?php

namespace App\Jobs;

use App\Models\DriveApiKey;
use App\Services\GoogleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoveGoogleDriveFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public string $fileId,
        public int $driveApiKeyId,
        public string $pageName
    ) {}

    public function handle(GoogleService $googleService): void
    {
        $driveApiKey = DriveApiKey::find($this->driveApiKeyId);
        if (!$driveApiKey) {
            return;
        }

        $driveApiKey = $googleService->ensureValidDriveToken($driveApiKey);
        $accessToken = $driveApiKey->oauth_access_token;
        if (!$accessToken) {
            return;
        }

        $folderName = "{$this->pageName}_Posted";

        // 1. Get current file to find its parent
        $fileResponse = Http::withToken($accessToken)
            ->get("https://www.googleapis.com/drive/v3/files/{$this->fileId}", [
                'fields' => 'parents'
            ]);

        if (!$fileResponse->ok()) {
            Log::error('Failed to get Drive file details', ['error' => $fileResponse->body()]);
            return;
        }

        $parents = $fileResponse->json('parents') ?? [];
        $currentParentId = $parents[0] ?? 'root';

        // 2. Search for folder with name in the same parent
        $searchResponse = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "mimeType='application/vnd.google-apps.folder' and name='" . str_replace("'", "\\'", $folderName) . "' and '{$currentParentId}' in parents and trashed=false",
                'fields' => 'files(id, name)'
            ]);

        $folderId = null;

        if ($searchResponse->ok() && !empty($searchResponse->json('files'))) {
            $folderId = $searchResponse->json('files')[0]['id'];
        } else {
            // 3. Create folder if not exists
            $createResponse = Http::withToken($accessToken)
                ->post('https://www.googleapis.com/drive/v3/files', [
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$currentParentId]
                ]);

            if ($createResponse->ok()) {
                $folderId = $createResponse->json('id');
            } else {
                Log::error('Failed to create Drive folder', ['error' => $createResponse->body()]);
                return;
            }
        }

        // 4. Move file
        // To move a file, we update its parents by adding the new folder ID and removing the old one.
        $queryParams = ['addParents' => $folderId];
        if (!empty($parents)) {
            $queryParams['removeParents'] = implode(',', $parents);
        }

        $url = "https://www.googleapis.com/drive/v3/files/{$this->fileId}?" . http_build_query($queryParams);
        $moveResponse = Http::withToken($accessToken)
            ->patch($url);

        if (!$moveResponse->ok()) {
            Log::error('Failed to move Drive file', ['error' => $moveResponse->body()]);
        }
    }
}
