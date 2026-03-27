<?php

namespace App\Services;

class DriveService
{
    public function __construct(private readonly GoogleDriveService $googleDriveService)
    {
    }

    public function fetchImagesFromDriveLink(string $driveLink): array
    {
        $folderId = $this->googleDriveService->extractFolderId($driveLink);
        $images = $this->googleDriveService->listPublicFolderImages($folderId);

        return [
            'folder_id' => $folderId,
            'images' => $images,
        ];
    }
}
