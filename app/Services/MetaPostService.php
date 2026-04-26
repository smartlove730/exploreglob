<?php

namespace App\Services;

use App\Models\FacebookPage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaPostService
{
    private string $apiVersion = 'v19.0';

    public function __construct(
        private readonly FacebookGraphService $facebookGraphService,
        private readonly InstagramService $instagramService,
    ) {
    }

    public function publish(FacebookPage $page, string $message, ?string $imageUrl, array $platforms, ?int $googleLocationId = null): array
    {
        $responses = [];

        foreach ($platforms as $platform) {
            if ($platform === 'facebook') {
                $responses['facebook'] = $this->facebookGraphService->publishToPage($page, $message, $imageUrl);
                continue;
            }

            if ($platform === 'instagram') {
                if (!$imageUrl) {
                    throw new RuntimeException('Instagram publishing requires an image.');
                }

                try {
                    $responses['instagram'] = $this->instagramService->publishImageWithCaption($page, $imageUrl, $message, 3);
                } catch (RuntimeException $exception) {
                    if (!$this->shouldSkipInstagramPublish($exception)) {
                        throw $exception;
                    }

                    $responses['instagram'] = [
                        'status' => 'skipped',
                        'reason' => 'instagram_not_connected',
                        'message' => $exception->getMessage(),
                    ];
                }
                continue;
            }

        }

        return [
            'facebook_post_id' => data_get($responses, 'facebook.post_id') ?: data_get($responses, 'facebook.id'),
            'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
            'response_json' => $responses,
        ];
    }

    public function publishCombined(FacebookPage $page, string $message, array $imageUrls, array $platforms): array
    {
        if (empty($imageUrls)) {
            throw new RuntimeException('At least one image is required.');
        }

        if (count($imageUrls) === 1) {
            return $this->publish($page, $message, $imageUrls[0], $platforms);
        }

        $responses = [];

        foreach ($platforms as $platform) {
            if ($platform === 'facebook') {
                $responses['facebook'] = $this->facebookGraphService->publishMultiImagePost($page, $message, $imageUrls);
                continue;
            }

            if ($platform === 'instagram') {
                try {
                    $responses['instagram'] = $this->instagramService->publishCarouselWithCaption($page, $imageUrls, $message, 3);
                } catch (RuntimeException $exception) {
                    if (!$this->shouldSkipInstagramPublish($exception)) {
                        throw $exception;
                    }

                    $responses['instagram'] = [
                        'status' => 'skipped',
                        'reason' => 'instagram_not_connected',
                        'message' => $exception->getMessage(),
                    ];
                }
                continue;
            }

        }

        return [
            'facebook_post_id' => data_get($responses, 'facebook.post_id') ?: data_get($responses, 'facebook.id'),
            'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
            'response_json' => $responses,
        ];
    }

    public function deleteFacebookPost(FacebookPage $page, ?string $facebookPostId): void
    {
        if (!$facebookPostId) {
            return;
        }

        $response = Http::delete("https://graph.facebook.com/{$this->apiVersion}/{$facebookPostId}", [
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to delete Facebook post: '.$response->body());
        }
    }

    public function deleteInstagramMedia(FacebookPage $page, ?string $instagramMediaId): void
    {
        if (!$instagramMediaId) {
            return;
        }

        $response = $this->performInstagramDeleteRequest($instagramMediaId, $page->page_access_token);

        if ($response->ok()) {
            return;
        }

        $errorCode = (int) data_get($response->json(), 'error.code', 0);
        $isPermissionIssue = $errorCode === 10;
        $fallbackToken = (string) optional($page->facebookAccount)->long_lived_user_token;

        if ($isPermissionIssue && $fallbackToken !== '' && $fallbackToken !== $page->page_access_token) {
            $fallbackResponse = $this->performInstagramDeleteRequest($instagramMediaId, $fallbackToken);

            if ($fallbackResponse->ok()) {
                return;
            }

            throw new RuntimeException('Unable to delete Instagram media: '.$fallbackResponse->body());
        }

        throw new RuntimeException('Unable to delete Instagram media: '.$response->body());
    }

    private function performInstagramDeleteRequest(string $instagramMediaId, string $accessToken): Response
    {
        return Http::delete("https://graph.facebook.com/{$this->apiVersion}/{$instagramMediaId}", [
            'access_token' => $accessToken,
        ]);
    }

    private function shouldSkipInstagramPublish(RuntimeException $exception): bool
    {
        $error = mb_strtolower($exception->getMessage());

        return str_contains($error, 'instagram business account is not linked');
    }

    public function fetchFacebookPagePosts(FacebookPage $page, int $limit = 25): Collection
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/posts", [
            'fields' => 'id,message,created_time,full_picture,permalink_url',
            'limit' => $limit,
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to fetch Facebook posts: '.$response->body());
        }

        return collect($response->json('data', []))
            ->map(fn (array $post) => [
                'platform' => 'facebook',
                'external_post_id' => (string) ($post['id'] ?? ''),
                'page_id' => $page->id,
                'page_name' => $page->page_name,
                'content' => (string) ($post['message'] ?? ''),
                'media_preview_url' => $post['full_picture'] ?? null,
                'permalink' => $post['permalink_url'] ?? null,
                'created_time' => $post['created_time'] ?? null,
            ])
            ->filter(fn (array $post) => $post['external_post_id'] !== '')
            ->values();
    }

    public function fetchInstagramPosts(FacebookPage $page, int $limit = 25): Collection
    {
        $igUserId = $this->resolveInstagramBusinessAccountId($page);
        if (!$igUserId) {
            return collect();
        }

        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$igUserId}/media", [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,timestamp,permalink',
            'limit' => $limit,
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to fetch Instagram posts: '.$response->body());
        }

        return collect($response->json('data', []))
            ->map(fn (array $post) => [
                'platform' => 'instagram',
                'external_post_id' => (string) ($post['id'] ?? ''),
                'page_id' => $page->id,
                'page_name' => $page->page_name,
                'content' => (string) ($post['caption'] ?? ''),
                'media_preview_url' => $post['thumbnail_url'] ?? $post['media_url'] ?? null,
                'permalink' => $post['permalink'] ?? null,
                'created_time' => $post['timestamp'] ?? null,
            ])
            ->filter(fn (array $post) => $post['external_post_id'] !== '')
            ->values();
    }

    private function resolveInstagramBusinessAccountId(FacebookPage $page): ?string
    {
        if ($page->instagram_business_account_id) {
            return (string) $page->instagram_business_account_id;
        }

        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}", [
            'fields' => 'instagram_business_account{id}',
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            return null;
        }

        $igUserId = (string) data_get($response->json(), 'instagram_business_account.id', '');
        if ($igUserId === '') {
            return null;
        }

        $page->forceFill(['instagram_business_account_id' => $igUserId])->save();

        return $igUserId;
    }
}
