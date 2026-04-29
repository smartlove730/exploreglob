<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleDriveService
{
    private const MAX_FILE_BINARY_BYTES = 40 * 1024 * 1024; // 40 MB

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

    public function extractFolderResourceKey(string $folderUrl): string
    {
        $folderUrl = trim($folderUrl);
        $query = parse_url($folderUrl, PHP_URL_QUERY);

        if (!is_string($query) || $query === '') {
            return '';
        }

        parse_str($query, $params);
        $resourceKey = (string) ($params['resourcekey'] ?? '');

        if ($resourceKey !== '') {
            return $resourceKey;
        }

        return (string) ($params['resourceKey'] ?? '');
    }

    public function listPublicFolderImages(
        string $folderId,
        ?string $apiKey = null,
        ?string $accessToken = null,
        string $folderResourceKey = '',
    ): array
    {
        return $this->listPublicFolderMedia($folderId, $apiKey, $accessToken, $folderResourceKey)
            ->filter(fn (array $file) => ($file['type'] ?? '') === 'image')
            ->values()
            ->all();
    }

    public function listPublicFolderMedia(
        string $folderId,
        ?string $apiKey = null,
        ?string $accessToken = null,
        string $folderResourceKey = '',
    ): \Illuminate\Support\Collection
    {
        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$accessToken && !$apiKey) {
            throw ValidationException::withMessages([
                'folder_url' => 'Google Drive access is not configured. Connect Google via OAuth or add an API key.',
            ]);
        }

        $filesList = [];
        $pageToken = null;

        do {
            $request = Http::timeout(30);

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            if ($folderResourceKey !== '') {
                $request = $request->withHeaders([
                    'X-Goog-Drive-Resource-Keys' => "{$folderId}/{$folderResourceKey}",
                ]);
            }

            $query = [
                'q' => "'{$folderId}' in parents and (mimeType contains 'image/' or mimeType contains 'video/') and trashed = false",
                'fields' => 'nextPageToken,files(id,name,mimeType,webViewLink,resourceKey,thumbnailLink,imageMediaMetadata(width,height),videoMediaMetadata(width,height,durationMillis))',
                'pageSize' => 200,
                'pageToken' => $pageToken,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ];

            if (!$accessToken) {
                $query['key'] = $apiKey;
            }

            $response = $request->get('https://www.googleapis.com/drive/v3/files', $query);

            if (!$response->successful()) {
                throw ValidationException::withMessages([
                    'folder_url' => 'Unable to fetch images from Google Drive. Make sure the folder is shared publicly.',
                ]);
            }

            $payload = $response->json();
            $files = $payload['files'] ?? [];

            foreach ($files as $file) {
                $id = (string) ($file['id'] ?? '');
                $resourceKey = (string) ($file['resourceKey'] ?? '');

                if ($id === '') {
                    continue;
                }

                $mimeType = (string) ($file['mimeType'] ?? '');
                $type = str_starts_with($mimeType, 'video/') ? 'video' : 'image';

                if (!$this->isSupportedMediaMimeType($mimeType)) {
                    continue;
                }

                $filesList[] = [
                    'id' => $id,
                    'name' => (string) ($file['name'] ?? 'Untitled'),
                    'type' => $type,
                    'mime_type' => $mimeType,
                    'web_view_link' => (string) ($file['webViewLink'] ?? ''),
                    'resource_key' => $resourceKey,
                    'thumbnail_url' => $this->normalizeThumbnailLink((string) ($file['thumbnailLink'] ?? '')),
                    'width' => (int) ($file['imageMediaMetadata']['width'] ?? 0),
                    'height' => (int) ($file['imageMediaMetadata']['height'] ?? 0),
                    'duration_ms' => (int) ($file['videoMediaMetadata']['durationMillis'] ?? 0),
                    'preview_url' => $this->buildPreviewUrl($id, $resourceKey),
                    'download_url' => $this->buildDownloadUrl($id, $resourceKey),
                ];
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return collect($filesList);
    }

    public function listAccessibleFolders(?string $apiKey = null, ?string $accessToken = null): \Illuminate\Support\Collection
    {
        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$accessToken && !$apiKey) {
            return collect();
        }

        $folders = [];
        $pageToken = null;

        do {
            $request = Http::timeout(30);

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            $query = [
                'q' => "mimeType = 'application/vnd.google-apps.folder' and trashed = false",
                'fields' => 'nextPageToken,files(id,name,webViewLink)',
                'pageSize' => 200,
                'pageToken' => $pageToken,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ];

            if (!$accessToken && $apiKey) {
                $query['key'] = $apiKey;
            }

            $response = $request->get('https://www.googleapis.com/drive/v3/files', $query);
            if (!$response->successful()) {
                break;
            }

            $payload = $response->json();
            foreach (($payload['files'] ?? []) as $file) {
                $folderId = (string) ($file['id'] ?? '');
                if ($folderId === '') {
                    continue;
                }

                $folders[] = [
                    'id' => $folderId,
                    'name' => (string) ($file['name'] ?? 'Untitled Folder'),
                    'url' => (string) ($file['webViewLink'] ?? "https://drive.google.com/drive/folders/{$folderId}"),
                ];
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return collect($folders);
    }

    public function buildPreviewUrl(string $fileId, string $resourceKey = ''): string
    {
        return $this->buildUserContentUrl($fileId, 'view', $resourceKey);
    }

    public function buildDownloadUrl(string $fileId, string $resourceKey = ''): string
    {
        return $this->buildUserContentUrl($fileId, 'download', $resourceKey);
    }

    public function normalizeThumbnailLink(string $thumbnailLink): string
    {
        if ($thumbnailLink === '') {
            return '';
        }

        $normalized = str_replace('=s220', '=s800', $thumbnailLink);

        return str_replace('&sz=w220-h220', '&sz=w800-h800', $normalized);
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
        return $this->fetchFileBinary($fileId, $apiKey, $resourceKey, $accessToken);
    }

    public function moveFileBasedOnStatus(string $fileId, string $status, ?string $accessToken, bool $useDateSubfolder = false): bool
    {
        $targetFolderName = match ($status) {
            'failed' => 'FailedPosts',
            'success' => 'SuccessPosts',
            default => null,
        };

        if (!$targetFolderName) {
            Log::warning('Skipped Drive file move due to unsupported status.', ['file_id' => $fileId, 'status' => $status]);
            return false;
        }

        if (!$accessToken) {
            Log::error('Unable to move Drive file because access token is missing.', ['file_id' => $fileId, 'status' => $status]);
            return false;
        }

        try {
            $targetFolderId = $this->findFolderByName($targetFolderName, $accessToken) ?? $this->createFolder($targetFolderName, $accessToken);

            if ($useDateSubfolder) {
                $dateFolder = now()->toDateString();
                $targetFolderId = $this->findFolderByName($dateFolder, $accessToken, $targetFolderId)
                    ?? $this->createFolder($dateFolder, $accessToken, $targetFolderId);
            }

            return $this->moveFileToFolder($fileId, $targetFolderId, $status, $accessToken);
        } catch (\Throwable $exception) {
            Log::error('Google Drive moveFileBasedOnStatus failed.', [
                'file_id' => $fileId,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    public function findFolderByName(string $folderName, string $accessToken, ?string $parentId = null): ?string
    {
        $query = "mimeType = 'application/vnd.google-apps.folder' and trashed = false and name = '{$folderName}'";
        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $response = Http::timeout(20)->withToken($accessToken)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Unable to find Drive folder: '.$response->body());
        }

        return data_get($response->json(), 'files.0.id');
    }

    public function createFolder(string $folderName, string $accessToken, ?string $parentId = null): string
    {
        $body = [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        if ($parentId) {
            $body['parents'] = [$parentId];
        }

        $response = Http::timeout(20)->withToken($accessToken)->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', $body);

        if (!$response->successful()) {
            throw new \RuntimeException('Unable to create Drive folder: '.$response->body());
        }

        return (string) data_get($response->json(), 'id');
    }

    public function moveFileToFolder(string $fileId, string $newFolderId, string $status, string $accessToken): bool
    {
        $metaResponse = Http::timeout(20)->withToken($accessToken)->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
            'fields' => 'id,parents',
            'supportsAllDrives' => 'true',
        ]);

        if ($metaResponse->status() === 404) {
            Log::error('Drive file not found while attempting move.', ['file_id' => $fileId, 'status' => $status]);
            return false;
        }

        if (!$metaResponse->successful()) {
            throw new \RuntimeException('Unable to fetch Drive file metadata: '.$metaResponse->body());
        }

        $oldParents = collect((array) data_get($metaResponse->json(), 'parents', []))->filter()->implode(',');

        $moveResponse = Http::timeout(20)->withToken($accessToken)->patch("https://www.googleapis.com/drive/v3/files/{$fileId}", [
            'addParents' => $newFolderId,
            'removeParents' => $oldParents,
            'supportsAllDrives' => 'true',
        ]);

        if (!$moveResponse->successful()) {
            throw new \RuntimeException('Unable to move Drive file: '.$moveResponse->body());
        }

        Log::info('Drive file moved based on posting status.', [
            'file_id' => $fileId,
            'old_folder' => $oldParents,
            'new_folder' => $newFolderId,
            'status' => $status,
        ]);

        return true;
    }

    public function fetchFileBinary(string $fileId, ?string $apiKey = null, string $resourceKey = '', ?string $accessToken = null): array
    {
        $query = ['alt' => 'media'];

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        if ($resourceKey !== '') {
            $query['resourceKey'] = $resourceKey;
        }

        $sizeBytes = $this->fetchFileSizeBytes($fileId, $apiKey, $resourceKey, $accessToken);
        if ($sizeBytes !== null && $sizeBytes > self::MAX_FILE_BINARY_BYTES) {
            throw ValidationException::withMessages([
                'file_id' => 'Google Drive file is too large to process on this server.',
            ]);
        }

        $request = Http::timeout(30)
            ->withOptions([
                'on_headers' => static function ($response): void {
                    $contentLength = (int) $response->getHeaderLine('Content-Length');
                    if ($contentLength > 0 && $contentLength > self::MAX_FILE_BINARY_BYTES) {
                        throw ValidationException::withMessages([
                            'file_id' => 'Google Drive file is too large to process on this server.',
                        ]);
                    }
                },
            ])
            ->withHeaders(['Accept' => 'image/*,video/*']);

        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get("https://www.googleapis.com/drive/v3/files/{$fileId}", $query);

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'file_id' => 'Unable to load this Google Drive file.',
            ]);
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }

    private function fetchFileSizeBytes(string $fileId, ?string $apiKey = null, string $resourceKey = '', ?string $accessToken = null): ?int
    {
        $query = [
            'fields' => 'size',
            'supportsAllDrives' => 'true',
        ];

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        if ($resourceKey !== '') {
            $query['resourceKey'] = $resourceKey;
        }

        $request = Http::timeout(15);
        if ($accessToken) {
            $request = $request->withToken($accessToken);
        }

        $response = $request->get("https://www.googleapis.com/drive/v3/files/{$fileId}", $query);
        if (!$response->successful()) {
            return null;
        }

        $size = $response->json('size');
        if ($size === null || $size === '') {
            return null;
        }

        return (int) $size;
    }

    private function isSupportedMediaMimeType(string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'video/mp4',
            'video/quicktime',
        ], true);
    }
}
