@extends('marketing.layout')

@section('title', 'ExploreGlob | Social Media Automation Platform')

@section('content')
    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="mx-auto grid w-full max-w-7xl items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pb-24">
            <div>
                <p class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">Now live for Facebook + Instagram</p>
                <h1 class="mt-6 text-4xl font-black leading-tight tracking-tight text-slate-900 sm:text-5xl">Automate your social media presence — everywhere your audience lives</h1>
                <p class="mt-6 max-w-xl text-lg text-slate-600">Schedule, manage, and auto-publish posts to Facebook and Instagram from one powerful dashboard.</p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-300/70 transition hover:-translate-y-0.5">Start Free Trial</a>
                    <a href="{{ route('marketing.features') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:border-slate-400">Watch Demo</a>
                </div>
            </div>
            <div class="rounded-3xl border border-violet-100 bg-white p-5 shadow-2xl shadow-indigo-100">
                <div class="rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">Publishing Dashboard</p>
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Auto publishing active</span>
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-xl border border-white bg-white/80 p-3 shadow-sm"><p class="text-xs text-slate-500">Today • 10:00 AM</p><p class="mt-1 text-sm text-slate-800">Launch teaser post scheduled for Facebook + Instagram</p></div>
                        <div class="rounded-xl border border-white bg-white/80 p-3 shadow-sm"><p class="text-xs text-slate-500">Tomorrow • 1:30 PM</p><p class="mt-1 text-sm text-slate-800">Product tip carousel queued for 4 brand accounts</p></div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Queued</p><p class="mt-1 text-lg font-bold text-slate-900">128</p></div>
                            <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Published</p><p class="mt-1 text-lg font-bold text-slate-900">4.2k</p></div>
                            <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Engagement</p><p class="mt-1 text-lg font-bold text-slate-900">8.9%</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-8 sm:px-6 lg:px-8">
            <p class="text-sm text-slate-600">Trusted by creators, agencies, and local businesses</p>
            <div class="flex flex-wrap gap-6 text-sm font-semibold text-slate-700"><span>StudioPilot</span><span>LocalMint</span><span>ScaleCraft</span><span>CreatorLoop</span></div>
        </div>
    </section>

    <!-- Feature Grid -->
    <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-700">Core capabilities</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-900">Built to keep your social presence consistent</h2>
        <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Auto publishing', 'Automatically publish posts without manual work', 'from-indigo-500 to-violet-500'],
                ['Multi-platform posting', 'Publish to multiple social networks at once', 'from-sky-500 to-indigo-500'],
                ['Smart scheduling', 'Choose best posting times automatically', 'from-fuchsia-500 to-violet-500'],
                ['Content calendar', 'Visual drag-and-drop planner', 'from-emerald-500 to-teal-500'],
                ['Team collaboration', 'Invite clients or team members', 'from-rose-500 to-pink-500'],
                ['Performance analytics', 'Track reach, clicks, engagement', 'from-amber-500 to-orange-500'],
            ] as [$title, $description, $gradient])
                <article class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <span class="inline-flex h-2.5 w-16 rounded-full bg-gradient-to-r {{ $gradient }}"></span>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <!-- Product Walkthrough -->
    <section class="bg-gradient-to-r from-indigo-600 to-violet-600">
        <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white">Create post → schedule → auto publish → track performance</h2>
        </div>
    </section>

    <!-- Stats / Testimonials / CTA -->
    <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['1.8M+', 'Posts automated monthly'],['94K+', 'Accounts connected'],['2,700+', 'Agencies using platform'],['41,000+', 'Hours saved weekly']] as [$value, $label])
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm"><p class="bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-3xl font-extrabold text-transparent">{{ $value }}</p><p class="mt-2 text-sm text-slate-600">{{ $label }}</p></div>
            @endforeach
        </div>
        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach([
                ['“We replaced four tools with ExploreGlob and cut scheduling time by 60%.”', 'Maya Chen · Creative Agency Owner'],
                ['“Our brand consistency is finally predictable, even across six locations.”', 'Andre Walker · Multi-location Retail'],
                ['“Approval workflows made client collaboration actually painless.”', 'Sofia Reyes · Social Media Consultant'],
            ] as [$quote, $by])
                <blockquote class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-700">{{ $quote }}</p><footer class="mt-4 text-xs text-slate-500">{{ $by }}</footer></blockquote>
            @endforeach
        </div>
        <div class="mt-12 rounded-3xl bg-slate-900 px-8 py-12 text-center">
            <h2 class="text-3xl font-bold text-white">Start automating your content today</h2>
            <p class="mx-auto mt-3 max-w-2xl text-slate-300">Launch your first workflows in minutes and keep every account active without adding more manual work.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-4"><a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white">Start Free Trial</a><a href="{{ route('marketing.contact') }}" class="rounded-xl border border-slate-600 px-6 py-3 text-sm font-semibold text-slate-100">Talk to Sales</a></div>
        </div>
    </section>
@endsection
