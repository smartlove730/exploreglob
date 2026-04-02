<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ExploreGlob | Social Media Automation')</title>
    <meta name="description" content="ExploreGlob helps businesses and creators automate Facebook and Instagram posting, collaboration, and analytics from one dashboard.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased" x-data="{ mobileOpen: false, scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 12)">
    <!-- Sticky Navbar -->
    <header class="sticky top-0 z-50 border-b border-transparent transition-all duration-200" :class="scrolled ? 'bg-slate-950/90 backdrop-blur-xl border-slate-800 shadow-lg shadow-slate-950/40' : 'bg-slate-950/70 backdrop-blur'">
        <nav class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('marketing.home') }}" class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-bold text-white">EG</span>
                <span class="text-base font-semibold tracking-tight text-white">ExploreGlob</span>
            </a>

            <div class="hidden items-center gap-7 text-sm md:flex">
                <a href="{{ route('marketing.features') }}" class="text-slate-300 transition hover:text-white">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="text-slate-300 transition hover:text-white">Pricing</a>
                <a href="{{ route('marketing.integrations') }}" class="text-slate-300 transition hover:text-white">Integrations</a>
                <a href="{{ route('marketing.security') }}" class="text-slate-300 transition hover:text-white">Security</a>
                <a href="{{ route('login') }}" class="text-slate-300 transition hover:text-white">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 font-medium text-white shadow-lg shadow-indigo-900/30 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-900/40">Start Free Trial</a>
            </div>

            <button type="button" class="inline-flex items-center rounded-lg border border-slate-700 p-2 text-slate-200 md:hidden" @click="mobileOpen = !mobileOpen" aria-label="Open menu">
                <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </nav>

        <div x-show="mobileOpen" x-transition class="border-t border-slate-800 bg-slate-950/95 px-4 py-4 md:hidden">
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('marketing.features') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">Features</a>
                <a href="{{ route('marketing.pricing') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">Pricing</a>
                <a href="{{ route('marketing.integrations') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">Integrations</a>
                <a href="{{ route('marketing.security') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">Security</a>
                <a href="{{ route('marketing.about') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">About</a>
                <a href="{{ route('marketing.contact') }}" class="rounded-lg px-3 py-2 text-slate-200 hover:bg-slate-900">Contact</a>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-center font-medium text-slate-100">Login</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-3 py-2 text-center font-medium text-white">Start Trial</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/90 bg-slate-950">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
            <div>
                <p class="text-lg font-semibold text-white">ExploreGlob</p>
                <p class="mt-3 text-sm text-slate-400">Automation-first social media publishing for creators, teams, and agencies.</p>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Product</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-400">
                    <li><a class="hover:text-white" href="{{ route('marketing.features') }}">Features</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.pricing') }}">Pricing</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.integrations') }}">Integrations</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.security') }}">Security</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Resources</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-400">
                    <li><a class="hover:text-white" href="{{ route('marketing.about') }}">About</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.contact') }}">Contact</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.privacy') }}">Privacy Policy</a></li>
                    <li><a class="hover:text-white" href="{{ route('marketing.terms') }}">Terms & Conditions</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-white">Social</p>
                <div class="mt-3 flex gap-3">
                    <a href="#" class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:border-slate-500 hover:text-white">𝕏</a>
                    <a href="#" class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:border-slate-500 hover:text-white">in</a>
                    <a href="#" class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:border-slate-500 hover:text-white">ig</a>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-800 px-4 py-4 text-center text-xs text-slate-500">© {{ now()->year }} ExploreGlob. All rights reserved.</div>
    </footer>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
