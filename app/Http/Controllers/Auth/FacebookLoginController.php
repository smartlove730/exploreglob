<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class FacebookLoginController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('status', 'Facebook login failed. Please try again.');
        }

        $user = User::where('facebook_id', $facebookUser->getId())->first();

        if (!$user) {
            // Some facebook accounts might not have an email or it is not provided
            $email = $facebookUser->getEmail();
            
            if ($email) {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                $user->update([
                    'facebook_id' => $facebookUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                $user = User::create([
                    'name' => $facebookUser->getName() ?? 'Facebook User',
                    'email' => $email ?? ($facebookUser->getId() . '@facebook.com'),
                    'facebook_id' => $facebookUser->getId(),
                    'email_verified_at' => now(),
                    'role' => User::ROLE_CUSTOMER,
                ]);
            }
        } elseif (!$user->hasVerifiedEmail()) {
            $user->update(['email_verified_at' => now()]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('admin.dashboard'));
    }
}
