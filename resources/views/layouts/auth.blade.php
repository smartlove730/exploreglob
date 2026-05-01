<!DOCTYPE html>
<html lang="en">
<head>
    @yield('SeoTags')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{asset('images/postzy-favicon.png')}}">
    <title>@yield('title', 'Postzy')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/auth.css') }}">
</head>
<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm" id="mainNavbar">
  <div class="container">
      <a class="navbar-brand fw-bold" href="{{ url('/') }}">
        @if(file_exists(public_path('images/postzy-logo.png')))
        <img src="{{ asset('images/postzy-logo.png') }}" alt="Postzy logo" loading="eager" decoding="async">
        @endif
        Postzy
      </a>

    <div class="d-flex align-items-center gap-2">
        @if(!request()->routeIs('login'))
        <a href="{{ route('login') }}" class="btn btn-sm" style="color: var(--pz-slate-600); font-weight: 500;">Log in</a>
        @endif
        @if(!request()->routeIs('register') && Route::has('register'))
        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign up</a>
        @endif
    </div>
  </div>
</nav>
</header>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container position-relative" style="z-index:1">
        <div class="row g-4">
            <div class="col-12 col-md-6">
                <a href="{{ url('/') }}" class="footer-brand text-decoration-none d-inline-flex align-items-center gap-2 mb-3">
                    @if(file_exists(public_path('images/postzy-logo.png')))
                    <img src="{{ asset('images/postzy-logo.png') }}" alt="Postzy logo" class="footer-logo">
                    @endif
                    <span class="footer-brand-text">Postzy</span>
                </a>
                <p class="footer-muted mt-2" style="font-size:0.85rem;">Your premium destination for discovering stories, guides, and insights from around the world.</p>
            </div>

            <div class="col-12 col-md-6">
                <h5 class="footer-title">Company</h5>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="{{ url('/about') }}">About Us</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                    <li><a href="{{ url('/policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ url('/terms') }}">Terms & Conditions</a></li>
                </ul>
            </div>
        </div>
        <p class="footer-copy mb-0">&copy; {{ date('Y') }} Postzy. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Password visibility toggle
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.auth-password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrapper = btn.closest('.auth-input-wrapper');
            const input = wrapper.querySelector('.auth-input');
            if (input.type === 'password') {
                input.type = 'text';
                wrapper.classList.add('auth-password-visible');
            } else {
                input.type = 'password';
                wrapper.classList.remove('auth-password-visible');
            }
        });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.auth-alert-dismiss').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('.auth-alert');
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 300);
        });
    });

    // Password strength indicator
    const passwordInput = document.getElementById('auth-password');
    const strengthBars = document.querySelectorAll('.auth-password-strength-bar');
    const strengthText = document.querySelector('.auth-password-strength-text');

    if (passwordInput && strengthBars.length) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score++;

            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const classes = ['', 'weak', 'fair', 'good', 'strong'];

            strengthBars.forEach((bar, i) => {
                bar.classList.remove('active', 'weak', 'fair', 'good', 'strong');
                if (i < score) {
                    bar.classList.add('active', classes[score]);
                }
            });

            if (strengthText) {
                strengthText.textContent = val.length > 0 ? labels[score] || '' : '';
            }
        });
    }

    // Navbar scroll effect
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });
    }
});
</script>
</body>
</html>
