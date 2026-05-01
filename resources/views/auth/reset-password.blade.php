@extends('layouts.auth')

@section('title', 'Reset Password — Postzy')

@section('content')
<section class="auth-section">
    <div class="auth-particles">
        <span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span>
    </div>

    <div class="auth-card-wrapper">
        <div class="auth-card">
            <div class="auth-card-body">

                <!-- Header -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h1 class="auth-title">Reset password</h1>
                    <p class="auth-subtitle">Choose a new, strong password for your account.</p>
                </div>

                <!-- Error Alert -->
                @if ($errors->any())
                <div class="auth-alert auth-alert-error">
                    <svg class="auth-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div class="auth-alert-content">
                        <div class="auth-alert-title">Something went wrong</div>
                        <div class="auth-alert-message">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button class="auth-alert-dismiss" type="button">&times;</button>
                </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-email">Email address</label>
                        <div class="auth-input-wrapper">
                            <input type="email" id="auth-email" class="auth-input" name="email" value="{{ old('email', $request->email) }}" placeholder="you@example.com" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-password">New password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" id="auth-password" class="auth-input" name="password" placeholder="Enter new password" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <button type="button" class="auth-password-toggle" aria-label="Toggle password visibility">
                                <svg class="auth-eye-show" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="auth-eye-hide" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="auth-password-strength">
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                        </div>
                        <div class="auth-password-strength-text"></div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-password-confirm">Confirm new password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" id="auth-password-confirm" class="auth-input" name="password_confirmation" placeholder="Confirm your password" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit-btn">Reset password</button>
                </form>

            </div>
        </div>
    </div>
</section>
@endsection
