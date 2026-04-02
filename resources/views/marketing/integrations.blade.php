@extends('marketing.layout')
@section('title', 'Integrations | ExploreGlob')
@section('content')
<section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-16 sm:px-6 lg:px-8"><h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">Connect the platforms that power your brand</h1></section>
<section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-20 sm:px-6 md:grid-cols-2 lg:grid-cols-3 lg:px-8">
<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-2xl font-bold">Facebook</h2><p class="mt-3 text-sm text-slate-600">Schedule, queue, and auto-publish feed posts with account-level visibility.</p></article>
<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-2xl font-bold">Instagram</h2><p class="mt-3 text-sm text-slate-600">Plan visuals, publish on-time, and maintain a consistent brand presence.</p></article>
<article class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm"><p class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Coming soon</p><h2 class="mt-3 text-2xl font-bold">Google Business Profile</h2><p class="mt-3 text-sm text-slate-600">Auto-post local updates and offers as soon as integration support launches.</p></article>
</section>
<section class="bg-white border-y border-slate-200"><div class="mx-auto grid w-full max-w-7xl gap-6 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8"><article class="rounded-2xl border border-slate-200 bg-gradient-to-br from-indigo-50 to-white p-7"><h3 class="text-2xl font-bold">API access</h3><p class="mt-3 text-sm text-slate-600">Sync content from your CMS and internal tools directly into publishing workflows.</p></article><article class="rounded-2xl border border-slate-200 bg-gradient-to-br from-fuchsia-50 to-white p-7"><h3 class="text-2xl font-bold">Webhook support</h3><p class="mt-3 text-sm text-slate-600">Trigger downstream actions when posts publish, fail, or require approval.</p></article></div></section>
@endsection
