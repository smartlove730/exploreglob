<?php

namespace App\Services;

use App\Models\FacebookPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MetaVideoService
{
    private string $apiVersion = 'v19.0';

    public function __construct(private readonly InstagramService $instagramService)
    {
    }

    public function postToFacebookVideo(FacebookPage $page, string $videoUrl, string $caption, ?string $videoPath = null): array
    {
        $this->assertPublicHttpsVideoUrl($videoUrl);

        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/videos", [
            'file_url' => $videoUrl,
            'description' => $caption,
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok() && $this->shouldRetryFacebookVideoAsUpload($response->body())) {
            $uploadableVideo = $this->resolveUploadableVideo($videoUrl, $videoPath);

            if ($uploadableVideo) {
                try {
                    $uploadResponse = Http::attach(
                        'source',
                        $uploadableVideo['stream'],
                        $uploadableVideo['filename'],
                        ['Content-Type' => 'video/mp4']
                    )->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/videos", [
                        'description' => $caption,
                        'access_token' => $page->page_access_token,
                    ]);
                } finally {
                    fclose($uploadableVideo['stream']);
                    if (!empty($uploadableVideo['cleanup_path']) && is_string($uploadableVideo['cleanup_path'])) {
                        @unlink($uploadableVideo['cleanup_path']);
                    }
                }

                if ($uploadResponse->ok()) {
                    return $uploadResponse->json();
                }

                throw new RuntimeException('Unable to post Facebook video via source upload fallback: '.$uploadResponse->body());
            }
        }

        if (!$response->ok()) {
            throw new RuntimeException('Unable to post Facebook video: '.$response->body());
        }

        return $response->json();
    }

    public function postToInstagramVideo(FacebookPage $page, string $videoUrl, string $caption, int $maxPollAttempts = 20, int $pollDelaySeconds = 4): array
    {
        $this->assertPublicHttpsVideoUrl($videoUrl);

        $igUserId = $this->instagramService->ensureInstagramBusinessAccountId($page);

        $createResponse = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", [
            // Meta deprecated VIDEO for Instagram feed publishing; REELS is now required.
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $caption,
            'access_token' => $page->page_access_token,
        ]);

        if (!$createResponse->ok() || !isset($createResponse['id'])) {
            throw new RuntimeException('Unable to create Instagram video container: '.$createResponse->body());
        }

        $creationId = (string) $createResponse['id'];
        $lastStatus = null;

        for ($attempt = 1; $attempt <= max(1, $maxPollAttempts); $attempt++) {
            $status = $this->checkInstagramStatus($creationId, $page->page_access_token);
            $lastStatus = (string) ($status['status_code'] ?? 'UNKNOWN');

            if ($lastStatus === 'FINISHED') {
                break;
            }

            if (in_array($lastStatus, ['ERROR', 'EXPIRED'], true)) {
                $errorMessage = trim((string) ($status['error_message'] ?? data_get($status, 'status')));
                $suffix = $errorMessage !== '' ? " ({$errorMessage})" : '';
                throw new RuntimeException("Instagram video processing failed with status: {$lastStatus}{$suffix}");
            }

            sleep(max(2, $pollDelaySeconds));
        }

        if ($lastStatus !== 'FINISHED') {
            throw new RuntimeException('Instagram video processing timed out before status FINISHED.');
        }

        $publishResponse = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $page->page_access_token,
        ]);

        if (!$publishResponse->ok()) {
            throw new RuntimeException('Unable to publish Instagram video: '.$publishResponse->body());
        }

        return [
            'creation_id' => $creationId,
            'publish_response' => $publishResponse->json(),
        ];
    }

    public function checkInstagramStatus(string $creationId, string $pageAccessToken): array
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$creationId}", [
            'fields' => 'status_code,status,error_message',
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok() && $this->isUnknownFieldError($response->body(), 'error_message')) {
            $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$creationId}", [
                'fields' => 'status_code,status',
                'access_token' => $pageAccessToken,
            ]);
        }

        if (!$response->ok()) {
            throw new RuntimeException('Unable to check Instagram video status: '.$response->body());
        }

        return $response->json();
    }

    private function isUnknownFieldError(string $body, string $field): bool
    {
        if ($body === '' || $field === '') {
            return false;
        }

        return str_contains($body, 'Tried accessing nonexisting field')
            && str_contains($body, "({$field})");
    }

    private function assertPublicHttpsVideoUrl(string $videoUrl): void
    {
        $host = parse_url($videoUrl, PHP_URL_HOST);
        $scheme = parse_url($videoUrl, PHP_URL_SCHEME);
        $path = strtolower((string) parse_url($videoUrl, PHP_URL_PATH));

        if ($scheme !== 'https' || !$host) {
            throw new RuntimeException('Video URL must be publicly accessible and use HTTPS.');
        }

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('Video URL must not point to localhost.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException('Video URL must be publicly reachable.');
        }

        if ($path !== '' && !str_ends_with($path, '.mp4') && !$this->urlServesMp4Content($videoUrl)) {
            throw new RuntimeException('Video URL must point to an MP4 file.');
        }
    }

    private function urlServesMp4Content(string $videoUrl): bool
    {
        $requests = [
            fn () => Http::timeout(10)->head($videoUrl),
            fn () => Http::timeout(10)->withHeaders(['Range' => 'bytes=0-0'])->get($videoUrl),
        ];

        foreach ($requests as $request) {
            try {
                $response = $request();
            } catch (Throwable) {
                continue;
            }

            if (!in_array($response->status(), [200, 206], true)) {
                continue;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $normalizedType = trim(strtok($contentType, ';') ?: '');

            if (in_array($normalizedType, ['video/mp4', 'application/mp4'], true)) {
                return true;
            }
        }

        return false;
    }

    private function shouldRetryFacebookVideoAsUpload(string $responseBody): bool
    {
        if ($responseBody === '') {
            return false;
        }

        return str_contains($responseBody, '"code":389')
            || str_contains($responseBody, '"error_subcode":1363057')
            || str_contains(strtolower($responseBody), 'unable to fetch video file from url');
    }

    private function resolveUploadableVideo(string $videoUrl, ?string $videoPath): ?array
    {
        $localVideo = $this->resolveLocalVideoUpload($videoPath);
        if ($localVideo) {
            return $localVideo;
        }

        return $this->downloadVideoToTempFile($videoUrl);
    }

    private function resolveLocalVideoUpload(?string $videoPath): ?array
    {
        if (!$videoPath) {
            return null;
        }

        if (!Storage::disk('public')->exists($videoPath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($videoPath);
        $stream = @fopen($absolutePath, 'r');
        if (!is_resource($stream)) {
            return null;
        }

        return [
            'stream' => $stream,
            'filename' => basename($absolutePath),
            'cleanup_path' => null,
        ];
    }

    private function downloadVideoToTempFile(string $videoUrl): ?array
    {
        try {
            $response = Http::timeout(90)->withOptions(['allow_redirects' => true])->get($videoUrl);
        } catch (Throwable) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($contentType !== '' && !str_contains($contentType, 'video/mp4') && !str_contains($contentType, 'application/mp4')) {
            return null;
        }

        $content = $response->body();
        if ($content === '') {
            return null;
        }

        $tempPath = storage_path('app/tmp/'.Str::uuid()->toString().'.mp4');
        @mkdir(dirname($tempPath), 0775, true);
        file_put_contents($tempPath, $content);

        $stream = @fopen($tempPath, 'r');
        if (!is_resource($stream)) {
            @unlink($tempPath);
            return null;
        }

        return [
            'stream' => $stream,
            'filename' => basename($tempPath),
            'cleanup_path' => $tempPath,
        ];
    }
}
