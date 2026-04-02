@extends('marketing.layout')
@section('title', 'Contact | ExploreGlob')
@section('content')
<section class="mx-auto w-full max-w-7xl px-4 pb-10 pt-16 sm:px-6 lg:px-8"><h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">Let’s build your automation workflow</h1></section>
<section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-24 sm:px-6 lg:grid-cols-3 lg:px-8">
<div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm lg:col-span-2"><h2 class="text-2xl font-bold">Contact form</h2>@if(session('success'))<p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ session('success') }}</p>@endif
<form method="POST" action="{{ route('marketing.contact.send') }}" class="mt-6 grid gap-4 sm:grid-cols-2">@csrf
<div><label class="mb-2 block text-sm font-medium">Name</label><input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></div>
<div><label class="mb-2 block text-sm font-medium">Email</label><input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></div>
<div class="sm:col-span-2"><label class="mb-2 block text-sm font-medium">Company</label><input name="company" value="{{ old('company') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></div>
<div class="sm:col-span-2"><label class="mb-2 block text-sm font-medium">Message</label><textarea name="message" rows="6" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2">{{ old('message') }}</textarea></div>
<button class="w-fit rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white">Send message</button></form></div>
<div class="space-y-6"><article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold">Sales inquiry</h3><p class="mt-2 text-sm text-slate-600">Need migration support, onboarding, or training? We can help.</p></article><article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold">Support request</h3><p class="mt-2 text-sm text-slate-600">Share account context and our support team will jump in quickly.</p></article><article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold">Response time</h3><p class="mt-2 text-sm text-slate-600">Typical response is within one business day.</p></article></div>
</section>
@endsection
