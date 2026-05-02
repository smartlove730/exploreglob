<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $otp = (string) random_int(100000, 999999);
        $request->user()->forceFill([
            'email_verification_otp' => $otp,
            'email_verification_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Log::info('Email verification OTP generated.', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
            'expires_at' => $request->user()->email_verification_otp_expires_at?->toDateTimeString(),
        ]);

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-otp-sent');
    }
}
