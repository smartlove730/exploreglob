@extends('marketing.layout')

@section('title', 'Pricing - Postzy')

@section('content')
<section class="hero-section" style="min-height:35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Simple Pricing</h1>
            <p class="hero-subtitle">Plans for teams of every size. Start free, scale as you grow.</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row g-4">
        @forelse($plans as $plan)
            <div class="col-md-4">
                <div class="animated-card" style="opacity:1;transform:none">
                    <div class="card-body d-flex flex-column p-4">
                        <h5 class="card-title">{{ $plan->name }}</h5>
                        <p class="display-6 mb-0" style="font-weight:800;color:var(--pz-indigo)">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</p>
                        <p class="text-muted">per {{ $plan->interval }}</p>
                        <ul class="small text-muted ps-3 mb-4">
                            <li>{{ $plan->post_limit }} posts / period</li>
                            <li>Facebook: {{ $plan->facebook_enabled ? '✅ Yes' : '❌ No' }}</li>
                            <li>Instagram: {{ $plan->instagram_enabled ? '✅ Yes' : '❌ No' }}</li>
                        </ul>
                        <a href="{{ route('register') }}" class="btn btn-primary mt-auto">Choose {{ $plan->name }}</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary mb-0">Pricing plans are being updated. Please check back shortly.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
