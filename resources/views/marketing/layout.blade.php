<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Postzy')</title>
    <link rel="icon" type="image/png" href="{{asset('images/postzy-favicon.png')}}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('marketing.home') }}">
            <!-- <img src="{{ asset('images/postzy-logo.png') }}" alt="Postzy logo" loading="eager" decoding="async"> -->
            Postzy
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('marketing.features') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('marketing.pricing') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('marketing.about') }}">About</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('marketing.contact') }}">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                <li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2 mt-2 mt-lg-0" href="{{ route('register') }}">Get Started</a></li>
            </ul>
        </div>
    </div>
</nav>

<main style="padding-top: 80px;">
    <div class="container py-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
</main>

<footer class="site-footer">
    <div class="container position-relative" style="z-index:1">
        <div class="d-flex flex-wrap gap-3 justify-content-between small">
            <span>&copy; {{ now()->year }} Postzy</span>
            <div class="d-flex gap-3">
                <a href="{{ route('marketing.privacy') }}" class="text-decoration-none" style="color:var(--pz-slate-400)">Privacy Policy</a>
                <a href="{{ route('marketing.terms') }}" class="text-decoration-none" style="color:var(--pz-slate-400)">Terms & Conditions</a>
                <a href="{{ route('marketing.data-deletion') }}" class="text-decoration-none" style="color:var(--pz-slate-400)">Data Deletion</a>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });
    }
</script>
</body>
</html>
