@extends('marketing.layout')

@section('title', 'ExploreGlob - Social Publishing Platform')

@section('content')
<div class="p-5 bg-white border rounded-3 mb-4">
    <h1 class="display-6 fw-bold">Plan, schedule, and publish social content in one place.</h1>
    <p class="lead text-muted">ExploreGlob helps teams manage Facebook, Instagram, and Google Business posting with scheduling, media management, and subscription-based usage controls.</p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('register') }}" class="btn btn-primary">Start Free</a>
        <a href="{{ route('marketing.pricing') }}" class="btn btn-outline-primary">View Pricing</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><h5>Smart Scheduling</h5><p class="text-muted mb-0">Queue content in advance and let background workers publish on time.</p></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><h5>Media Library</h5><p class="text-muted mb-0">Reuse your approved images/videos across future campaigns.</p></div></div></div>
    <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body"><h5>Usage Visibility</h5><p class="text-muted mb-0">Track plan usage and account status from your customer dashboard.</p></div></div></div>
</div>
@endsection
