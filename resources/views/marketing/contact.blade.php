@extends('marketing.layout')
@section('title', 'Contact | ExploreGlob')

@section('content')
<section style="padding:3rem 0 1.3rem;">
    <p class="eyebrow">Contact us</p>
    <h1 class="page-title">Let’s design your social automation workflow</h1>
    <p class="lead">Tell us about your team, current challenges, and goals. We typically respond within one business day.</p>
</section>

<section class="section-grid" style="grid-template-columns:2fr 1fr;padding-bottom:2.2rem;align-items:start;">
    <div class="card">
        <h2 style="font-size:1.5rem;font-weight:800;">Send us a message</h2>

        @if(session('success'))
            <p class="band" style="background:#ecfdf5;border-color:#a7f3d0;color:#065f46;">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('marketing.contact.send') }}" class="contact-form">
            @csrf
            <div>
                <label style="display:block;margin-bottom:.3rem;font-weight:600;">Name</label>
                <input name="name" value="{{ old('name') }}" required style="width:100%;padding:.62rem .7rem;border:1px solid #cfd8ff;border-radius:.65rem;">
            </div>
            <div>
                <label style="display:block;margin-bottom:.3rem;font-weight:600;">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:.62rem .7rem;border:1px solid #cfd8ff;border-radius:.65rem;">
            </div>
            <div style="grid-column:1 / -1;">
                <label style="display:block;margin-bottom:.3rem;font-weight:600;">Company</label>
                <input name="company" value="{{ old('company') }}" style="width:100%;padding:.62rem .7rem;border:1px solid #cfd8ff;border-radius:.65rem;">
            </div>
            <div style="grid-column:1 / -1;">
                <label style="display:block;margin-bottom:.3rem;font-weight:600;">Message</label>
                <textarea name="message" rows="6" required style="width:100%;padding:.62rem .7rem;border:1px solid #cfd8ff;border-radius:.65rem;">{{ old('message') }}</textarea>
            </div>
            <button class="cta-btn cta-btn-primary" style="width:max-content;">Send message</button>
        </form>
    </div>

    <div class="cards-3">
        <article class="card"><h3>Sales inquiry</h3><p>Need onboarding, migration help, or enterprise rollout support? We can help.</p></article>
        <article class="card"><h3>Support request</h3><p>Share account context and our support team will guide you quickly.</p></article>
        <article class="card"><h3>Response time</h3><p>Most replies arrive within one business day.</p></article>
    </div>
</section>
@endsection
