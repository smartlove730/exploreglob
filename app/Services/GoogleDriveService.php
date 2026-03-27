<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleDriveService
{
    public function extractFolderId(string $folderUrl): string
    {
        $folderUrl = trim($folderUrl);

        if (preg_match('~/folders/([a-zA-Z0-9_-]+)~', $folderUrl, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $folderUrl, $matches) === 1) {
            return $matches[1];
        }

        throw ValidationException::withMessages([
            'folder_url' => 'Unable to parse Google Drive folder ID from the provided link.',
        ]);
    }

    public function listPublicFolderImages(string $folderId): array
    {
        $apiKey = config('services.google_drive.api_key');

        if (!$apiKey) {
            throw ValidationException::withMessages([
                'folder_url' => 'Google Drive API key is not configured.',
            ]);
        }

        $images = [];
        $pageToken = null;

        do {
            $response = Http::timeout(30)
                ->get('https://www.googleapis.com/drive/v3/files', [
                    'key' => $apiKey,
                    'q' => "'{$folderId}' in parents and mimeType contains 'image/' and trashed = false",
                    'fields' => 'nextPageToken,files(id,name,mimeType,webViewLink)',
                    'pageSize' => 200,
                    'pageToken' => $pageToken,
                    'supportsAllDrives' => 'true',
                    'includeItemsFromAllDrives' => 'true',
                ]);

            if (!$response->successful()) {
                throw ValidationException::withMessages([
                    'folder_url' => 'Unable to fetch images from Google Drive. Make sure the folder is shared publicly.',
                ]);
            }

            $payload = $response->json();
            $files = $payload['files'] ?? [];

            foreach ($files as $file) {
                $id = (string) ($file['id'] ?? '');

                if ($id === '') {
                    continue;
                }

                $images[] = [
                    'id' => $id,
                    'name' => (string) ($file['name'] ?? 'Untitled'),
                    'mime_type' => (string) ($file['mimeType'] ?? ''),
                    'web_view_link' => (string) ($file['webViewLink'] ?? ''),
                    'preview_url' => $this->buildPreviewUrl($id),
                    'download_url' => $this->buildDownloadUrl($id),
                ];
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return $images;
    }

    public function buildPreviewUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }

    public function buildDownloadUrl(string $fileId): string
    {
        return "https://drive.google.com/uc?export=download&id={$fileId}";
    }
}
