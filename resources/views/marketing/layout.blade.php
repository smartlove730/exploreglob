<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/avif" href="{{ asset('e.avif') }}">
    <title>@yield('title', 'ExploreGlob | Social Media Automation')</title>
    <meta name="description" content="ExploreGlob helps businesses and creators automate Facebook and Instagram posting, collaboration, and analytics from one dashboard.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/marketing.css') }}?v={{ file_exists(public_path('css/marketing.css')) ? filemtime(public_path('css/marketing.css')) : time() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="marketing-body">
    <header class="site-header">
        <div class="site-shell">
            <nav>
                <a href="{{ route('marketing.home') }}" class="brand">
                    <span class="brand-badge">EG</span>
                    <span>ExploreGlob</span>
                </a>

                <div class="main-nav">
                    <a href="{{ route('marketing.features') }}" class="{{ request()->routeIs('marketing.features') ? 'is-active' : '' }}">Features</a>
                    <a href="{{ route('marketing.pricing') }}" class="{{ request()->routeIs('marketing.pricing') ? 'is-active' : '' }}">Pricing</a>
                    <a href="{{ route('marketing.about') }}" class="{{ request()->routeIs('marketing.about') ? 'is-active' : '' }}">About</a>
                    <a href="{{ route('marketing.contact') }}" class="{{ request()->routeIs('marketing.contact') ? 'is-active' : '' }}">Contact</a>
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}" class="cta-btn cta-btn-primary">Start Free Trial</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="site-shell">@yield('content')</main>

    <footer class="site-footer">
        <div class="site-shell footer-grid">
            <div>
                <p><strong>ExploreGlob</strong></p>
                <p class="lead">Automation-first social media publishing for creators, teams, and agencies.</p>
            </div>
            <div>
                <p><strong>Product</strong></p>
                <a href="{{ route('marketing.features') }}">Features</a>
                <a href="{{ route('marketing.pricing') }}">Pricing</a>
                <a href="{{ route('marketing.integrations') }}">Integrations</a>
            </div>
            <div>
                <p><strong>Company</strong></p>
                <a href="{{ route('marketing.about') }}">About</a>
                <a href="{{ route('marketing.contact') }}">Contact</a>
                <a href="{{ route('marketing.security') }}">Security</a>
            </div>
            <div>
                <p><strong>Legal</strong></p>
                <a href="{{ route('marketing.privacy') }}">Privacy Policy</a>
                <a href="{{ route('marketing.terms') }}">Terms & Conditions</a>
            </div>
        </div>
        <div class="site-shell" style="padding-bottom:1.1rem;color:#6b7390;font-size:.84rem;">© {{ now()->year }} ExploreGlob. All rights reserved.</div>
    </footer>
</body>
</html>
