<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReauthorizationRequiredException;
use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use App\Services\GoogleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GoogleController extends Controller
{
    public function __construct(private readonly GoogleService $googleService)
    {
    }

    public function index()
    {
        $account = GoogleAccount::query()->where('user_id', Auth::id())->with('locations')->first();
        $connectedDriveAccounts = DriveApiKey::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('oauth_access_token')
                    ->orWhereNotNull('oauth_refresh_token');
            })
            ->select(['id', 'name', 'email'])
            ->latest('id')
            ->get();

        $connectedEmail = (string) ($connectedDriveAccounts->first()?->email ?? '');

        $profiles = GoogleAccount::query()
            ->where('user_id', Auth::id())
            ->with('locations')
            ->get()
            ->map(function (GoogleAccount $googleAccount) use ($connectedEmail) {
                return [
                    'connected_email' => $connectedEmail,
                    'account' => [
                        'name' => $googleAccount->google_account_id,
                        'accountName' => $googleAccount->google_account_id,
                        'type' => 'ACCOUNT',
                    ],
                    'locations' => $googleAccount->locations
                        ->map(fn (GoogleLocation $location) => [
                            'name' => $location->location_id,
                            'title' => $location->name,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return view('admin.google-business.settings', [
            'account' => $account,
            'locations' => $account?->locations ?? collect(),
            'profiles' => $profiles,
            'connectedDriveAccounts' => $connectedDriveAccounts,
        ]);
    }

    public function redirect(): RedirectResponse
    {
        try {
            return redirect()->away($this->googleService->getOauthRedirectUrl());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function callback(): RedirectResponse
    {
        request()->validate(['code' => 'required|string']);
        $settingsRoute = Auth::user()?->isAdmin() ? 'admin.google.settings' : 'app.google.settings';

        try {
            $tokenData = $this->googleService->exchangeCodeForToken((string) request('code'));
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            $refreshToken = (string) ($tokenData['refresh_token'] ?? '');
            $oauthUserInfo = [];

            if ($accessToken === '') {
                return redirect()->route($settingsRoute)->with('error', 'Unable to connect Google account.');
            }

            try {
                $oauthUserInfo = $this->googleService->fetchOauthUserInfo($accessToken);
            } catch (\Throwable) {
                $oauthUserInfo = [];
            }

            $accounts = $this->googleService->fetchAccounts($accessToken);
            $primaryAccount = $accounts[0]['name'] ?? null;
            if (!$primaryAccount) {
                return redirect()->route($settingsRoute)->with('error', 'No Google Business account found.');
            }

            $account = GoogleAccount::updateOrCreate(
                ['user_id' => Auth::id(), 'google_account_id' => $primaryAccount],
                [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken ?: GoogleAccount::query()
                        ->where('user_id', Auth::id())
                        ->where('google_account_id', $primaryAccount)
                        ->value('refresh_token'),
                    'expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                    'token_last_refreshed_at' => now(),
                    'reauthorization_required' => false,
                    'reauthorization_reason' => null,
                ]
            );

            $oauthEmail = (string) ($oauthUserInfo['email'] ?? Auth::user()?->email ?? '');
            $oauthDisplayName = (string) ($oauthUserInfo['name'] ?? '');
            $fallbackName = $oauthEmail !== '' ? $oauthEmail : ('Google OAuth '.Auth::id());
            $driveKeyName = $oauthDisplayName !== '' ? "{$oauthDisplayName} ({$fallbackName})" : $fallbackName;
            $supportsDriveOauthColumns = Schema::hasColumns('drive_api_keys', [
                'oauth_access_token',
                'oauth_refresh_token',
                'oauth_expires_at',
            ]);

            $driveApiKeyUpdateData = [
                'user_id' => Auth::id(),
                'name' => $driveKeyName,
                'email' => $oauthEmail !== '' ? $oauthEmail : Auth::user()?->email,
                'description' => 'Connected via Google OAuth',
                'is_active' => true,
            ];

            if ($supportsDriveOauthColumns) {
                $driveApiKeyUpdateData = array_merge($driveApiKeyUpdateData, [
                    'oauth_access_token' => $accessToken,
                    'oauth_refresh_token' => $refreshToken ?: DriveApiKey::query()
                        ->where('user_id', Auth::id())
                        ->where('email', $oauthEmail !== '' ? $oauthEmail : Auth::user()?->email)
                        ->value('oauth_refresh_token'),
                    'oauth_expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                ]);

                if (Schema::hasColumn('drive_api_keys', 'oauth_token_last_refreshed_at')) {
                    $driveApiKeyUpdateData['oauth_token_last_refreshed_at'] = now();
                }

                if (Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_required')) {
                    $driveApiKeyUpdateData['oauth_reauthorization_required'] = false;
                }

                if (Schema::hasColumn('drive_api_keys', 'oauth_reauthorization_reason')) {
                    $driveApiKeyUpdateData['oauth_reauthorization_reason'] = null;
                }
            } else {
                $driveApiKeyUpdateData['description'] = 'Connected via Google OAuth. Please run latest migrations to persist Drive OAuth tokens.';
            }

            DriveApiKey::updateOrCreate(
                ['user_id' => Auth::id(), 'email' => $oauthEmail !== '' ? $oauthEmail : Auth::user()?->email],
                $driveApiKeyUpdateData
            );

            $count = $this->googleService->syncLocations($account);

            return redirect()->route($settingsRoute)->with('success', "Google connected successfully. Synced {$count} location(s).");
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed', ['error' => $exception->getMessage()]);

            return redirect()->route($settingsRoute)->with('error', 'Google connection failed. Please try again.');
        }
    }

    public function syncLocations(): RedirectResponse
    {
        $data = request()->validate([
            'drive_api_key_id' => 'nullable|integer|exists:drive_api_keys,id',
        ]);

        try {
            $account = GoogleAccount::query()->where('user_id', Auth::id())->first();
            if ($account?->reauthorization_required) {
                return back()->with('error', 'Google needs to be reconnected before syncing locations.');
            }

            $count = $this->googleService->syncLocationsForUser((int) Auth::id(), (int) ($data['drive_api_key_id'] ?? 0) ?: null);

            return back()->with('success', "Synced {$count} location(s).");
        } catch (ReauthorizationRequiredException $exception) {
            if (isset($account) && $account) {
                $account->update([
                    'reauthorization_required' => true,
                    'reauthorization_reason' => $exception->getMessage(),
                ]);
            }

            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            if ($this->isGoogleQuotaError($exception)) {
                $cachedCount = GoogleLocation::query()->where('user_id', Auth::id())->count();
                if ($cachedCount > 0) {
                    return back()->with('success', "Google quota is currently limited. Loaded {$cachedCount} cached location(s) from your last successful sync.");
                }

                return back()->with('error', 'Google quota limit reached (429). No cached businesses available yet. Please wait about 1 minute and try again.');
            }

            return back()->with('error', 'Unable to sync Google locations: '.$exception->getMessage());
        }
    }

    public function listProfiles(): JsonResponse
    {
        try {
            $profiles = $this->googleService->listBusinessProfilesForUser((int) Auth::id());

            return response()->json([
                'success' => !empty($profiles),
                'data' => [
                    'profiles' => $profiles,
                ],
                'message' => empty($profiles)
                    ? 'No Google Business profiles found for your user.'
                    : 'Google Business profiles loaded successfully.',
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch Google Business profiles: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function setDefaultLocation(GoogleLocation $location): RedirectResponse
    {
        abort_unless($location->user_id === Auth::id(), 403);

        GoogleLocation::query()
            ->where('user_id', Auth::id())
            ->update(['is_default' => false]);

        $location->update(['is_default' => true]);

        return back()->with('success', 'Default Google Business location updated.');
    }

    private function isGoogleQuotaError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, '429')
            || str_contains($message, 'quota exceeded')
            || str_contains($message, 'too many requests');
    }
}
