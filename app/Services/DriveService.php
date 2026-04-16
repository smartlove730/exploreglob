<?php

namespace App\Services;

use App\Models\DriveApiKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DriveService
{
    public function __construct(
        private readonly GoogleDriveService $googleDriveService,
        private readonly GoogleService $googleService,
    )
    {
    }

    public function fetchImagesFromDriveLink(string $driveLink, ?DriveApiKey $driveApiKey = null): array
    {
        $media = $this->fetchMediaFromDriveLink($driveLink, $driveApiKey);

        return [
            'folder_id' => $media['folder_id'],
            'images' => collect($media['media'])->where('type', 'image')->values()->all(),
        ];
    }

    public function fetchMediaFromDriveLink(string $driveLink, ?DriveApiKey $driveApiKey = null): array
    {
        $folderId = $this->googleDriveService->extractFolderId($driveLink);
        $folderResourceKey = $this->googleDriveService->extractFolderResourceKey($driveLink);
        $media = $this->googleDriveService->listPublicFolderMedia(
            $folderId,
            $driveApiKey?->api_key,
            $driveApiKey?->oauth_access_token,
            $folderResourceKey
        )->all();

        return [
            'folder_id' => $folderId,
            'media' => $media,
        ];
    }

    public function prepareInstagramEligibleImage(array $image, DriveApiKey $driveApiKey): string
    {
        $fileId = (string) ($image['id'] ?? '');
        if ($fileId === '') {
            throw new RuntimeException('Unable to prepare Instagram media: missing Drive file id.');
        }

        $resourceKey = (string) ($image['resource_key'] ?? '');
        $driveToken = null;

        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $binary = $this->googleDriveService->fetchImageBinary(
            $fileId,
            $driveApiKey->api_key,
            $resourceKey,
            $driveToken
        );

        return $this->normalizeBinaryToInstagramJpegUrl(
            (string) ($binary['content'] ?? ''),
            $fileId
        );
    }

    public function prepareInstagramEligibleFromUrl(string $sourceUrl): string
    {
        if ($sourceUrl === '') {
            throw new RuntimeException('Empty image URL cannot be normalized for Instagram.');
        }

        $response = Http::timeout(45)
            ->withOptions(['allow_redirects' => true])
            ->withHeaders(['Accept' => 'image/*'])
            ->get($sourceUrl);

        if (!$response->successful()) {
            throw new RuntimeException('Unable to download image for Instagram normalization.');
        }

        return $this->normalizeBinaryToInstagramJpegUrl($response->body(), 'url-image');
    }

    public function prepareInstagramEligibleVideo(array $video, DriveApiKey $driveApiKey): string
    {
        $fileId = (string) ($video['id'] ?? '');
        if ($fileId === '') {
            throw new RuntimeException('Unable to prepare Instagram video: missing Drive file id.');
        }

        $resourceKey = (string) ($video['resource_key'] ?? '');
        $sourceUrl = (string) ($video['download_url'] ?? $video['preview_url'] ?? '');
        $sourceMimeType = strtolower((string) ($video['mime_type'] ?? ''));
        $driveToken = null;

        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $binary = $this->downloadDriveBinaryFromUrl($sourceUrl, $sourceMimeType)
            ?: $this->googleDriveService->fetchFileBinary(
                $fileId,
                $driveApiKey->api_key,
                $resourceKey,
                $driveToken
            );

        $contentType = strtolower((string) ($binary['content_type'] ?? $sourceMimeType));
        $normalizedType = trim(strtok($contentType, ';') ?: '');

        $extension = match ($normalizedType) {
            'video/mp4', 'application/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            default => throw new RuntimeException("Unsupported video format for Instagram publishing: {$contentType}"),
        };

        $videoBinary = (string) ($binary['content'] ?? '');
        if ($videoBinary === '') {
            throw new RuntimeException('Unable to prepare Instagram video: empty video payload.');
        }

        $path = 'automation/instagram/videos/'.Str::uuid()->toString().'-'.$fileId.'.'.$extension;
        Storage::disk('public')->put($path, $videoBinary);

        return $this->forceHttpsUrl(url(Storage::disk('public')->url($path)));
    }

    private function downloadDriveBinaryFromUrl(string $sourceUrl, string $mimeType = ''): ?array
    {
        if ($sourceUrl === '') {
            return null;
        }

        $host = parse_url($sourceUrl, PHP_URL_HOST);
        if (!is_string($host) || (!str_contains($host, 'googleusercontent.com') && !str_contains($host, 'google.com'))) {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->retry(2, 250)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders(['Accept' => $mimeType !== '' ? $mimeType : 'image/*,video/*'])
                ->get($sourceUrl);

            if (!$response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type', ''));
            if (!str_starts_with($contentType, 'image/') && !str_starts_with($contentType, 'video/')) {
                return null;
            }

            $content = (string) $response->body();
            if ($content === '') {
                return null;
            }

            return [
                'content' => $content,
                'content_type' => (string) $response->header('Content-Type', $mimeType !== '' ? $mimeType : 'application/octet-stream'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeInstagramDimensions(\GdImage $source): \GdImage
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $ratio = $srcW / max(1, $srcH);

        // Instagram feed supports approx 4:5 to 1.91:1.
        $minRatio = 0.8;
        $maxRatio = 1.91;
        $cropX = 0;
        $cropY = 0;
        $cropW = $srcW;
        $cropH = $srcH;

        if ($ratio > $maxRatio) {
            $cropW = (int) floor($srcH * $maxRatio);
            $cropX = (int) floor(($srcW - $cropW) / 2);
        } elseif ($ratio < $minRatio) {
            $cropH = (int) floor($srcW / $minRatio);
            $cropY = (int) floor(($srcH - $cropH) / 2);
        }

        $targetW = max(320, $cropW);
        $targetH = max(320, $cropH);

        $canvas = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);

        imagedestroy($source);

        return $canvas;
    }

    private function encodeJpegBinary(\GdImage $image, int $quality = 90): string
    {
        ob_start();
        imagejpeg($image, null, max(60, min(95, $quality)));
        $binary = (string) ob_get_clean();

        if ($binary === '') {
            throw new RuntimeException('Failed to encode image for Instagram.');
        }

        return $binary;
    }

    private function forceHttpsUrl(string $url): string
    {
        $parts = parse_url($url);

        if (!isset($parts['host'])) {
            return $url;
        }

        $parts['scheme'] = 'https';

        $httpsUrl = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $httpsUrl .= ':'.$parts['port'];
        }
        $httpsUrl .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $httpsUrl .= '?'.$parts['query'];
        }

        return $httpsUrl;
    }

    private function normalizeBinaryToInstagramJpegUrl(string $binaryContent, string $seed): string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            throw new RuntimeException('GD extension is required to normalize Instagram images.');
        }

        $imageResource = @imagecreatefromstring($binaryContent);
        if (!$imageResource) {
            throw new RuntimeException('Unsupported image format for Instagram publishing.');
        }

        $normalized = $this->normalizeInstagramDimensions($imageResource);
        $jpegBinary = $this->encodeJpegBinary($normalized, 90);
        imagedestroy($normalized);

        $path = 'automation/instagram/'.Str::uuid()->toString().'-'.$seed.'.jpg';
        Storage::disk('public')->put($path, $jpegBinary);

        return $this->forceHttpsUrl(url(Storage::disk('public')->url($path)));
    }
}
