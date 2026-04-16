<?php

namespace App\Services;

use App\Models\FacebookPage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class InstagramService
{
    private string $apiVersion = 'v19.0';

    public function fetchInstagramBusinessAccountId(string $pageId, string $pageAccessToken): ?string
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$pageId}", [
            'fields' => 'instagram_business_account',
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to fetch Instagram business account: '.$response->body());
        }

        return data_get($response->json(), 'instagram_business_account.id');
    }

    public function ensureInstagramBusinessAccountId(FacebookPage $page): string
    {
        $instagramBusinessAccountId = $page->instagram_business_account_id
            ?? $this->fetchInstagramBusinessAccountId($page->page_id, $page->page_access_token);

        if (!$instagramBusinessAccountId) {
            throw new RuntimeException('Instagram business account is not linked to this Facebook page.');
        }

        if ($page->instagram_business_account_id !== $instagramBusinessAccountId) {
            $page->forceFill(['instagram_business_account_id' => $instagramBusinessAccountId])->save();
        }

        return $instagramBusinessAccountId;
    }

    public function fetchInstagramUsername(string $igUserId, string $pageAccessToken): ?string
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}", [
            'fields' => 'username',
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to fetch Instagram username: '.$response->body());
        }

        return data_get($response->json(), 'username');
    }

    public function createMediaContainer(string $igUserId, string $pageAccessToken, string $imageUrl, string $caption, bool $isCarouselItem = false): string
    {
        $payload = [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $pageAccessToken,
        ];

        if ($isCarouselItem) {
            $payload['is_carousel_item'] = 'true';
            unset($payload['caption']);
        }

        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", $payload);

        if (!$response->ok() || !isset($response['id'])) {
            throw new RuntimeException('Unable to create Instagram media container: '.$response->body());
        }

        return (string) $response['id'];
    }

    public function createCarouselContainer(string $igUserId, string $pageAccessToken, array $children, string $caption): string
    {
        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $children),
            'caption' => $caption,
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok() || !isset($response['id'])) {
            throw new RuntimeException('Unable to create Instagram carousel container: '.$response->body());
        }

        return (string) $response['id'];
    }

    public function publishMedia(string $igUserId, string $pageAccessToken, string $creationId): array
    {
        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to publish Instagram media: '.$response->body());
        }

        return $response->json();
    }

    public function publishImageWithCaption(FacebookPage $page, string $imageUrl, string $caption, int $publishDelaySeconds = 3): array
    {
        $igUserId = $this->ensureInstagramBusinessAccountId($page);
        $creationId = $this->createMediaContainer($igUserId, $page->page_access_token, $imageUrl, $caption);

        sleep(max(2, min(5, $publishDelaySeconds)));

        $published = $this->publishMedia($igUserId, $page->page_access_token, $creationId);

        return [
            'creation_id' => $creationId,
            'publish_response' => $published,
        ];
    }

    public function publishCarouselWithCaption(FacebookPage $page, array $imageUrls, string $caption, int $publishDelaySeconds = 3): array
    {
        if (count($imageUrls) < 2) {
            throw new RuntimeException('Instagram carousel requires at least two images.');
        }

        $igUserId = $this->ensureInstagramBusinessAccountId($page);
        $children = [];

        foreach ($imageUrls as $imageUrl) {
            $children[] = $this->createMediaContainer($igUserId, $page->page_access_token, $imageUrl, $caption, true);
        }

        sleep(max(2, min(6, $publishDelaySeconds)));

        $carouselContainerId = $this->createCarouselContainer($igUserId, $page->page_access_token, $children, $caption);

        sleep(max(2, min(6, $publishDelaySeconds)));

        $published = $this->publishMedia($igUserId, $page->page_access_token, $carouselContainerId);

        return [
            'children_creation_ids' => $children,
            'creation_id' => $carouselContainerId,
            'publish_response' => $published,
        ];
    }
}
