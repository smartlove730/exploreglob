@extends('marketing.layout')

@section('title', 'About | ExploreGlob')

@section('content')
    <!-- Mission -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-10 pt-16 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-300">About</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Our mission is to make consistent social media posting effortless for every business.</h1>
    </section>

    <!-- Story -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-8">
            <h2 class="text-2xl font-bold text-white">Built to solve manual posting frustration</h2>
            <p class="mt-4 text-slate-300">ExploreGlob started after seeing teams juggle spreadsheets, reminders, and late-night manual posting. We built a platform where content planning, scheduling, collaboration, and analytics live in one streamlined workflow so teams can focus on strategy instead of repetitive tasks.</p>
        </div>
    </section>

    <!-- Values -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-white">Values</h2>
        <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach(['Automation-first mindset', 'Simplicity', 'Reliability', 'Customer success'] as $value)
                <article class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-sm font-semibold text-slate-100">{{ $value }}</article>
            @endforeach
        </div>
    </section>

    <!-- Team -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-white">Team</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach(['Product', 'Engineering', 'Customer Success', 'Growth'] as $role)
                <article class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600"></div>
                    <p class="mt-4 font-semibold text-white">{{ $role }} Lead</p>
                    <p class="text-sm text-slate-400">Team member placeholder</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
