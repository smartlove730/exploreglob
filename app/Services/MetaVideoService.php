<?php

namespace App\Services;

use App\Models\FacebookPage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaVideoService
{
    private string $apiVersion = 'v19.0';

    public function __construct(private readonly InstagramService $instagramService)
    {
    }

    public function postToFacebookVideo(FacebookPage $page, string $videoUrl, string $caption): array
    {
        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/videos", [
            'file_url' => $videoUrl,
            'description' => $caption,
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to post Facebook video: '.$response->body());
        }

        return $response->json();
    }

    public function postToInstagramVideo(FacebookPage $page, string $videoUrl, string $caption, int $maxPollAttempts = 20, int $pollDelaySeconds = 4): array
    {
        $igUserId = $this->instagramService->ensureInstagramBusinessAccountId($page);

        $createResponse = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", [
            'media_type' => 'VIDEO',
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
                throw new RuntimeException("Instagram video processing failed with status: {$lastStatus}");
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
            'fields' => 'status_code',
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to check Instagram video status: '.$response->body());
        }

        return $response->json();
    }
}
