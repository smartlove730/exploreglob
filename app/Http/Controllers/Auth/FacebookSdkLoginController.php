<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\FacebookApp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FacebookSdkLoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'access_token' => 'required|string',
        ]);

        $app = FacebookApp::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('is_admin', true)->orWhere('role', User::ROLE_ADMIN))
            ->orderBy('name')
            ->first();

        if (!$app) {
            return response()->json(['message' => 'Facebook login is currently unavailable.'], 422);
        }

        $debugResponse = Http::get('https://graph.facebook.com/debug_token', [
            'input_token' => $data['access_token'],
            'access_token' => $app->app_id.'|'.$app->app_secret,
        ]);

        $debugData = $debugResponse->json('data', []);
        if (!$debugResponse->successful() || !($debugData['is_valid'] ?? false) || (string) ($debugData['app_id'] ?? '') !== (string) $app->app_id) {
            return response()->json(['message' => 'Invalid Facebook token. Please try again.'], 422);
        }

        $profileResponse = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $data['access_token'],
        ]);

        if (!$profileResponse->successful()) {
            return response()->json(['message' => 'Unable to fetch Facebook profile.'], 422);
        }

        $profile = $profileResponse->json();
        $facebookId = (string) ($profile['id'] ?? '');
        if ($facebookId === '') {
            return response()->json(['message' => 'Facebook did not return a valid account ID.'], 422);
        }

        $email = strtolower((string) ($profile['email'] ?? ''));
        if ($email === '') {
            $email = 'facebook_'.$facebookId.'@exploreglob.local';
        }

        $user = User::query()
            ->where('facebook_id', $facebookId)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => (string) ($profile['name'] ?? 'Facebook User'),
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'role' => User::ROLE_CUSTOMER,
                'is_admin' => false,
                'facebook_id' => $facebookId,
                'facebook_avatar' => data_get($profile, 'picture.data.url'),
                'email_verified_at' => now(),
            ]);
        } else {
            $user->update([
                'facebook_id' => $facebookId,
                'facebook_avatar' => data_get($profile, 'picture.data.url') ?: $user->facebook_avatar,
                'name' => $user->name ?: (string) ($profile['name'] ?? 'Facebook User'),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Facebook login successful.',
            'redirect' => route('admin.dashboard'),
        ]);
    }
}

