@extends('marketing.layout')

@section('title', 'Integrations | ExploreGlob')

@section('content')
    <!-- Integrations Hero -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Connect the platforms that power your brand</h1>
        <p class="mt-5 max-w-3xl text-lg text-slate-300">Centralize publishing workflows and automate your posting operations across your social ecosystem.</p>
    </section>

    <!-- Integration Cards -->
    <section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-20 sm:px-6 md:grid-cols-2 lg:grid-cols-3 lg:px-8">
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-6"><h2 class="text-2xl font-bold text-white">Facebook</h2><p class="mt-3 text-sm text-slate-300">Schedule, queue, and auto-publish feed posts with account-level visibility.</p></article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-6"><h2 class="text-2xl font-bold text-white">Instagram</h2><p class="mt-3 text-sm text-slate-300">Plan visuals, publish on-time, and maintain a consistent brand presence.</p></article>
        <article class="rounded-xl border border-amber-500/40 bg-slate-900 p-6"><p class="inline-flex rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">Coming soon</p><h2 class="mt-3 text-2xl font-bold text-white">Google Business Profile</h2><p class="mt-3 text-sm text-slate-300">Auto-post local updates and offers as soon as integration support launches.</p></article>
    </section>

    <!-- API & Webhooks -->
    <section class="border-y border-slate-800 bg-slate-900/40">
        <div class="mx-auto grid w-full max-w-7xl gap-6 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <article class="rounded-xl border border-slate-800 bg-slate-950 p-7">
                <h3 class="text-2xl font-bold text-white">API access</h3>
                <p class="mt-3 text-sm text-slate-300">Use API endpoints to sync content from your CMS, internal tools, or campaign systems directly into scheduling workflows.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-950 p-7">
                <h3 class="text-2xl font-bold text-white">Webhook support</h3>
                <p class="mt-3 text-sm text-slate-300">Trigger actions when posts publish, fail, or require approval so your team can respond quickly in real time.</p>
            </article>
        </div>
    </section>
@endsection
