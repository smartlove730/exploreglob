<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Explore Glob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #eef2ff, #f8fafc); }
        .auth-card { max-width: 460px; border: 0; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card auth-card w-100">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Welcome back</h1>
            <p class="text-muted mb-4">Login to manage your content and billing.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Log in</button>
            </form>

            @if(!empty($facebookLoginAppId))
                <div class="text-center text-muted my-3">or</div>
                <button type="button" id="facebook_login_btn" class="btn btn-outline-primary w-100">Continue with Facebook</button>
            @endif

            <div class="d-flex justify-content-between mt-3 small">
                <a href="{{ route('password.request') }}">Forgot password?</a>
                <a href="{{ route('register') }}">Create account</a>
            </div>
        </div>
    </div>
</body>

@if(!empty($facebookLoginAppId))
<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: '{{ $facebookLoginAppId }}',
            cookie: true,
            xfbml: false,
            version: 'v20.0'
        });
    };

    (function (d, s, id) {
        if (d.getElementById(id)) {
            return;
        }

        const js = d.createElement(s);
        js.id = id;
        js.src = 'https://connect.facebook.net/en_US/sdk.js';
        d.head.appendChild(js);
    }(document, 'script', 'facebook-jssdk'));

    const facebookLoginButton = document.getElementById('facebook_login_btn');
    if (facebookLoginButton) {
        facebookLoginButton.addEventListener('click', function () {
            if (typeof FB === 'undefined') {
                alert('Facebook SDK is still loading. Please try again in a moment.');
                return;
            }

            FB.login(function (response) {
                const token = response?.authResponse?.accessToken;
                if (!token) {
                    alert('Facebook login was cancelled or failed.');
                    return;
                }

                fetch('{{ route('login.facebook-sdk') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ access_token: token }),
                    credentials: 'same-origin',
                })
                .then(async (res) => {
                    const payload = await res.json();
                    if (!res.ok) {
                        throw new Error(payload.message || 'Facebook login failed.');
                    }

                    window.location.assign(payload.redirect || '{{ route('admin.dashboard') }}');
                })
                .catch((error) => {
                    alert(error.message || 'Facebook login failed.');
                });
            }, { scope: 'email,public_profile,pages_show_list,pages_read_engagement,pages_manage_posts,pages_manage_metadata' });
        });
    }
</script>
@endif
</html>
