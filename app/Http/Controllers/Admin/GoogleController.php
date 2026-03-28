<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use App\Services\GoogleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    public function __construct(private readonly GoogleService $googleService)
    {
    }

    public function index()
    {
        $account = GoogleAccount::query()->where('user_id', Auth::id())->with('locations')->first();

        return view('admin.google-business.settings', [
            'account' => $account,
            'locations' => $account?->locations ?? collect(),
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

        try {
            $tokenData = $this->googleService->exchangeCodeForToken((string) request('code'));
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            $refreshToken = (string) ($tokenData['refresh_token'] ?? '');

            if ($accessToken === '') {
                return redirect()->route('admin.google.settings')->with('error', 'Unable to connect Google account.');
            }

            $accounts = $this->googleService->fetchAccounts($accessToken);
            $primaryAccount = $accounts[0]['name'] ?? null;
            if (!$primaryAccount) {
                return redirect()->route('admin.google.settings')->with('error', 'No Google Business account found.');
            }

            $account = GoogleAccount::updateOrCreate(
                ['user_id' => Auth::id(), 'google_account_id' => $primaryAccount],
                [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                ]
            );

            DriveApiKey::updateOrCreate(
                ['user_id' => Auth::id(), 'name' => 'OAuth ('.Auth::user()->email.')'],
                [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'description' => 'Connected via Google OAuth',
                    'is_active' => true,
                    'oauth_access_token' => $accessToken,
                    'oauth_refresh_token' => $refreshToken,
                    'oauth_expires_at' => now()->addSeconds((int) ($tokenData['expires_in'] ?? 3600)),
                ]
            );

            $count = $this->googleService->syncLocations($account);

            return redirect()->route('admin.google.settings')->with('success', "Google connected successfully. Synced {$count} location(s).");
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed', ['error' => $exception->getMessage()]);

            return redirect()->route('admin.google.settings')->with('error', 'Google connection failed. Please try again.');
        }
    }

    public function syncLocations(): RedirectResponse
    {
        $account = GoogleAccount::query()->where('user_id', Auth::id())->first();
        if (!$account) {
            return back()->with('error', 'Connect Google Business first.');
        }

        try {
            $count = $this->googleService->syncLocations($account);

            return back()->with('success', "Synced {$count} location(s).");
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
