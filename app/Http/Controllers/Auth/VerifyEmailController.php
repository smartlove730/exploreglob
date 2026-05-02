<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        if (
            $request->user()->email_verification_otp !== $request->string('otp')->toString()
            || ! $request->user()->email_verification_otp_expires_at
            || now()->greaterThan($request->user()->email_verification_otp_expires_at)
        ) {
            Log::warning('Email verification OTP validation failed.', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
            ]);

            throw ValidationException::withMessages([
                'otp' => 'The OTP is invalid or expired. Please request a new one.',
            ]);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $request->user()->forceFill([
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ])->save();

        Log::info('Email verified successfully using OTP.', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
        ]);

        return redirect()->route('dashboard')->with('status', 'Email verified successfully.');
    }
}
