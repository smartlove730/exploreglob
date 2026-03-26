<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookAccount;
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

    public function index()
    {
        $account = FacebookAccount::with('pages')
            ->where('user_id', Auth::id())
            ->first();

        return view('admin.facebook.settings', [
            'account' => $account,
            'pages' => $account?->pages ?? collect(),
        ]);
    }

    public function redirectToFacebook(): RedirectResponse
    {
        return redirect()->away($this->facebookGraphService->getOAuthRedirectUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $shortToken = $this->facebookGraphService->exchangeCodeForToken($request->string('code')->toString());
            $tokenData = $this->facebookGraphService->exchangeForLongLivedToken($shortToken);

            $account = FacebookAccount::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'long_lived_user_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]
            );

            $pages = $this->facebookGraphService->fetchManagedPages($tokenData['access_token']);
            $this->facebookGraphService->upsertPages($account, $pages);

            $target = \Illuminate\Support\Facades\Route::has('admin.facebook.settings')
                ? route('admin.facebook.settings')
                : url('/admin/facebook/settings');

            return redirect($target)->with('success', 'Facebook account connected and pages synced successfully.');
        } catch (Throwable $exception) {
            Log::error('Facebook OAuth callback failed', ['error' => $exception->getMessage()]);

            $target = \Illuminate\Support\Facades\Route::has('admin.facebook.settings')
                ? route('admin.facebook.settings')
                : url('/admin/facebook/settings');

            return redirect($target)->with('error', 'Facebook connection failed. Please try again.');
        }
    }

    public function syncPages(): RedirectResponse
    {
        $account = FacebookAccount::where('user_id', Auth::id())->first();

        if (!$account) {
            return back()->with('error', 'Connect Facebook first.');
        }

        try {
            $pages = $this->facebookGraphService->fetchManagedPages($account->long_lived_user_token);
            $this->facebookGraphService->upsertPages($account, $pages);

            return back()->with('success', 'Facebook pages synced.');
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

        $account->pages()->update(['is_active' => false]);
        $page->update(['is_active' => true]);

        return back()->with('success', 'Active page updated.');
    }
}
