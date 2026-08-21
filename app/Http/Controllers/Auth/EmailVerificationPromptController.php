<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        // Auto-send OTP on first visit if user doesn't have a valid (non-expired) OTP
        if (
            ! $request->user()->email_verification_otp
            || ! $request->user()->email_verification_otp_expires_at
            || now()->greaterThan($request->user()->email_verification_otp_expires_at)
        ) {
            $request->user()->sendEmailVerificationNotification();
            session()->flash('status', 'verification-otp-sent');
        }

        return view('auth.verify-email');
    }
}
