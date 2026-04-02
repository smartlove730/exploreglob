<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | ExploreGlob</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v={{ file_exists(public_path('css/marketing.css')) ? filemtime(public_path('css/marketing.css')) : time() }}">
    <style>
        body{font-family:'Inter',sans-serif;}
        .auth-wrap{min-height:100vh;display:grid;place-items:center;padding:2rem 1rem;}
        .auth-shell{width:min(980px,96vw);display:grid;grid-template-columns:1.1fr .9fr;gap:1rem;}
        .auth-card{background:#fff;border:1px solid #dce3ff;border-radius:1rem;padding:1.3rem;box-shadow:0 18px 35px rgba(17,24,39,.06);}
        .auth-title{font-size:2rem;line-height:1.1;margin:.3rem 0 1rem;font-weight:900;}
        .auth-input{width:100%;padding:.72rem .78rem;border:1px solid #cfd8ff;border-radius:.65rem;}
        .auth-label{display:block;font-size:.88rem;font-weight:600;margin-bottom:.4rem;color:#2f3a5e;}
        .auth-actions{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap;}
        @media (max-width: 860px){.auth-shell{grid-template-columns:1fr;}}
    </style>
</head>
<body class="marketing-body">
    <div class="auth-wrap">
        <div class="auth-shell">
            <section class="auth-card">
                <p class="eyebrow">Welcome back</p>
                <h1 class="auth-title">Log in to your ExploreGlob account</h1>
                <p class="lead">Access your social calendar, approvals, analytics, and automation workflows.</p>

                @if (session('status'))
                    <p class="band" style="background:#ecfeff;border-color:#a5f3fc;color:#155e75;">{{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <div class="band" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
                        <ul style="margin:0;padding-left:1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" style="display:grid;gap:.9rem;">
                    @csrf
                    <div>
                        <label class="auth-label" for="email">Email</label>
                        <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required>
                    </div>
                    <div>
                        <label class="auth-label" for="password">Password</label>
                        <input id="password" class="auth-input" type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <label style="display:flex;gap:.5rem;align-items:center;font-size:.9rem;color:#4b567a;">
                        <input type="checkbox" name="remember" value="1"> Remember me
                    </label>
                    <button type="submit" class="cta-btn cta-btn-primary" style="width:max-content;">Log in</button>
                </form>

                <div class="auth-actions" style="margin-top:1rem;">
                    <a href="{{ route('password.request') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">Forgot password?</a>
                    <a href="{{ route('register') }}" style="color:#1f2a4d;text-decoration:none;font-weight:600;">Create account</a>
                </div>
            </section>

            <aside class="highlight" style="display:grid;align-content:center;gap:.8rem;">
                <p class="eyebrow" style="color:#e0e7ff;">ExploreGlob</p>
                <h2 style="font-size:1.7rem;font-weight:800;line-height:1.2;">Consistent social publishing starts here.</h2>
                <p>Plan content once, collaborate with your team, and auto-publish on schedule across channels.</p>
                <ul style="margin:0;padding-left:1rem;color:#e9edff;display:grid;gap:.35rem;">
                    <li>Visual campaign calendar</li>
                    <li>Approval workflow and role controls</li>
                    <li>Cross-platform auto publishing</li>
                </ul>
                <a href="{{ route('marketing.home') }}" class="cta-btn cta-btn-secondary" style="width:max-content;margin-top:.4rem;">Back to website</a>
            </aside>
        </div>
    </div>
</body>
</html>
