@extends('marketing.layout')

@section('title', 'Postzy - Social Publishing Platform')

@section('content')
<section class="hero-section" style="min-height:50vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Plan, Schedule & Publish Content</h1>
            <p class="hero-subtitle">Postzy helps teams manage Facebook, Instagram, and Google Business posting with scheduling and automation. </p>
            <div class="d-flex flex-wrap gap-3 justify-content-center">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">Start Free</a>
                <a href="{{ route('marketing.pricing') }}" class="btn btn-primary btn-lg" style="background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.4)">View Pricing</a>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <h2 class="section-title">Why Postzy?</h2>
    <div class="row g-4">
        <div class="col-md-4"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body text-center py-5"><div style="font-size:2.5rem;margin-bottom:1rem">⏰</div><h5 class="card-title">Smart Scheduling</h5><p class="card-text">Queue content in advance and let background workers publish on time.</p></div></div></div>
        <div class="col-md-4"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body text-center py-5"><div style="font-size:2.5rem;margin-bottom:1rem">📤</div><h5 class="card-title">Automate posting</h5><p class="card-text">Having multiple accounts and dont have time to post everywhere let us handle that.</p></div></div></div>
         <div class="col-md-4"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body text-center py-5"><div style="font-size:2.5rem;margin-bottom:1rem">🤖</div><h5 class="card-title">AI Captions</h5><p class="card-text">Give pre defined prompt and Let AI write captions and select trending hashtags.</p></div></div></div>
        <div class="col-md-4"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body text-center py-5"><div style="font-size:2.5rem;margin-bottom:1rem">📊</div><h5 class="card-title">Usage Visibility</h5><p class="card-text">Track plan usage and account status from your customer dashboard.</p></div></div></div>
    </div>
</div>
@endsection
