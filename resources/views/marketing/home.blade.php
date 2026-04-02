@extends('marketing.layout')

@section('title', 'ExploreGlob | Social Media Automation Platform')

@section('content')
    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,_rgba(139,92,246,0.18),_transparent_45%),radial-gradient(circle_at_20%_20%,_rgba(79,70,229,0.2),_transparent_35%)]"></div>
        <div class="mx-auto grid w-full max-w-7xl items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pb-24">
            <div>
                <p class="inline-flex rounded-full border border-violet-500/30 bg-violet-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Now live for Facebook + Instagram</p>
                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl">Automate your social media presence — everywhere your audience lives</h1>
                <p class="mt-6 max-w-xl text-lg text-slate-300">Schedule, manage, and auto-publish posts to Facebook and Instagram from one powerful dashboard.</p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-900/35 transition hover:-translate-y-0.5">Start Free Trial</a>
                    <a href="{{ route('marketing.features') }}" class="rounded-xl border border-slate-700 px-6 py-3 text-sm font-semibold text-slate-100 transition hover:border-slate-500 hover:bg-slate-900">Watch Demo</a>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 shadow-2xl shadow-slate-950/60">
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-semibold text-white">Publishing Dashboard</p>
                        <span class="rounded-full bg-emerald-500/10 px-2 py-1 text-xs text-emerald-300">Auto publishing active</span>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                            <p class="text-xs text-slate-400">Today • 10:00 AM</p>
                            <p class="mt-1 text-sm text-slate-100">Launch teaser post scheduled for Facebook + Instagram</p>
                        </div>
                        <div class="rounded-lg border border-slate-800 bg-slate-900 p-3">
                            <p class="text-xs text-slate-400">Tomorrow • 1:30 PM</p>
                            <p class="mt-1 text-sm text-slate-100">Product tip carousel queued for 4 brand accounts</p>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-lg bg-slate-900 p-3"><p class="text-xs text-slate-400">Queued</p><p class="mt-1 text-lg font-bold text-white">128</p></div>
                            <div class="rounded-lg bg-slate-900 p-3"><p class="text-xs text-slate-400">Published</p><p class="mt-1 text-lg font-bold text-white">4.2k</p></div>
                            <div class="rounded-lg bg-slate-900 p-3"><p class="text-xs text-slate-400">Engagement</p><p class="mt-1 text-lg font-bold text-white">8.9%</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="border-y border-slate-800 bg-slate-900/40">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-8 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-400">Trusted by creators, agencies, and local businesses</p>
            <div class="flex flex-wrap gap-6 text-sm font-semibold text-slate-300">
                <span>StudioPilot</span><span>LocalMint</span><span>ScaleCraft</span><span>CreatorLoop</span>
            </div>
        </div>
    </section>

    <!-- Feature Grid -->
    <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mb-10 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-300">Core capabilities</p>
                <h2 class="mt-2 text-3xl font-bold text-white">Built to keep your social presence consistent</h2>
            </div>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Auto publishing', 'Automatically publish posts without manual work'],
                ['Multi-platform posting', 'Publish to multiple social networks at once'],
                ['Smart scheduling', 'Choose best posting times automatically'],
                ['Content calendar', 'Visual drag-and-drop planner'],
                ['Team collaboration', 'Invite clients or team members'],
                ['Performance analytics', 'Track reach, clicks, engagement'],
            ] as [$title, $description])
                <article class="group rounded-xl border border-slate-800 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/50 transition duration-300 hover:-translate-y-1 hover:border-slate-700">
                    <h3 class="text-lg font-semibold text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <!-- Product Walkthrough -->
    <section class="border-y border-slate-800 bg-slate-900/40">
        <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white">From idea to insights in one automation workflow</h2>
            <div class="mt-10 grid gap-4 md:grid-cols-4">
                @foreach(['Create post', 'Schedule', 'Auto publish', 'Track performance'] as $step)
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-5 text-sm font-semibold text-slate-100">{{ $step }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['1.8M+', 'Posts automated monthly'],
                ['94K+', 'Accounts connected'],
                ['2,700+', 'Agencies using platform'],
                ['41,000+', 'Hours saved weekly'],
            ] as [$value, $label])
                <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 text-center">
                    <p class="bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-3xl font-extrabold text-transparent">{{ $value }}</p>
                    <p class="mt-2 text-sm text-slate-300">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Testimonials -->
    <section class="border-y border-slate-800 bg-slate-900/40">
        <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white">Loved by fast-moving teams</h2>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach([
                    ['“We replaced four tools with ExploreGlob and cut scheduling time by 60%.”', 'Maya Chen', 'Creative Agency Owner'],
                    ['“Our brand consistency is finally predictable, even across six locations.”', 'Andre Walker', 'Multi-location Retail'],
                    ['“Approval workflows made client collaboration actually painless.”', 'Sofia Reyes', 'Social Media Consultant'],
                ] as [$quote, $name, $role])
                    <blockquote class="rounded-xl border border-slate-800 bg-slate-950 p-6">
                        <p class="text-sm leading-6 text-slate-200">{{ $quote }}</p>
                        <footer class="mt-4 text-xs text-slate-400">{{ $name }} · {{ $role }}</footer>
                    </blockquote>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mb-10 flex items-end justify-between">
            <h2 class="text-3xl font-bold text-white">Choose the plan that scales with you</h2>
            <a href="{{ route('marketing.pricing') }}" class="text-sm font-semibold text-violet-300 hover:text-violet-200">See full pricing →</a>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6"><h3 class="text-lg font-semibold text-white">Starter</h3><p class="mt-2 text-slate-300">For individuals getting consistent online.</p></div>
            <div class="rounded-xl border border-violet-500/60 bg-slate-900 p-6 shadow-lg shadow-violet-900/20"><h3 class="text-lg font-semibold text-white">Growth</h3><p class="mt-2 text-slate-300">For creators and startups growing across channels.</p></div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6"><h3 class="text-lg font-semibold text-white">Agency</h3><p class="mt-2 text-slate-300">For client teams managing multiple brands.</p></div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-violet-500/30 bg-gradient-to-r from-indigo-600/20 to-violet-600/20 px-8 py-12 text-center">
            <h2 class="text-3xl font-bold text-white">Start automating your content today</h2>
            <p class="mx-auto mt-3 max-w-2xl text-slate-200">Launch your first workflows in minutes and keep every account active without adding more manual work.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white">Start Free Trial</a>
                <a href="{{ route('marketing.contact') }}" class="rounded-xl border border-slate-600 px-6 py-3 text-sm font-semibold text-slate-100">Talk to Sales</a>
            </div>
        </div>
    </section>
@endsection
