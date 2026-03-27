<?php

namespace App\Services;

use App\Models\FacebookPage;
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

    public function publish(FacebookPage $page, string $message, ?string $imageUrl, array $platforms): array
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

                $responses['instagram'] = $this->instagramService->publishImageWithCaption($page, $imageUrl, $message, 3);
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

        $response = Http::delete("https://graph.facebook.com/{$this->apiVersion}/{$instagramMediaId}", [
            'access_token' => $page->page_access_token,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to delete Instagram media: '.$response->body());
        }
    }
}
