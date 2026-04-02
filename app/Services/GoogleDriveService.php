<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleDriveService
{
    public function extractFolderReference(string $folderUrl): array
    {
        $folderUrl = trim($folderUrl);
        Log::info('google_drive.extract_folder_reference.start', [
            'folder_url' => $folderUrl,
        ]);
        $folderId = null;

        if (preg_match('~/folders/([a-zA-Z0-9_-]+)~', $folderUrl, $matches) === 1) {
            $folderId = $matches[1];
        } elseif (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $folderUrl, $matches) === 1) {
            $folderId = $matches[1];
        }

        if (!$folderId) {
            Log::warning('google_drive.extract_folder_reference.failed', [
                'folder_url' => $folderUrl,
            ]);
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

        $reference = [
            'id' => $folderId,
            'resource_key' => trim($resourceKey),
        ];

        Log::info('google_drive.extract_folder_reference.success', [
            'folder_id' => $reference['id'],
            'has_resource_key' => $reference['resource_key'] !== '',
        ]);

        return $reference;
    }

    public function extractFolderId(string $folderUrl): string
    {
        return $this->extractFolderReference($folderUrl)['id'];
    }

    public function listPublicFolderImages(string $folderId, ?string $apiKey = null, ?string $accessToken = null, string $folderResourceKey = ''): array
    {
        Log::info('google_drive.list_public_folder_images.start', [
            'folder_id' => $folderId,
            'has_folder_resource_key' => $folderResourceKey !== '',
            'has_access_token' => !empty($accessToken),
            'has_api_key' => !empty($apiKey) || !empty(config('services.google_drive.api_key')),
        ]);

        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$apiKey) {
            throw ValidationException::withMessages([
                'folder_url' => 'Google Drive API key is not configured.',
            ]);
        }

        $images = [];
        $seenImageIds = [];
        $resolvedShortcutTargets = [];
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
            Log::info('google_drive.list_public_folder_images.folder_scan_start', [
                'folder_id' => $currentFolderId,
                'has_resource_key' => $currentFolderResourceKey !== '',
            ]);
            $files = $this->listFolderEntries($currentFolderId, $apiKey, $accessToken, $currentFolderResourceKey);
            Log::info('google_drive.list_public_folder_images.folder_scan_result', [
                'folder_id' => $currentFolderId,
                'entries_count' => count($files),
                'mime_summary' => $this->summarizeMimeTypes($files),
            ]);

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
                    $targetExtension = '';

                    if ($targetMimeType === 'application/vnd.google-apps.folder' && $targetId !== '') {
                        $pendingFolders[] = [
                            'id' => $targetId,
                            'resource_key' => $targetResourceKey,
                        ];
                        continue;
                    }

                    $targetName = (string) ($file['name'] ?? '');
                    if ($targetId !== '' && $targetMimeType === '') {
                        if (!array_key_exists($targetId, $resolvedShortcutTargets)) {
                            $resolvedShortcutTargets[$targetId] = $this->fetchFileMetadata(
                                $targetId,
                                $apiKey,
                                $accessToken,
                                $targetResourceKey
                            );
                        }

                        $targetMeta = (array) ($resolvedShortcutTargets[$targetId] ?? []);
                        $targetMimeType = (string) ($targetMeta['mimeType'] ?? '');
                        $targetName = (string) ($targetMeta['name'] ?? $targetName);
                        $targetExtension = (string) ($targetMeta['fileExtension'] ?? '');
                    }

                    if (!$this->isImageLike($targetMimeType, $targetName)) {
                        if (!$this->isImageLike($targetMimeType, $targetName, $targetExtension)) {
                            continue;
                        }
                    }

                    if ($targetMimeType === '') {
                        continue;
                    }

                    $fileId = $targetId !== '' ? $targetId : $fileId;
                    $resourceKey = $targetResourceKey !== '' ? $targetResourceKey : $resourceKey;
                    $mimeType = $targetMimeType;
                }

                $fileName = (string) ($file['name'] ?? '');
                $fileExtension = (string) ($file['fileExtension'] ?? '');
                if ($fileId === '' || !$this->isImageLike($mimeType, $fileName, $fileExtension)) {
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

        Log::info('google_drive.list_public_folder_images.complete', [
            'folder_id' => $folderId,
            'visited_folders' => count($visitedFolders),
            'images_count' => count($images),
        ]);

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
                'q' => "'{$folderId}' in parents and trashed = false",
                'fields' => 'nextPageToken,files(id,name,mimeType,fileExtension,webViewLink,resourceKey,shortcutDetails(targetId,targetMimeType,targetResourceKey))',
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
            Log::debug('google_drive.list_folder_entries.response', [
                'folder_id' => $folderId,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'page_token' => $pageToken,
            ]);

            if (!$response->successful() && $accessToken) {
                $folderMetadata = $this->fetchFolderDriveMetadata($folderId, $apiKey, $accessToken, $folderResourceKey);
                $sharedDriveId = (string) ($folderMetadata['driveId'] ?? '');

                if ($sharedDriveId !== '') {
                    $retryQuery = array_merge($query, [
                        'corpora' => 'drive',
                        'driveId' => $sharedDriveId,
                    ]);

                    $response = $request->get('https://www.googleapis.com/drive/v3/files', $retryQuery);
                    Log::debug('google_drive.list_folder_entries.retry_shared_drive', [
                        'folder_id' => $folderId,
                        'drive_id' => $sharedDriveId,
                        'status' => $response->status(),
                        'successful' => $response->successful(),
                    ]);
                }
            }

            if (!$response->successful()) {
                Log::warning('google_drive.list_folder_entries.failed', [
                    'folder_id' => $folderId,
                    'status' => $response->status(),
                    'error' => $this->extractGoogleErrorMessage($response),
                ]);
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

    private function fetchFileMetadata(string $fileId, ?string $apiKey, ?string $accessToken, string $resourceKey = ''): array
    {
        $query = [
            'fields' => 'id,name,mimeType,fileExtension,resourceKey',
            'supportsAllDrives' => 'true',
        ];

        if ($resourceKey !== '') {
            $query['resourceKey'] = $resourceKey;
        }

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        $request = Http::timeout(30);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get("https://www.googleapis.com/drive/v3/files/{$fileId}", $query);
        Log::debug('google_drive.fetch_file_metadata.response', [
            'file_id' => $fileId,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

        return $response->successful() ? (array) $response->json() : [];
    }

    private function fetchFolderDriveMetadata(string $folderId, ?string $apiKey, ?string $accessToken, string $folderResourceKey = ''): array
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
        Log::debug('google_drive.fetch_folder_metadata.response', [
            'folder_id' => $folderId,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

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

    private function isImageLike(string $mimeType, string $name = '', string $fileExtension = ''): bool
    {
        if (str_starts_with($mimeType, 'image/')) {
            return true;
        }

        $ext = strtolower(trim($fileExtension));
        if ($ext === '') {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        }

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'heic', 'heif', 'avif'], true);
    }

    private function summarizeMimeTypes(array $files): array
    {
        $summary = [];
        foreach ($files as $file) {
            $mime = (string) ($file['mimeType'] ?? 'unknown');
            $summary[$mime] = ($summary[$mime] ?? 0) + 1;
        }

        arsort($summary);

        return array_slice($summary, 0, 10, true);
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
        Log::debug('google_drive.fetch_image_binary.start', [
            'file_id' => $fileId,
            'has_resource_key' => $resourceKey !== '',
            'has_access_token' => !empty($accessToken),
            'has_api_key' => !empty($apiKey),
        ]);
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
        Log::debug('google_drive.fetch_image_binary.response', [
            'file_id' => $fileId,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

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
