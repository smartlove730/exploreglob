@extends('marketing.layout')

@section('title', 'Features - Postzy')

@section('content')
<section class="hero-section" style="min-height:35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Powerful Features</h1>
            <p class="hero-subtitle">Everything you need to manage and publish social content at scale.</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-md-6"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body p-4"><h5 class="card-title">📅 Content Calendar</h5><p class="card-text">Create and manage scheduled posts in a unified calendar workflow.</p></div></div></div>
        <div class="col-md-6"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body p-4"><h5 class="card-title">📋 Bulk CSV Scheduling</h5><p class="card-text">Import multiple scheduled posts with validation and per-row error reporting.</p></div></div></div>
        <div class="col-md-6"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body p-4"><h5 class="card-title">🌐 Cross-Platform Publishing</h5><p class="card-text">Publish to Facebook, Instagram, and Google Business based on plan capabilities.</p></div></div></div>
        <div class="col-md-6"><div class="animated-card" style="opacity:1;transform:none"><div class="card-body p-4"><h5 class="card-title">🔒 Subscription Controls</h5><p class="card-text">Enforce quotas and platform access with built-in plan enforcement.</p></div></div></div>
    </div>
</div>
@endsection
