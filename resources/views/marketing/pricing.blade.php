@extends('marketing.layout')

@section('title', 'Pricing - ExploreGlob')

@section('content')
<h1 class="h2 mb-2">Pricing</h1>
<p class="text-muted mb-4">Simple plans for teams of every size.</p>

<div class="row g-3">
    @forelse($plans as $plan)
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <h5>{{ $plan->name }}</h5>
                    <p class="display-6 mb-0">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</p>
                    <p class="text-muted">per {{ $plan->interval }}</p>
                    <ul class="small text-muted ps-3 mb-4">
                        <li>{{ $plan->post_limit }} posts / period</li>
                        <li>Facebook: {{ $plan->facebook_enabled ? 'Yes' : 'No' }}</li>
                        <li>Instagram: {{ $plan->instagram_enabled ? 'Yes' : 'No' }}</li>
                        <li>Google Business: {{ $plan->google_business_enabled ? 'Yes' : 'No' }}</li>
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
@endsection
