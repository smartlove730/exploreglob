<?php

namespace App\Services;

use App\Models\DriveApiKey;

class DriveService
{
    public function __construct(private readonly GoogleDriveService $googleDriveService)
    {
    }

    public function fetchImagesFromDriveLink(string $driveLink, ?DriveApiKey $driveApiKey = null): array
    {
        $folderId = $this->googleDriveService->extractFolderId($driveLink);
        $images = $this->googleDriveService->listPublicFolderImages(
            $folderId,
            $driveApiKey?->api_key,
            $driveApiKey?->oauth_access_token
        );

        return [
            'folder_id' => $folderId,
            'images' => $images,
        ];
    }
}
