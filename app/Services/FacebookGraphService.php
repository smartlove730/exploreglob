<?php

namespace App\Services;

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

    public function __construct(private readonly InstagramService $instagramService)
    {
    }

    public function getOAuthRedirectUrl(FacebookApp $app): string
    {
        $query = http_build_query([
            'client_id' => $app->app_id,
            'redirect_uri' => $app->redirect_uri,
            'scope' => 'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish',
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
            throw new RuntimeException('Unable to refresh long-lived Facebook token.');
        }

        return [
            'access_token' => $response['access_token'],
            'expires_in' => (int) ($response['expires_in'] ?? 0),
        ];
    }

    public function fetchManagedPages(string $longLivedToken): array
    {
        $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/me/accounts", [
            'access_token' => $longLivedToken,
        ]);

        if (!$response->ok()) {
            throw new RuntimeException('Unable to fetch Facebook pages.');
        }

        return (array) $response->json('data', []);
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
            throw new RuntimeException('Facebook posting API call failed: '.$response->body());
        }

        return $response->json();
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
                    'facebook_app_id' => $account->facebook_app_id,
                    'page_name' => $page['name'],
                    'page_access_token' => $page['access_token'],
                    'instagram_business_account_id' => $instagramBusinessAccountId,
                    'is_active' => true,
                ]
            );
        }
    }
}
