<?php

namespace App\Services;

use App\Exceptions\ReauthorizationRequiredException;
use App\Models\DriveApiKey;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use RuntimeException;

class GoogleService
{
    private const OAUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REFRESH_SKEW_SECONDS = 300;

    public function __construct(private readonly Client $client = new Client())
    {
    }

    public function getOauthRedirectUrl(): string
    {
        $clientId = $this->resolveClientId();
        $redirectUri = $this->resolveRedirectUri();

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/business.manage',
                'https://www.googleapis.com/auth/drive.readonly',
                'openid',
                'email',
                'profile',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ]);

        return self::OAUTH_URL.'?'.$query;
    }

    public function getDriveOauthRedirectUrl(?string $redirectUri = null): string
    {
        $clientId = $this->resolveClientId();
        $resolvedRedirectUri = $redirectUri ?: $this->resolveRedirectUri();

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $resolvedRedirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/drive.readonly',
                'openid',
                'email',
                'profile',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ]);

        return self::OAUTH_URL.'?'.$query;
    }

    public function exchangeCodeForToken(string $code, ?string $redirectUri = null): array
    {
        $clientId = $this->resolveClientId();
        $clientSecret = $this->resolveClientSecret();
        $resolvedRedirectUri = $redirectUri ?: $this->resolveRedirectUri();

        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $resolvedRedirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function resolveRedirectUri(): string
    {
        $configuredRedirectUri = (string) config('services.google.redirect_uri', '');
        if ($configuredRedirectUri !== '') {
            return $configuredRedirectUri;
        }

        if (function_exists('route')) {
            return route('oauth.google.callback');
        }

        throw new RuntimeException('Google redirect URI is not configured.');
    }


    private function resolveClientId(): string
    {
        $clientId = (string) config('services.google.client_id', '');
        if ($clientId !== '') {
            return $clientId;
        }

        throw new RuntimeException('Google client ID is not configured. Set GOOGLE_CLIENT_ID or GOOGLE_DRIVE_CLIENT_ID.');
    }

    private function resolveClientSecret(): string
    {
        $clientSecret = (string) config('services.google.client_secret', '');
        if ($clientSecret !== '') {
            return $clientSecret;
        }

        throw new RuntimeException('Google client secret is not configured. Set GOOGLE_CLIENT_SECRET or GOOGLE_DRIVE_CLIENT_SECRET.');
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $clientId = $this->resolveClientId();
        $clientSecret = $this->resolveClientSecret();

        $response = $this->client->post(self::TOKEN_URL, [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ],
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function ensureValidGoogleAccountToken(GoogleAccount $account): GoogleAccount
    {
        if ($account->expires_at && $account->expires_at->gt(now()->addSeconds(self::REFRESH_SKEW_SECONDS))) {
            return $account;
        }

        if (!$account->refresh_token) {
            throw new RuntimeException('Google token expired and no refresh token is available. Reconnect your Google account.');
        }

        try {
            $tokenData = $this->refreshAccessToken($account->refresh_token);
        } catch (GuzzleException $exception) {
            if ($this->isInvalidGrant($exception)) {
                $account->update([
                    'reauthorization_required' => true,
                    'reauthorization_reason' => 'Google connection expired or was revoked. Please reconnect your Google account.',
                ]);
                throw new ReauthorizationRequiredException('Google connection expired or was revoked. Please reconnect your Google account.');
            }

            throw $exception;
        }

        $account->update([
            'access_token' => Arr::get($tokenData, 'access_token', $account->access_token),
            'expires_at' => now()->addSeconds((int) Arr::get($tokenData, 'expires_in', 3600)),
            'refresh_token' => Arr::get($tokenData, 'refresh_token', $account->refresh_token),
            'token_last_refreshed_at' => now(),
            'reauthorization_required' => false,
            'reauthorization_reason' => null,
        ]);

        return $account->fresh();
    }

    public function ensureValidDriveToken(DriveApiKey $driveApiKey): DriveApiKey
    {
        if ($driveApiKey->oauth_access_token && $driveApiKey->oauth_expires_at?->gt(now()->addSeconds(self::REFRESH_SKEW_SECONDS))) {
            return $driveApiKey;
        }

        if (!$driveApiKey->oauth_refresh_token) {
            throw new RuntimeException('Google Drive OAuth token is missing. Connect a Google account from Drive Keys.');
        }

        try {
            $tokenData = $this->refreshAccessToken($driveApiKey->oauth_refresh_token);
        } catch (GuzzleException $exception) {
            if ($this->isInvalidGrant($exception)) {
                $driveApiKey->update([
                    'oauth_reauthorization_required' => true,
                    'oauth_reauthorization_reason' => 'Google Drive authorization expired. Reconnect Google from Drive Keys.',
                ]);
                throw new ReauthorizationRequiredException('Google Drive authorization expired. Reconnect Google from Drive Keys.');
            }

            throw $exception;
        }

        $driveApiKey->update([
            'oauth_access_token' => Arr::get($tokenData, 'access_token', $driveApiKey->oauth_access_token),
            'oauth_refresh_token' => Arr::get($tokenData, 'refresh_token', $driveApiKey->oauth_refresh_token),
            'oauth_expires_at' => now()->addSeconds((int) Arr::get($tokenData, 'expires_in', 3600)),
            'oauth_token_last_refreshed_at' => now(),
            'oauth_reauthorization_required' => false,
            'oauth_reauthorization_reason' => null,
        ]);

        return $driveApiKey->fresh();
    }

    public function fetchAccounts(string $accessToken): array
    {
        $response = $this->client->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts', [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
        ]);

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return (array) ($payload['accounts'] ?? []);
    }

    public function fetchOauthUserInfo(string $accessToken): array
    {
        $response = $this->client->get('https://openidconnect.googleapis.com/v1/userinfo', [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function fetchLocations(string $accessToken, string $accountName): array
    {
        $response = $this->client->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", [
            'headers' => ['Authorization' => 'Bearer '.$accessToken],
            'query' => ['readMask' => 'name,title'],
        ]);

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return (array) ($payload['locations'] ?? []);
    }

    public function syncLocations(GoogleAccount $googleAccount): int
    {
        $googleAccount = $this->ensureValidGoogleAccountToken($googleAccount);
        $locations = $this->fetchLocations($googleAccount->access_token, $googleAccount->google_account_id);

        GoogleLocation::query()->where('google_account_id', $googleAccount->id)->delete();

        $inserted = 0;
        foreach ($locations as $index => $location) {
            $locationName = (string) Arr::get($location, 'name');
            if ($locationName === '') {
                continue;
            }

            GoogleLocation::create([
                'user_id' => $googleAccount->user_id,
                'google_account_id' => $googleAccount->id,
                'location_id' => $locationName,
                'name' => (string) Arr::get($location, 'title', $locationName),
                'is_default' => $index === 0,
            ]);
            $inserted++;
        }

        return $inserted;
    }

    public function ensureGoogleAccountForUser(int $userId): ?GoogleAccount
    {
        $googleAccount = GoogleAccount::query()->where('user_id', $userId)->latest('id')->first();
        if ($googleAccount) {
            return $this->ensureValidGoogleAccountToken($googleAccount);
        }

        $driveApiKey = DriveApiKey::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('oauth_access_token')
                    ->orWhereNotNull('oauth_refresh_token');
            })
            ->latest('id')
            ->first();

        if (!$driveApiKey) {
            return null;
        }

        $driveApiKey = $this->ensureValidDriveToken($driveApiKey);
        if (!$driveApiKey->oauth_access_token) {
            return null;
        }

        $accounts = $this->fetchAccounts($driveApiKey->oauth_access_token);
        $primaryAccount = (string) Arr::get($accounts, '0.name', '');
        if ($primaryAccount === '') {
            return null;
        }

        return GoogleAccount::updateOrCreate(
            ['user_id' => $userId, 'google_account_id' => $primaryAccount],
            [
                'access_token' => $driveApiKey->oauth_access_token,
                'refresh_token' => $driveApiKey->oauth_refresh_token,
                'expires_at' => $driveApiKey->oauth_expires_at ?: now()->addHour(),
                'token_last_refreshed_at' => now(),
                'reauthorization_required' => false,
                'reauthorization_reason' => null,
            ]
        );
    }

    public function syncLocationsForUser(int $userId): int
    {
        $googleAccount = $this->ensureGoogleAccountForUser($userId);
        if (!$googleAccount) {
            throw new RuntimeException('No Google Business account found for this user. Reconnect Google with business.manage scope.');
        }

        return $this->syncLocations($googleAccount);
    }

    public function listBusinessProfilesForUser(int $userId): array
    {
        $googleAccount = $this->ensureGoogleAccountForUser($userId);
        if (!$googleAccount) {
            return [];
        }

        $connectedEmail = (string) (DriveApiKey::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('oauth_access_token')
                    ->orWhereNotNull('oauth_refresh_token');
            })
            ->latest('id')
            ->value('email') ?? '');

        $googleAccount = $this->ensureValidGoogleAccountToken($googleAccount);
        $accounts = $this->fetchAccounts($googleAccount->access_token);

        return collect($accounts)->map(function (array $account) use ($googleAccount, $connectedEmail) {
            $accountName = (string) Arr::get($account, 'name', '');
            $locations = [];

            if ($accountName !== '') {
                $locations = $this->fetchLocations($googleAccount->access_token, $accountName);
            }

            return [
                'connected_email' => $connectedEmail,
                'account' => $account,
                'locations' => $locations,
            ];
        })->values()->all();
    }

    public function uploadLocationMedia(string $accessToken, string $locationId, string $sourceUrl): string
    {
        $response = $this->client->post("https://mybusiness.googleapis.com/v4/{$locationId}/media", [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'mediaFormat' => 'PHOTO',
                'sourceUrl' => $sourceUrl,
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return (string) Arr::get($payload, 'googleUrl', $sourceUrl);
    }

    public function publishLocalPost(GoogleAccount $googleAccount, string $locationId, string $caption, string $sourceUrl): array
    {
        $googleAccount = $this->ensureValidGoogleAccountToken($googleAccount);
        $mediaUrl = $this->uploadLocationMedia($googleAccount->access_token, $locationId, $sourceUrl);

        $response = $this->client->post("https://mybusiness.googleapis.com/v4/{$locationId}/localPosts", [
            'headers' => [
                'Authorization' => 'Bearer '.$googleAccount->access_token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'summary' => $caption,
                'media' => [[
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $mediaUrl,
                ]],
                'topicType' => 'STANDARD',
                'callToAction' => [
                    'actionType' => 'LEARN_MORE',
                    'url' => config('app.url'),
                ],
            ],
        ]);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function isInvalidGrant(GuzzleException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains(strtolower($message), 'invalid_grant');
    }
}
