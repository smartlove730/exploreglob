<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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

    public function ensureFolderExists(string $folderName, string $accessToken): string
    {
        $query = [
            'q' => "name = '".addslashes($folderName)."' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => 1,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
        ];

        $lookup = Http::timeout(30)
            ->withToken($accessToken)
            ->get('https://www.googleapis.com/drive/v3/files', $query);

        if ($lookup->successful()) {
            $folderId = (string) data_get($lookup->json(), 'files.0.id', '');
            if ($folderId !== '') {
                return $folderId;
            }
        }

        $create = Http::timeout(30)
            ->withToken($accessToken)
            ->asJson()
            ->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', [
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

        if (!$create->successful()) {
            throw ValidationException::withMessages([
                'folder' => 'Unable to create failed media folder on Google Drive.',
            ]);
        }

        $folderId = (string) $create->json('id', '');
        if ($folderId === '') {
            throw ValidationException::withMessages([
                'folder' => 'Google Drive did not return a folder id for failed media.',
            ]);
        }

        return $folderId;
    }

    public function moveFileToFolder(string $fileId, string $folderId, string $accessToken): void
    {
        $meta = Http::timeout(30)
            ->withToken($accessToken)
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                'fields' => 'parents',
                'supportsAllDrives' => 'true',
            ]);

        if (!$meta->successful()) {
            throw ValidationException::withMessages([
                'file_id' => 'Unable to load Google Drive file parents before moving file.',
            ]);
        }

        $parents = collect((array) $meta->json('parents', []))
            ->filter(fn ($parentId) => is_string($parentId) && $parentId !== '' && $parentId !== $folderId)
            ->values()
            ->all();

        $query = [
            'addParents' => $folderId,
            'supportsAllDrives' => 'true',
        ];

        if (!empty($parents)) {
            $query['removeParents'] = implode(',', $parents);
        }

        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->patch("https://www.googleapis.com/drive/v3/files/{$fileId}?".http_build_query($query));

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'file_id' => 'Unable to move Google Drive file to failed media folder.',
            ]);
        }
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
