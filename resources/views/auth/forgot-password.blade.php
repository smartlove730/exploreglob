@extends('layouts.auth')

@section('title', 'Forgot Password — Postzy')

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
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                    </div>
                    <h1 class="auth-title">Forgot password?</h1>
                    <p class="auth-subtitle">No worries! Enter your email and we'll send you a reset link.</p>
                </div>

                <!-- Success Alert -->
                @if (session('status'))
                <div class="auth-alert auth-alert-success">
                    <svg class="auth-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <div class="auth-alert-content">
                        <div class="auth-alert-message">{{ session('status') }}</div>
                    </div>
                    <button class="auth-alert-dismiss" type="button">&times;</button>
                </div>
                @endif

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
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="auth-form-group">
                        <label class="auth-form-label" for="auth-email">Email address</label>
                        <div class="auth-input-wrapper">
                            <input type="email" id="auth-email" class="auth-input" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit-btn">Send reset link</button>
                </form>

                <!-- Footer Links -->
                <div class="auth-footer-links" style="margin-top: 1.5rem;">
                    <a href="{{ route('login') }}">← Back to login</a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
