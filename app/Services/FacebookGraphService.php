<?php

namespace App\Services;

use App\Exceptions\ReauthorizationRequiredException;
use App\Models\FacebookAccount;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FacebookGraphService
{
    private string $apiVersion = 'v19.0';
    private const OAUTH_SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'business_management',
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_contents',
    ];

    public function __construct(private readonly InstagramService $instagramService)
    {
    }

    public function getOAuthRedirectUrl(FacebookApp $app): string
    {
        $query = http_build_query([
            'client_id' => $app->app_id,
            'redirect_uri' => $app->redirect_uri,
            'scope' => implode(',', self::OAUTH_SCOPES),
            'auth_type' => 'rerequest',
        ]);

        return "https://www.facebook.com/{$this->apiVersion}/dialog/oauth?{$query}";
    }

    public function exchangeCodeForToken(FacebookApp $app, string $code): string
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'client_id' => $app->app_id,
            'client_secret' => $app->app_secret,
            'redirect_uri' => $app->redirect_uri,
            'code' => $code,
        ]);

        if (!$response->ok() || !isset($response['access_token'])) {
            throw new RuntimeException('Unable to exchange Facebook authorization code.');
        }

        return $response['access_token'];
    }

    public function exchangeForLongLivedToken(FacebookApp $app, string $shortToken): array
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $app->app_id,
            'client_secret' => $app->app_secret,
            'fb_exchange_token' => $shortToken,
        ]);

        if (!$response->ok() || !isset($response['access_token'])) {
            throw new RuntimeException('Unable to generate long-lived Facebook token.');
        }

        return [
            'access_token' => $response['access_token'],
            'expires_in' => (int) ($response['expires_in'] ?? 0),
        ];
    }

    public function refreshLongLivedToken(FacebookAccount $account): array
    {
        if (!$account->app) {
            throw new RuntimeException('Facebook account is not linked to an app.');
        }

        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $account->app->app_id,
            'client_secret' => $account->app->app_secret,
            'fb_exchange_token' => $account->long_lived_user_token,
        ]);

        if (!$response->ok() || !isset($response['access_token'])) {
            try {
                $this->throwRefreshException($response->json());
            } catch (ReauthorizationRequiredException $exception) {
                $account->update([
                    'reauthorization_required' => true,
                    'reauthorization_reason' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }

        return [
            'access_token' => $response['access_token'],
            'expires_in' => (int) ($response['expires_in'] ?? 0),
        ];
    }

    public function fetchManagedPages(string $longLivedToken): array
    {
        $pages = [];
        $nextUrl = "https://graph.facebook.com/{$this->apiVersion}/me/accounts";
        $query = [
            'access_token' => $longLivedToken,
            'limit' => 100,
            'fields' => 'id,name,access_token,tasks',
        ];
        $requestCount = 0;

        while ($nextUrl && $requestCount < 20) {
            $response = Http::get($nextUrl, $query);
            $requestCount++;

            if (!$response->ok()) {
                $this->throwRefreshException($response->json(), 'Unable to fetch Facebook pages.');
            }

            $batch = (array) $response->json('data', []);
            if (!empty($batch)) {
                $pages = array_merge($pages, $batch);
            }

            $nextUrl = (string) $response->json('paging.next', '');
            $query = [];
        }

        return $pages;
    }

    public function publishToPage(FacebookPage $page, string $message, ?string $imageUrl = null): array
    {
        $endpoint = $imageUrl ? 'photos' : 'feed';
        $payload = [
            'access_token' => $page->page_access_token,
            'message' => $message,
        ];

        if ($imageUrl) {
            $payload['url'] = $imageUrl;
        }

        $response = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/{$endpoint}", $payload);

        if (!$response->ok()) {
            $this->throwRefreshException($response->json(), 'Facebook posting API call failed.');
        }

        return $response->json();
    }

    public function publishMultiImagePost(FacebookPage $page, string $message, array $imageUrls): array
    {
        if (empty($imageUrls)) {
            throw new RuntimeException('At least one image is required for multi-image Facebook posting.');
        }

        $mediaIds = [];

        foreach ($imageUrls as $imageUrl) {
            $uploadResponse = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/photos", [
                'access_token' => $page->page_access_token,
                'url' => $imageUrl,
                'published' => 'false',
            ]);

            if (!$uploadResponse->ok() || !isset($uploadResponse['id'])) {
                $this->throwRefreshException($uploadResponse->json(), 'Facebook multi-image upload failed.');
            }

            $mediaIds[] = (string) $uploadResponse['id'];
        }

        $payload = [
            'access_token' => $page->page_access_token,
            'message' => $message,
        ];

        foreach ($mediaIds as $index => $mediaId) {
            $payload["attached_media[{$index}]"] = json_encode(['media_fbid' => $mediaId], JSON_THROW_ON_ERROR);
        }

        $publishResponse = Http::asForm()->post("https://graph.facebook.com/{$this->apiVersion}/{$page->page_id}/feed", $payload);

        if (!$publishResponse->ok()) {
            $this->throwRefreshException($publishResponse->json(), 'Facebook multi-image publish failed.');
        }

        return $publishResponse->json();
    }

    public function upsertPages(FacebookAccount $account, array $pages): void
    {
        foreach ($pages as $page) {
            if (!isset($page['id'], $page['name'], $page['access_token'])) {
                continue;
            }

            $instagramBusinessAccountId = null;

            try {
                $instagramBusinessAccountId = $this->instagramService->fetchInstagramBusinessAccountId(
                    (string) $page['id'],
                    (string) $page['access_token']
                );
            } catch (Throwable $exception) {
                Log::warning('Unable to resolve Instagram business account for Facebook page', [
                    'facebook_account_id' => $account->id,
                    'page_id' => $page['id'],
                    'error' => $exception->getMessage(),
                ]);
            }

            $account->pages()->updateOrCreate(
                ['page_id' => $page['id']],
                [
                    'user_id' => $account->user_id,
                    'facebook_app_id' => $account->facebook_app_id,
                    'page_name' => $page['name'],
                    'page_access_token' => $page['access_token'],
                    'instagram_business_account_id' => $instagramBusinessAccountId,
                    'is_active' => true,
                ]
            );
        }
    }

    private function throwRefreshException(?array $payload = null, string $fallback = 'Facebook API request failed.'): void
    {
        $errorCode = (int) data_get($payload, 'error.code', 0);
        $subCode = (int) data_get($payload, 'error.error_subcode', 0);
        $message = (string) data_get($payload, 'error.message', $fallback);

        if ($errorCode === 190 || in_array($subCode, [458, 459, 460, 463, 464, 467], true)) {
            throw new ReauthorizationRequiredException('Facebook connection expired or was revoked. Please reconnect your Facebook account.');
        }

        throw new RuntimeException($fallback.' '.$message);
    }
}
