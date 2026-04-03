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
        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$apiKey) {
            throw ValidationException::withMessages([
                'folder_url' => 'Google Drive API key is not configured.',
            ]);
        }

        $images = [];
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
                    'q' => "'{$folderId}' in parents and mimeType contains 'image/' and trashed = false",
                    'fields' => 'nextPageToken,files(id,name,mimeType,webViewLink,resourceKey)',
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

                $images[] = [
                    'id' => $id,
                    'name' => (string) ($file['name'] ?? 'Untitled'),
                    'mime_type' => (string) ($file['mimeType'] ?? ''),
                    'web_view_link' => (string) ($file['webViewLink'] ?? ''),
                    'resource_key' => $resourceKey,
                    'preview_url' => $this->buildPreviewUrl($id, $resourceKey),
                    'download_url' => $this->buildDownloadUrl($id, $resourceKey),
                ];
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return $images;
    }

    public function listDriveMedia(
        ?string $apiKey = null,
        ?string $accessToken = null,
    ): array
    {
        $apiKey = $apiKey ?: config('services.google_drive.api_key');

        if (!$apiKey) {
            throw ValidationException::withMessages([
                'drive_api_key_id' => 'Google Drive API key is not configured.',
            ]);
        }

        $media = [];
        $pageToken = null;

        do {
            $request = Http::timeout(30);

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            $query = [
                'q' => "trashed = false and (mimeType contains 'image/' or mimeType contains 'video/')",
                'fields' => 'nextPageToken,files(id,name,mimeType,webViewLink,resourceKey)',
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
                    'drive_api_key_id' => 'Unable to fetch media from Google Drive.',
                ]);
            }

            $payload = $response->json();
            $files = $payload['files'] ?? [];

            foreach ($files as $file) {
                $id = (string) ($file['id'] ?? '');
                $mimeType = (string) ($file['mimeType'] ?? '');
                $resourceKey = (string) ($file['resourceKey'] ?? '');

                if ($id === '') {
                    continue;
                }

                $type = str_starts_with($mimeType, 'video/') ? 'video' : (str_starts_with($mimeType, 'image/') ? 'image' : '');
                if ($type === '') {
                    continue;
                }

                $media[] = [
                    'id' => $id,
                    'name' => (string) ($file['name'] ?? 'Untitled'),
                    'mime_type' => $mimeType,
                    'type' => $type,
                    'web_view_link' => (string) ($file['webViewLink'] ?? ''),
                    'resource_key' => $resourceKey,
                    'preview_url' => $this->buildPreviewUrl($id, $resourceKey),
                    'download_url' => $this->buildDownloadUrl($id, $resourceKey),
                ];
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return $media;
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
        return $this->fetchFileBinary($fileId, $apiKey, $resourceKey, $accessToken, 'image/*');
    }

    public function fetchFileBinary(
        string $fileId,
        ?string $apiKey = null,
        string $resourceKey = '',
        ?string $accessToken = null,
        string $accept = '*/*'
    ): array
    {
        $query = ['alt' => 'media'];

        if (!$accessToken && $apiKey) {
            $query['key'] = $apiKey;
        }

        if ($resourceKey !== '') {
            $query['resourceKey'] = $resourceKey;
        }

        $request = Http::timeout(30)->withHeaders(['Accept' => $accept]);

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
}
