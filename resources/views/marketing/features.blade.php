@extends('marketing.layout')

@section('title', 'Features | ExploreGlob')

@section('content')
    <!-- Features Hero -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-14 pt-16 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-300">Features</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Everything you need to run social media on autopilot</h1>
        <p class="mt-5 max-w-3xl text-lg text-slate-300">Designed for creators, in-house teams, and agencies that need reliable scheduling, collaborative workflows, and measurable performance.</p>
    </section>

    <!-- Feature Blocks -->
    <section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-20 sm:px-6 lg:grid-cols-2 lg:px-8">
        @foreach([
            ['Auto publishing engine', ['Schedule once', 'Publish everywhere', 'Never miss posting again']],
            ['Visual content calendar', ['Drag and drop planner', 'Monthly overview', 'Campaign scheduling']],
            ['Multi-account manager', ['Manage multiple brands', 'Switch accounts instantly', 'Agency friendly']],
            ['Team collaboration', ['Invite teammates', 'Assign roles', 'Approve content before publishing']],
            ['Analytics dashboard', ['Track engagement', 'Measure growth', 'Optimize posting strategy']],
        ] as [$title, $items])
            <article class="rounded-xl border border-slate-800 bg-slate-900/70 p-7 shadow-xl shadow-slate-950/40 transition hover:-translate-y-1">
                <h2 class="text-2xl font-bold text-white">{{ $title }}</h2>
                <ul class="mt-4 space-y-2 text-sm text-slate-300">
                    @foreach($items as $item)
                        <li>• {{ $item }}</li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </section>

    <!-- Upcoming Integrations -->
    <section class="border-y border-slate-800 bg-slate-900/40">
        <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-700 bg-slate-950 p-8">
                <p class="inline-flex rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">Coming soon</p>
                <h3 class="mt-4 text-3xl font-bold text-white">Google Business Profile posting</h3>
                <p class="mt-3 max-w-2xl text-slate-300">Expand your local visibility by auto-posting updates to Google Business Profile as soon as support is released.</p>
            </div>
        </div>
    </section>
@endsection
