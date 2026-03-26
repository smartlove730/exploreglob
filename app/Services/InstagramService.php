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

    public function createMediaContainer(string $igUserId, string $pageAccessToken, string $imageUrl, string $caption): string
    {
        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $pageAccessToken,
        ]);

        if (!$response->ok() || !isset($response['id'])) {
            throw new RuntimeException('Unable to create Instagram media container: '.$response->body());
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
}
