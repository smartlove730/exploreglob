@extends('marketing.layout')

@section('title', 'Contact | ExploreGlob')

@section('content')
    <!-- Contact Hero -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-10 pt-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Let’s build your automation workflow</h1>
        <p class="mt-4 max-w-3xl text-lg text-slate-300">Share your goals and our team will help you pick the right plan, onboarding path, and rollout timeline.</p>
    </section>

    <section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-24 sm:px-6 lg:grid-cols-3 lg:px-8">
        <!-- Contact Form -->
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-7 lg:col-span-2">
            <h2 class="text-2xl font-bold text-white">Contact form</h2>
            @if(session('success'))
                <p class="mt-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-300">{{ session('success') }}</p>
            @endif
            <form method="POST" action="{{ route('marketing.contact.send') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">Name</label>
                    <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none ring-violet-500 focus:ring">
                    @error('name')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none ring-violet-500 focus:ring">
                    @error('email')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-200">Company</label>
                    <input name="company" value="{{ old('company') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none ring-violet-500 focus:ring">
                    @error('company')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-200">Message</label>
                    <textarea name="message" rows="6" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100 outline-none ring-violet-500 focus:ring">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                </div>
                <button class="w-fit rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white">Send message</button>
            </form>
        </div>

        <!-- Side Cards -->
        <div class="space-y-6">
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h3 class="text-lg font-bold text-white">Sales inquiry</h3>
                <p class="mt-2 text-sm text-slate-300">Need migration support, a custom onboarding plan, or team training? We can help.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h3 class="text-lg font-bold text-white">Support request</h3>
                <p class="mt-2 text-sm text-slate-300">Already a customer? Share account context and our support team will jump in quickly.</p>
            </article>
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h3 class="text-lg font-bold text-white">Response time</h3>
                <p class="mt-2 text-sm text-slate-300">We typically reply within one business day. Agency and priority plans receive faster response windows.</p>
            </article>
        </div>
    </section>
@endsection
