<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ReauthorizationRequiredException;
use App\Http\Controllers\Controller;
use App\Models\FacebookAccount;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Services\FacebookGraphService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacebookSettingsController extends Controller
{
    public function __construct(private readonly FacebookGraphService $facebookGraphService)
    {
    }

    public function index(Request $request)
    {
        $apps = FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();
        $selectedAppId = (int) $request->integer('app_id');

        if ($selectedAppId <= 0) {
            $selectedAppId = (int) ($apps->first()?->id ?? $this->resolveDefaultApp()?->id ?? 0);
        }

        $accountQuery = FacebookAccount::with(['pages', 'app'])
            ->where('user_id', Auth::id());

        if ($selectedAppId > 0) {
            $accountQuery->where('facebook_app_id', $selectedAppId);
        }

        $account = $accountQuery->first();

        return view('admin.facebook.settings', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'account' => $account,
            'pages' => $account?->pages ?? collect(),
        ]);
    }

    public function redirectToFacebook(Request $request): RedirectResponse
    {
        $appId = $request->integer('app_id');
        $app = $this->resolveConnectApp($appId);

        if (!$app) {
            return back()->with('error', 'No active Facebook app is configured. Please check your Facebook env settings.');
        }

        session(['facebook_auth_app_id' => $app->id]);

        return redirect()->away($this->facebookGraphService->getOAuthRedirectUrl($app));
    }

    public function callback(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $appId = (int) session('facebook_auth_app_id');
        $app = FacebookApp::query()->where('is_active', true)->findOrFail($appId);

        try {
            $shortToken = $this->facebookGraphService->exchangeCodeForToken($app, $request->string('code')->toString());
            $tokenData = $this->facebookGraphService->exchangeForLongLivedToken($app, $shortToken);

            $account = FacebookAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'facebook_app_id' => $app->id,
                ],
                [
                    'long_lived_user_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    'token_last_refreshed_at' => now(),
                    'reauthorization_required' => false,
                    'reauthorization_reason' => null,
                ]
            );

            $pages = $this->facebookGraphService->fetchManagedPages($tokenData['access_token']);
            $this->facebookGraphService->upsertPages($account, $pages);
            session()->forget('facebook_auth_app_id');

            $target = route(Auth::user()?->isAdmin() ? 'admin.facebook.settings' : 'app.facebook.settings', ['app_id' => $app->id]);

            return redirect($target)->with('success', 'Facebook account connected and pages synced successfully.');
        } catch (Throwable $exception) {
            Log::error('Facebook OAuth callback failed', ['error' => $exception->getMessage()]);

            $target = route(Auth::user()?->isAdmin() ? 'admin.facebook.settings' : 'app.facebook.settings', ['app_id' => $app->id]);

            return redirect($target)->with('error', 'Facebook connection failed. Please try again.');
        }
    }

    public function syncPages(Request $request): RedirectResponse
    {
        $appId = (int) $request->integer('app_id');

        if ($appId <= 0) {
            $appId = (int) ($this->resolveDefaultApp()?->id ?? 0);
        }

        $account = FacebookAccount::where('user_id', Auth::id())
            ->where('facebook_app_id', $appId)
            ->first();

        if (!$account) {
            return back()->with('error', 'Connect Facebook first.');
        }

        try {
            if ($account->reauthorization_required) {
                return back()->with('error', 'Facebook needs to be reconnected before syncing pages.');
            }

            if (!$account->token_expires_at || $account->token_expires_at->lte(now()->addDay())) {
                $tokenData = $this->facebookGraphService->refreshLongLivedToken($account);
                $account->update([
                    'long_lived_user_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                    'token_last_refreshed_at' => now(),
                    'reauthorization_required' => false,
                    'reauthorization_reason' => null,
                ]);
                $account = $account->fresh();
            }

            $pages = $this->facebookGraphService->fetchManagedPages($account->long_lived_user_token);
            $this->facebookGraphService->upsertPages($account, $pages);

            return back()->with('success', 'Facebook pages synced.');
        } catch (ReauthorizationRequiredException $exception) {
            $account->update([
                'reauthorization_required' => true,
                'reauthorization_reason' => $exception->getMessage(),
            ]);

            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Facebook pages sync failed', ['error' => $exception->getMessage()]);

            return back()->with('error', 'Unable to sync pages right now.');
        }
    }

    public function activatePage(FacebookPage $page): RedirectResponse
    {
        $account = FacebookAccount::where('id', $page->facebook_account_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $page->update(['is_active' => true]);

        return back()->with('success', 'Page marked as active.');
    }

    private function resolveConnectApp(int $appId = 0): ?FacebookApp
    {
        if ($appId > 0) {
            return FacebookApp::query()
                ->where('is_active', true)
                ->whereKey($appId)
                ->first();
        }

        return $this->resolveDefaultApp();
    }

    private function resolveDefaultApp(): ?FacebookApp
    {
        $ownedApp = FacebookApp::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($ownedApp) {
            return $ownedApp;
        }

        $envAppId = (string) config('services.facebook.app_id');
        $envAppSecret = (string) config('services.facebook.app_secret');
        $envRedirectUri = (string) config('services.facebook.redirect_uri');

        if ($envAppId === '' || $envAppSecret === '' || $envRedirectUri === '') {
            return null;
        }

        $app = FacebookApp::query()->firstOrCreate(
            ['app_id' => $envAppId],
            [
                'user_id' => Auth::id(),
                'name' => 'Default Facebook App',
                'app_secret' => $envAppSecret,
                'redirect_uri' => $envRedirectUri,
                'is_active' => true,
            ]
        );

        $app->update([
            'app_secret' => $envAppSecret,
            'redirect_uri' => $envRedirectUri,
            'is_active' => true,
        ]);

        return $app->fresh();
    }
}
