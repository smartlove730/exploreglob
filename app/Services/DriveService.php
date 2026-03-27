<?php

namespace App\Services;

use App\Models\DriveApiKey;
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

        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            throw new RuntimeException('GD extension is required to normalize Instagram images.');
        }

        $imageResource = @imagecreatefromstring((string) ($binary['content'] ?? ''));
        if (!$imageResource) {
            throw new RuntimeException('Unsupported image format for Instagram publishing.');
        }

        $normalized = $this->normalizeInstagramDimensions($imageResource);
        $jpegBinary = $this->encodeJpegBinary($normalized, 90);

        imagedestroy($normalized);

        $path = 'automation/instagram/'.Str::uuid()->toString().'-'.$fileId.'.jpg';
        Storage::disk('public')->put($path, $jpegBinary);

        return $this->forceHttpsUrl(url(Storage::disk('public')->url($path)));
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
}
