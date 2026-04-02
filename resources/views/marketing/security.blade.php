@extends('marketing.layout')

@section('title', 'Security | ExploreGlob')

@section('content')
    <!-- Security Hero -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Your accounts stay protected at every step</h1>
        <p class="mt-5 max-w-3xl text-lg text-slate-300">Security and reliability are built into account connections, team workflows, and publishing infrastructure.</p>
    </section>

    <!-- Security Sections -->
    <section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-24 sm:px-6 md:grid-cols-2 lg:px-8">
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-7"><h2 class="text-xl font-bold text-white">Encrypted connections</h2><p class="mt-3 text-sm text-slate-300">All traffic between your browser, platform APIs, and publishing services is encrypted in transit.</p></article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-7"><h2 class="text-xl font-bold text-white">OAuth secure login</h2><p class="mt-3 text-sm text-slate-300">Connect Facebook and Instagram through OAuth flows without sharing platform passwords.</p></article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-7"><h2 class="text-xl font-bold text-white">Role-based permissions</h2><p class="mt-3 text-sm text-slate-300">Set role levels for creators, editors, and approvers to keep publishing controls clear.</p></article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-7"><h2 class="text-xl font-bold text-white">Workspace access control</h2><p class="mt-3 text-sm text-slate-300">Limit client or team visibility to the right brand workspaces and social accounts.</p></article>
        <article class="rounded-xl border border-slate-800 bg-slate-900 p-7 md:col-span-2"><h2 class="text-xl font-bold text-white">Cloud infrastructure reliability</h2><p class="mt-3 text-sm text-slate-300">Redundant infrastructure and monitored background jobs help ensure your queued posts publish on schedule.</p></article>
    </section>
@endsection
