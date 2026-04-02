@extends('marketing.layout')
@section('title', 'Pricing | ExploreGlob')

@section('content')
<section style="padding:3rem 0 1.25rem;" x-data="{ yearly: true }">
    <p class="eyebrow">Pricing</p>
    <h1 class="page-title">Simple plans for creators, growing teams, and agencies</h1>
    <p class="lead">Choose monthly or yearly billing and scale up as your publishing needs grow.</p>

    <div class="band" style="display:inline-flex;gap:.5rem;align-items:center;margin-top:1rem;">
        <button @click="yearly=false" class="cta-btn cta-btn-secondary" :style="!yearly ? 'background:#eef1ff;color:#4f46e5;' : ''">Monthly</button>
        <button @click="yearly=true" class="cta-btn cta-btn-secondary" :style="yearly ? 'background:#eef1ff;color:#4f46e5;' : ''">Yearly (save 20%)</button>
    </div>

    <div class="cards-3" style="margin-top:1rem;">
        <article class="card price-card">
            <h2>Starter</h2>
            <p>For individuals</p>
            <p class="price"><span x-show="!yearly">$19</span><span x-show="yearly">$15</span><small style="font-size:1rem;color:#6b7390;"> /mo</small></p>
            <ul style="padding-left:1rem;"><li>1 social account</li><li>Basic scheduling</li><li>Simple analytics</li></ul>
        </article>
        <article class="card price-card featured">
            <h2>Growth</h2>
            <p>For creators and startups</p>
            <p class="price"><span x-show="!yearly">$49</span><span x-show="yearly">$39</span><small style="font-size:1rem;color:#6b7390;"> /mo</small></p>
            <ul style="padding-left:1rem;"><li>Up to 10 accounts</li><li>Auto publishing</li><li>Calendar + team tools</li></ul>
        </article>
        <article class="card price-card">
            <h2>Agency</h2>
            <p>For scaling teams</p>
            <p class="price"><span x-show="!yearly">$99</span><span x-show="yearly">$79</span><small style="font-size:1rem;color:#6b7390;"> /mo</small></p>
            <ul style="padding-left:1rem;"><li>Unlimited accounts</li><li>Client workspaces</li><li>Priority support</li></ul>
        </article>
    </div>

    <table class="compare-table">
        <thead><tr><th>Feature</th><th>Starter</th><th>Growth</th><th>Agency</th></tr></thead>
        <tbody>
            <tr><td>Facebook + Instagram</td><td>1 account</td><td>Up to 10</td><td>Unlimited</td></tr>
            <tr><td>Team collaboration</td><td>—</td><td>Included</td><td>Advanced</td></tr>
            <tr><td>Google Business Profile</td><td>Coming soon</td><td>Coming soon</td><td>Early access</td></tr>
        </tbody>
    </table>
</section>
@endsection
