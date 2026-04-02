<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleDriveService
{
    public function extractFolderReference(string $folderUrl): array
    {
        $folderUrl = trim($folderUrl);
        $folderId = null;

        if (preg_match('~/folders/([a-zA-Z0-9_-]+)~', $folderUrl, $matches) === 1) {
            $folderId = $matches[1];
        } elseif (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $folderUrl, $matches) === 1) {
            $folderId = $matches[1];
        }

        if (!$folderId) {
            throw ValidationException::withMessages([
                'folder_url' => 'Unable to parse Google Drive folder ID from the provided link.',
            ]);
        }

        $resourceKey = '';
        $query = parse_url($folderUrl, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $queryParams);
            $resourceKey = (string) ($queryParams['resourcekey'] ?? $queryParams['resourceKey'] ?? '');
        }

        return [
            'id' => $folderId,
            'resource_key' => trim($resourceKey),
        ];
    }

    public function extractFolderId(string $folderUrl): string
    {
        return $this->extractFolderReference($folderUrl)['id'];
    }

    public function listPublicFolderImages(string $folderId, ?string $apiKey = null, ?string $accessToken = null, string $folderResourceKey = ''): array
    {
        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$apiKey) {
            throw ValidationException::withMessages([
                'folder_url' => 'Google Drive API key is not configured.',
            ]);
        }

        $images = [];
        $seenImageIds = [];
        $visitedFolders = [];
        $pendingFolders = [[
            'id' => $folderId,
            'resource_key' => $folderResourceKey,
        ]];

        while (!empty($pendingFolders)) {
            $current = array_shift($pendingFolders);
            $currentFolderId = (string) ($current['id'] ?? '');
            $currentFolderResourceKey = (string) ($current['resource_key'] ?? '');

            if ($currentFolderId === '' || isset($visitedFolders[$currentFolderId])) {
                continue;
            }

            $visitedFolders[$currentFolderId] = true;
            $files = $this->listFolderEntries($currentFolderId, $apiKey, $accessToken, $currentFolderResourceKey);

            foreach ($files as $file) {
                $mimeType = (string) ($file['mimeType'] ?? '');
                $fileId = (string) ($file['id'] ?? '');
                $resourceKey = (string) ($file['resourceKey'] ?? '');

                if ($mimeType === 'application/vnd.google-apps.folder' && $fileId !== '') {
                    $pendingFolders[] = [
                        'id' => $fileId,
                        'resource_key' => $resourceKey,
                    ];
                    continue;
                }

                if ($mimeType === 'application/vnd.google-apps.shortcut') {
                    $targetId = (string) data_get($file, 'shortcutDetails.targetId', '');
                    $targetMimeType = (string) data_get($file, 'shortcutDetails.targetMimeType', '');
                    $targetResourceKey = (string) data_get($file, 'shortcutDetails.targetResourceKey', '');

                    if ($targetMimeType === 'application/vnd.google-apps.folder' && $targetId !== '') {
                        $pendingFolders[] = [
                            'id' => $targetId,
                            'resource_key' => $targetResourceKey,
                        ];
                        continue;
                    }

                    if (!str_starts_with($targetMimeType, 'image/')) {
                        continue;
                    }

                    $fileId = $targetId !== '' ? $targetId : $fileId;
                    $resourceKey = $targetResourceKey !== '' ? $targetResourceKey : $resourceKey;
                    $mimeType = $targetMimeType;
                }

                if ($fileId === '' || !str_starts_with($mimeType, 'image/')) {
                    continue;
                }

                if (isset($seenImageIds[$fileId])) {
                    continue;
                }

                $seenImageIds[$fileId] = true;

                $images[] = [
                    'id' => $fileId,
                    'name' => (string) ($file['name'] ?? 'Untitled'),
                    'mime_type' => $mimeType,
                    'web_view_link' => (string) ($file['webViewLink'] ?? ''),
                    'resource_key' => $resourceKey,
                    'preview_url' => $this->buildPreviewUrl($fileId, $resourceKey),
                    'download_url' => $this->buildDownloadUrl($fileId, $resourceKey),
                ];
            }
        }

        return $images;
    }

    private function listFolderEntries(string $folderId, ?string $apiKey, ?string $accessToken, string $folderResourceKey = ''): array
    {
        $pageToken = null;
        $files = [];

        do {
            $request = Http::timeout(30);
            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            $query = [
                'q' => "'{$folderId}' in parents and (mimeType contains 'image/' or mimeType = 'application/vnd.google-apps.folder' or mimeType = 'application/vnd.google-apps.shortcut') and trashed = false",
                'fields' => 'nextPageToken,files(id,name,mimeType,webViewLink,resourceKey,shortcutDetails(targetId,targetMimeType,targetResourceKey))',
                'pageSize' => 200,
                'pageToken' => $pageToken,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ];

            if ($folderResourceKey !== '') {
                $query['resourceKeys'] = $folderId.'/'.$folderResourceKey;
            }

            if (!$accessToken) {
                $query['key'] = $apiKey;
            }

            $response = $request->get('https://www.googleapis.com/drive/v3/files', $query);

            if (!$response->successful() && $accessToken) {
                $folderMetadata = $this->fetchFolderMetadata($folderId, $apiKey, $accessToken, $folderResourceKey);
                $sharedDriveId = (string) ($folderMetadata['driveId'] ?? '');

                if ($sharedDriveId !== '') {
                    $retryQuery = array_merge($query, [
                        'corpora' => 'drive',
                        'driveId' => $sharedDriveId,
                    ]);

                    $response = $request->get('https://www.googleapis.com/drive/v3/files', $retryQuery);
                }
            }

            if (!$response->successful()) {
                throw ValidationException::withMessages([
                    'folder_url' => 'Unable to fetch images from Google Drive. '.$this->extractGoogleErrorMessage($response),
                ]);
            }

            $payload = $response->json();
            $files = array_merge($files, (array) ($payload['files'] ?? []));
            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return $files;
    }

    private function fetchFolderMetadata(string $folderId, ?string $apiKey, ?string $accessToken, string $folderResourceKey = ''): array
    {
        $query = [
            'fields' => 'id,driveId,resourceKey',
            'supportsAllDrives' => 'true',
        ];

        if ($folderResourceKey !== '') {
            $query['resourceKey'] = $folderResourceKey;
        }

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        $request = Http::timeout(30);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get("https://www.googleapis.com/drive/v3/files/{$folderId}", $query);

        return $response->successful() ? (array) $response->json() : [];
    }

    private function extractGoogleErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        $message = (string) data_get($response->json(), 'error.message', '');

        if ($message === '') {
            return 'Make sure the folder is shared publicly (or accessible by the connected Drive account).';
        }

        return trim($message);
    }

    public function buildPreviewUrl(string $fileId, string $resourceKey = ''): string
    {
        return $this->buildUserContentUrl($fileId, 'view', $resourceKey);
    }

    public function buildDownloadUrl(string $fileId, string $resourceKey = ''): string
    {
        return $this->buildUserContentUrl($fileId, 'download', $resourceKey);
    }

    private function buildUserContentUrl(string $fileId, string $export, string $resourceKey = ''): string
    {
        $query = [
            'id' => $fileId,
            'export' => $export,
            'authuser' => '0',
        ];

        if ($resourceKey !== '') {
            $query['resourcekey'] = $resourceKey;
        }

        return 'https://drive.usercontent.google.com/download?'.http_build_query($query);
    }

    public function fetchImageBinary(string $fileId, ?string $apiKey = null, string $resourceKey = '', ?string $accessToken = null): array
    {
        $query = ['alt' => 'media'];

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        if ($resourceKey !== '') {
            $query['resourceKey'] = $resourceKey;
        }

        $request = Http::timeout(30)->withHeaders(['Accept' => 'image/*']);

        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get("https://www.googleapis.com/drive/v3/files/{$fileId}", $query);

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'file_id' => 'Unable to load this Google Drive image.',
            ]);
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }
}
