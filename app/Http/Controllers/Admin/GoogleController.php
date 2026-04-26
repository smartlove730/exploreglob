<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReauthorizationRequiredException;
use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use App\Services\GoogleService;
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
        $accounts = GoogleAccount::query()
            ->where('user_id', Auth::id())
            ->withCount('locations')
            ->orderBy('reauthorization_required')
            ->orderByDesc('token_last_refreshed_at')
            ->orderByDesc('updated_at')
            ->get();

        $account = $accounts->first();
        $businessProfiles = $accounts->filter(
            fn (GoogleAccount $googleAccount) => str_starts_with($googleAccount->google_account_id, 'accounts/')
        )->values();
        $locations = GoogleLocation::query()
            ->where('user_id', Auth::id())
            ->with('googleAccount')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('admin.google-business.settings', [
            'account' => $account,
            'accounts' => $accounts,
            'businessProfiles' => $businessProfiles,
            'locations' => $locations,
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

            try {
                $accounts = $this->googleService->fetchAccounts($accessToken);
            } catch (\Throwable) {
                $accounts = [];
            }
            $oauthIdentifier = (string) ($oauthUserInfo['sub'] ?? $oauthUserInfo['email'] ?? '');
            $accountNames = collect($accounts)->pluck('name')->filter()->values();
            if ($accountNames->isEmpty()) {
                $fallbackAccount = $oauthIdentifier !== '' ? "oauth:{$oauthIdentifier}" : 'oauth:default';
                $accountNames = collect([$fallbackAccount]);
            }

            $syncedCount = 0;
            foreach ($accountNames as $accountName) {
                $account = GoogleAccount::updateOrCreate(
                    ['user_id' => Auth::id(), 'google_account_id' => $accountName],
                    [
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken ?: GoogleAccount::query()
                            ->where('user_id', Auth::id())
                            ->where('google_account_id', $accountName)
                            ->value('refresh_token'),
                        'expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                        'token_last_refreshed_at' => now(),
                        'reauthorization_required' => false,
                        'reauthorization_reason' => null,
                    ]
                );

                if (str_starts_with((string) $accountName, 'accounts/')) {
                    $syncedCount += $this->googleService->syncLocations($account);
                }
            }

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

            return redirect()->route($settingsRoute)->with('success', "Google connected successfully. Synced {$syncedCount} location(s).");
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed', ['error' => $exception->getMessage()]);

            return redirect()->route($settingsRoute)->with('error', 'Google connection failed. Please try again.');
        }
    }

    public function syncLocations(): RedirectResponse
    {
        $accounts = GoogleAccount::query()
            ->where('user_id', Auth::id())
            ->where('google_account_id', 'like', 'accounts/%')
            ->orderByDesc('token_last_refreshed_at')
            ->get();

        if ($accounts->isEmpty()) {
            return back()->with('error', 'Connect Google Business first.');
        }

        try {
            $count = 0;
            foreach ($accounts as $account) {
                if ($account->reauthorization_required) {
                    continue;
                }

                $count += $this->googleService->syncLocations($account);
            }

            return back()->with('success', "Synced {$count} location(s).");
        } catch (ReauthorizationRequiredException $exception) {
            GoogleAccount::query()
                ->where('user_id', Auth::id())
                ->where('google_account_id', 'like', 'accounts/%')
                ->update([
                    'reauthorization_required' => true,
                    'reauthorization_reason' => $exception->getMessage(),
                ]);

            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return back()->with('error', 'Unable to sync Google locations: '.$exception->getMessage());
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
}
