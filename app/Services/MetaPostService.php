<?php

namespace App\Services;

use App\Models\FacebookPage;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaPostService
{
    private string $apiVersion = 'v19.0';

    public function __construct(
        private readonly FacebookGraphService $facebookGraphService,
        private readonly InstagramService $instagramService,
        private readonly GoogleService $googleService,
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

                $responses['instagram'] = $this->instagramService->publishImageWithCaption($page, $imageUrl, $message, 3);
                continue;
            }
            if ($platform === 'google_business') {
                if (!$imageUrl) {
                    throw new RuntimeException('Google Business publishing requires an HTTPS image URL.');
                }

                $googleAccount = \App\Models\GoogleAccount::query()->where('user_id', $page->facebookAccount->user_id)->first();
                $googleLocation = \App\Models\GoogleLocation::query()
                    ->where('user_id', $page->facebookAccount->user_id)
                    ->when($googleLocationId, fn ($query) => $query->whereKey($googleLocationId), fn ($query) => $query->where('is_default', true))
                    ->first();

                if (!$googleAccount || !$googleLocation) {
                    throw new RuntimeException('Google account or location is not configured.');
                }

                $responses['google_business'] = $this->googleService->publishLocalPost(
                    $googleAccount,
                    $googleLocation->location_id,
                    $message,
                    $imageUrl
                );
            }

        }

        return [
            'facebook_post_id' => data_get($responses, 'facebook.post_id') ?: data_get($responses, 'facebook.id'),
            'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
            'google_post_name' => data_get($responses, 'google_business.name'),
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
                $responses['instagram'] = $this->instagramService->publishCarouselWithCaption($page, $imageUrls, $message, 3);
                continue;
            }

            if ($platform === 'google_business') {
                $googleAccount = \App\Models\GoogleAccount::query()->where('user_id', $page->facebookAccount->user_id)->first();
                $googleLocation = \App\Models\GoogleLocation::query()->where('user_id', $page->facebookAccount->user_id)->where('is_default', true)->first();

                if (!$googleAccount || !$googleLocation) {
                    throw new RuntimeException('Google account or default location is not configured.');
                }

                $responses['google_business'] = $this->googleService->publishLocalPost($googleAccount, $googleLocation->location_id, $message, $imageUrls[0]);
            }
        }

        return [
            'facebook_post_id' => data_get($responses, 'facebook.post_id') ?: data_get($responses, 'facebook.id'),
            'instagram_media_id' => data_get($responses, 'instagram.publish_response.id'),
            'google_post_name' => data_get($responses, 'google_business.name'),
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
}
