<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - Postzy</title>
    <link rel="icon" type="image/png" href="{{asset('images/postzy-favicon.png')}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --pz-indigo: #6366f1;
            --pz-indigo-dark: #4f46e5;
            --pz-indigo-light: #818cf8;
            --pz-slate-50: #f8fafc;
            --pz-slate-100: #f1f5f9;
            --pz-slate-400: #94a3b8;
            --pz-slate-600: #475569;
            --pz-slate-800: #1e293b;
            --pz-slate-900: #0f172a;
            --pz-gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --pz-gradient-hero: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--pz-gradient-hero);
            position: relative;
            overflow: hidden;
            padding: 1rem;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(99,102,241,0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(168,85,247,0.2) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(236,72,153,0.15) 0%, transparent 50%);
            animation: auroraShift 12s ease-in-out infinite alternate;
        }

        @keyframes auroraShift {
            0% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.05); }
            100% { opacity: 0.7; transform: scale(1.02); }
        }

        body::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .verify-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(255,255,255,0.2);
            max-width: 480px;
            width: 100%;
            padding: 3rem 2.5rem;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: cardSlideUp 0.6s cubic-bezier(0.25,0.46,0.45,0.94) forwards;
        }

        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verify-icon {
            width: 80px;
            height: 80px;
            background: var(--pz-gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.25rem;
            box-shadow: 0 8px 25px rgba(99,102,241,0.3);
        }

        .verify-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--pz-slate-900);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .verify-text {
            color: var(--pz-slate-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .verify-email-address {
            font-weight: 700;
            color: var(--pz-indigo);
        }

        .btn-verify {
            background: var(--pz-gradient-primary);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.95rem;
            color: white;
            transition: all 0.3s cubic-bezier(0.25,0.46,0.45,0.94);
            box-shadow: 0 4px 15px rgba(99,102,241,0.25);
            width: 100%;
            cursor: pointer;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99,102,241,0.35);
        }

        .btn-verify:active { transform: translateY(0); }

        .btn-logout {
            background: transparent;
            border: 1.5px solid var(--pz-slate-400);
            padding: 0.6rem 1.5rem;
            border-radius: 9999px;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--pz-slate-600);
            transition: all 0.3s ease;
            width: 100%;
            cursor: pointer;
            margin-top: 0.75rem;
        }

        .btn-logout:hover {
            border-color: var(--pz-slate-600);
            color: var(--pz-slate-800);
            background: var(--pz-slate-100);
        }

        .alert-verify {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            margin-bottom: 1.5rem;
        }

        .brand-text {
            font-size: 1.25rem;
            font-weight: 800;
            background: var(--pz-gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--pz-slate-400), transparent);
            margin: 1.5rem 0;
            opacity: 0.3;
        }

        @media (max-width: 576px) {
            .verify-card {
                padding: 2rem 1.5rem;
            }
            .verify-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <a href="{{ url('/') }}" class="brand-link">
            <span class="brand-text">Postzy</span>
        </a>

        <div class="verify-icon">
            ✉️
        </div>

        <h1 class="verify-title">Verify Your Email</h1>

        <p class="verify-text">
            We've sent a verification link to
            <span class="verify-email-address">{{ Auth::user()->email ?? 'your email' }}</span>.
            Please check your inbox and click the link to activate your account.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert-verify">
                ✅ A fresh verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-verify">
                Resend Verification Email
            </button>
        </form>

        <div class="divider"></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                ← Back to Login
            </button>
        </form>
    </div>
</body>
</html>
