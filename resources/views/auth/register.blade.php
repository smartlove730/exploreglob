@extends('layouts.auth')

@section('title', 'Create Account — Postzy')

@section('content')
<section class="auth-section">
    <div class="auth-particles">
        <span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span>
    </div>

    <div class="auth-card-wrapper auth-card-wide">
        <div class="auth-card">
            <div class="auth-card-body">

                <!-- Header -->
                <div class="auth-header">
                    <div class="auth-icon">
                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    </div>
                    <h1 class="auth-title">Create your account</h1>
                    <p class="auth-subtitle">Start publishing and manage plans in one place.</p>
                </div>

                <!-- Error Alert -->
                @if ($errors->any())
                <div class="auth-alert auth-alert-error">
                    <svg class="auth-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div class="auth-alert-content">
                        <div class="auth-alert-title">Please fix the following</div>
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
                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-name">Full name</label>
                        <div class="auth-input-wrapper">
                            <input type="text" id="auth-name" class="auth-input" name="name" value="{{ old('name') }}" placeholder="John Doe" required autofocus>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-email">Email address</label>
                        <div class="auth-input-wrapper">
                            <input type="email" id="auth-email" class="auth-input" name="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-password">Password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" id="auth-password" class="auth-input" name="password" placeholder="Create a strong password" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <button type="button" class="auth-password-toggle" aria-label="Toggle password visibility">
                                <svg class="auth-eye-show" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="auth-eye-hide" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <!-- Password Strength -->
                        <div class="auth-password-strength">
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                            <div class="auth-password-strength-bar"></div>
                        </div>
                        <div class="auth-password-strength-text"></div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-password-confirm">Confirm password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" id="auth-password-confirm" class="auth-input" name="password_confirmation" placeholder="Confirm your password" required>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit-btn">Create account</button>
                </form>

                <!-- Social Login -->
                <div class="auth-divider">or sign up with</div>
                <div class="auth-social-buttons">
                    <a href="{{ route('auth.google') }}" class="auth-social-btn">
                        <svg viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        Continue with Google
                    </a>
                    <a href="{{ route('auth.facebook.login') }}" class="auth-social-btn">
                        <svg viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Continue with Facebook
                    </a>
                </div>

                <!-- Footer Links -->
                <div class="auth-footer-links">
                    Already have an account? <a href="{{ route('login') }}">Log in</a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
