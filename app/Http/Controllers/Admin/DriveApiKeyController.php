<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Services\GoogleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DriveApiKeyController extends Controller
{
    public function __construct(private readonly GoogleService $googleService)
    {
    }

    public function index()
    {
        $keys = $this->scopedQuery()->orderByDesc('created_at')->paginate(20);

        return view('admin.google-drive-keys.index', compact('keys'));
    }

    public function create()
    {
        return view('admin.google-drive-keys.create');
    }

    public function redirectToGoogleOauth(): RedirectResponse
    {
        try {
            return redirect()->away(
                $this->googleService->getDriveOauthRedirectUrl(route('admin.google-drive.callback'))
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['user_id'] = Auth::id();

        DriveApiKey::create($data);

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google account connection added successfully.');
    }

    public function edit(DriveApiKey $google_drive_key)
    {
        $this->authorizeModel($google_drive_key);

        return view('admin.google-drive-keys.edit', ['driveKey' => $google_drive_key]);
    }

    public function update(Request $request, DriveApiKey $google_drive_key): RedirectResponse
    {
        $this->authorizeModel($google_drive_key);

        $data = $this->validateData($request);
        $google_drive_key->update($data);

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google account connection updated successfully.');
    }

    public function destroy(DriveApiKey $google_drive_key): RedirectResponse
    {
        $this->authorizeModel($google_drive_key);

        $google_drive_key->delete();

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google account connection removed successfully.');
    }

    public function callback(Request $request)
    {
        $oauthError = (string) $request->query('error', '');
        if ($oauthError !== '') {
            return redirect()
                ->route('admin.facebook.google-drive-keys.index')
                ->with('error', 'Google OAuth failed: '.$oauthError);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('admin.facebook.google-drive-keys.index')
                ->with('error', 'Missing OAuth authorization code from Google callback.');
        }

        try {
            $tokenData = [];
            $driveRedirectUri = route('admin.google-drive.callback');

            try {
                $tokenData = $this->googleService->exchangeCodeForToken($code, $driveRedirectUri);
            } catch (\Throwable $exception) {
                Log::warning('Drive OAuth token exchange failed with explicit drive callback; retrying default redirect URI.', [
                    'error' => $exception->getMessage(),
                ]);

                $tokenData = $this->googleService->exchangeCodeForToken($code);
            }

            $accessToken = (string) ($tokenData['access_token'] ?? '');
            $refreshToken = (string) ($tokenData['refresh_token'] ?? '');

            if ($accessToken === '') {
                return redirect()
                    ->route('admin.facebook.google-drive-keys.index')
                    ->with('error', 'Google OAuth did not return an access token.');
            }

            $oauthUserInfo = [];
            try {
                $oauthUserInfo = $this->googleService->fetchOauthUserInfo($accessToken);
            } catch (\Throwable) {
                $oauthUserInfo = [];
            }

            $oauthEmail = (string) ($oauthUserInfo['email'] ?? Auth::user()?->email ?? '');
            $oauthDisplayName = (string) ($oauthUserInfo['name'] ?? '');
            $fallbackName = $oauthEmail !== '' ? $oauthEmail : ('Google OAuth '.Auth::id());
            $driveKeyName = $oauthDisplayName !== '' ? "{$oauthDisplayName} ({$fallbackName})" : $fallbackName;

            $driveApiKeyUpdateData = [
                'user_id' => Auth::id(),
                'name' => $driveKeyName,
                'email' => $oauthEmail !== '' ? $oauthEmail : Auth::user()?->email,
                'description' => 'Connected via Google OAuth',
                'is_active' => true,
            ];

            if (Schema::hasColumns('drive_api_keys', ['oauth_access_token', 'oauth_refresh_token', 'oauth_expires_at'])) {
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
            }

            DriveApiKey::updateOrCreate(
                ['user_id' => Auth::id(), 'email' => $oauthEmail !== '' ? $oauthEmail : Auth::user()?->email],
                $driveApiKeyUpdateData
            );

            return redirect()
                ->route('admin.posts.create')
                ->with('success', 'Google Drive OAuth connected. You can now fetch media by pasting a folder link.');
        } catch (\Throwable $exception) {
            Log::error('Google Drive OAuth callback failed', [
                'error' => $exception->getMessage(),
                'error_class' => $exception::class,
            ]);

            return redirect()
                ->route('admin.facebook.google-drive-keys.index')
                ->with('error', 'Unable to connect Google Drive OAuth. '.$exception->getMessage());
        }
    }

    private function scopedQuery()
    {
        return DriveApiKey::query()->ownedBy(Auth::user());
    }

    private function authorizeModel(DriveApiKey $driveApiKey): void
    {
        if (!Auth::user()?->isAdmin() && $driveApiKey->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|email|max:255',
            'redirect_url' => 'nullable|url|max:2048',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
