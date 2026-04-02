@extends('marketing.layout')

@section('title', 'Pricing | ExploreGlob')

@section('content')
    <!-- Pricing Hero -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-16 sm:px-6 lg:px-8" x-data="{ yearly: true }">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Simple pricing for creators, teams, and agencies</h1>
        <p class="mt-4 max-w-3xl text-lg text-slate-300">Start free, scale as you grow, and keep every brand account active with automation-first workflows.</p>

        <!-- Billing Toggle -->
        <div class="mt-8 inline-flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900 p-2 text-sm">
            <button @click="yearly = false" :class="yearly ? 'text-slate-300' : 'bg-slate-800 text-white'" class="rounded-lg px-4 py-2 font-medium">Monthly</button>
            <button @click="yearly = true" :class="yearly ? 'bg-slate-800 text-white' : 'text-slate-300'" class="rounded-lg px-4 py-2 font-medium">Yearly <span class="text-emerald-300">(save 20%)</span></button>
        </div>

        <!-- Plans -->
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            <article class="rounded-xl border border-slate-800 bg-slate-900 p-7">
                <h2 class="text-2xl font-bold text-white">Starter</h2>
                <p class="mt-1 text-sm text-slate-400">for individuals</p>
                <p class="mt-5 text-4xl font-extrabold text-white"><span x-show="!yearly">$19</span><span x-show="yearly">$15</span><span class="text-base font-medium text-slate-400">/mo</span></p>
                <ul class="mt-6 space-y-2 text-sm text-slate-300">
                    <li>• 1 social account</li><li>• Basic scheduling</li><li>• Limited analytics</li>
                </ul>
                <a href="{{ route('register') }}" class="mt-7 inline-flex rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-100">Choose Starter</a>
            </article>

            <article class="rounded-xl border border-violet-500 bg-slate-900 p-7 shadow-xl shadow-violet-900/20">
                <p class="inline-flex rounded-full bg-gradient-to-r from-pink-500 to-purple-500 px-3 py-1 text-xs font-semibold text-white">Most popular</p>
                <h2 class="mt-3 text-2xl font-bold text-white">Growth</h2>
                <p class="mt-1 text-sm text-slate-400">for creators and startups</p>
                <p class="mt-5 text-4xl font-extrabold text-white"><span x-show="!yearly">$49</span><span x-show="yearly">$39</span><span class="text-base font-medium text-slate-400">/mo</span></p>
                <ul class="mt-6 space-y-2 text-sm text-slate-300">
                    <li>• Multiple accounts</li><li>• Auto publishing</li><li>• Content calendar</li><li>• Team collaboration</li>
                </ul>
                <a href="{{ route('register') }}" class="mt-7 inline-flex rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white">Choose Growth</a>
            </article>

            <article class="rounded-xl border border-slate-800 bg-slate-900 p-7">
                <h2 class="text-2xl font-bold text-white">Agency</h2>
                <p class="mt-1 text-sm text-slate-400">for marketing teams</p>
                <p class="mt-5 text-4xl font-extrabold text-white"><span x-show="!yearly">$99</span><span x-show="yearly">$79</span><span class="text-base font-medium text-slate-400">/mo</span></p>
                <ul class="mt-6 space-y-2 text-sm text-slate-300">
                    <li>• Unlimited accounts</li><li>• Client workspace support</li><li>• Advanced analytics</li><li>• Priority support</li><li>• Early access integrations</li>
                </ul>
                <a href="{{ route('register') }}" class="mt-7 inline-flex rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-100">Choose Agency</a>
            </article>
        </div>

        <!-- Feature Comparison -->
        <div class="mt-16 overflow-x-auto rounded-xl border border-slate-800">
            <table class="min-w-full divide-y divide-slate-800 text-left text-sm text-slate-300">
                <thead class="bg-slate-900 text-slate-200"><tr><th class="px-4 py-3">Feature</th><th class="px-4 py-3">Starter</th><th class="px-4 py-3">Growth</th><th class="px-4 py-3">Agency</th></tr></thead>
                <tbody class="divide-y divide-slate-800 bg-slate-950">
                    <tr><td class="px-4 py-3">Facebook + Instagram publishing</td><td class="px-4 py-3">1 account</td><td class="px-4 py-3">Up to 10 accounts</td><td class="px-4 py-3">Unlimited</td></tr>
                    <tr><td class="px-4 py-3">Team collaboration</td><td class="px-4 py-3">—</td><td class="px-4 py-3">Included</td><td class="px-4 py-3">Advanced roles</td></tr>
                    <tr><td class="px-4 py-3">Analytics depth</td><td class="px-4 py-3">Basic</td><td class="px-4 py-3">Standard</td><td class="px-4 py-3">Advanced</td></tr>
                    <tr><td class="px-4 py-3">Google Business Profile</td><td class="px-4 py-3">Coming soon</td><td class="px-4 py-3">Coming soon</td><td class="px-4 py-3">Early access</td></tr>
                </tbody>
            </table>
        </div>

        <!-- FAQ -->
        <div class="mt-16 grid gap-4">
            <h2 class="text-2xl font-bold text-white">Pricing FAQ</h2>
            <details class="rounded-xl border border-slate-800 bg-slate-900 p-5"><summary class="cursor-pointer font-semibold text-white">Can I cancel anytime?</summary><p class="mt-3 text-sm text-slate-300">Yes. You can cancel from billing settings and keep access through your current cycle.</p></details>
            <details class="rounded-xl border border-slate-800 bg-slate-900 p-5"><summary class="cursor-pointer font-semibold text-white">Do you offer onboarding support?</summary><p class="mt-3 text-sm text-slate-300">Growth and Agency include guided onboarding resources and best-practice workflows.</p></details>
            <details class="rounded-xl border border-slate-800 bg-slate-900 p-5"><summary class="cursor-pointer font-semibold text-white">Is there a free trial?</summary><p class="mt-3 text-sm text-slate-300">Yes, all plans include a free trial with no credit card required to start.</p></details>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="mx-auto w-full max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-violet-500/30 bg-gradient-to-r from-indigo-600/20 to-violet-600/20 px-8 py-12 text-center">
            <h2 class="text-3xl font-bold text-white">Need a custom rollout for your team?</h2>
            <p class="mt-3 text-slate-200">Talk to sales for migration help, training, and automation workflow setup.</p>
            <a href="{{ route('marketing.contact') }}" class="mt-6 inline-flex rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white">Contact Sales</a>
        </div>
    </section>
@endsection
