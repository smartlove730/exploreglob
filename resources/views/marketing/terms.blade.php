@extends('marketing.layout')

@section('title', 'Terms & Conditions - ExploreGlob')

@section('content')
<div class="bg-white border rounded-3 p-4 p-md-5">
    <h1 class="h2 mb-2">Terms & Conditions</h1>
    <p class="text-muted mb-4">Last updated: {{ now()->format('F d, Y') }}</p>

    <p class="text-muted">By accessing or using ExploreGlob, you agree to these Terms & Conditions. If you do not agree, do not use the service.</p>

    <h2 class="h5 mt-4">1) Service Scope</h2>
    <p class="text-muted">ExploreGlob provides tools for creating, scheduling, and publishing content, including integrations with third-party services such as Facebook/Meta. Your use of those services must also follow their platform policies and terms.</p>

    <h2 class="h5 mt-4">2) Account Responsibility</h2>
    <ul class="text-muted">
        <li>You are responsible for account credentials and activity under your account.</li>
        <li>You must provide accurate information and keep it up to date.</li>
        <li>You must not use the platform for illegal, harmful, or deceptive activity.</li>
    </ul>

    <h2 class="h5 mt-4">3) Third-Party Platforms</h2>
    <p class="text-muted">You authorize ExploreGlob to act on your behalf only for the permissions you grant. We are not responsible for downtime, policy changes, or actions taken by third-party platforms.</p>

    <h2 class="h5 mt-4">4) Subscription and Access</h2>
    <p class="text-muted">Paid plans may include usage limits and billing terms shown at checkout. We may suspend or terminate access for fraud, abuse, non-payment, or serious policy violations.</p>

    <h2 class="h5 mt-4">5) Limitation of Liability</h2>
    <p class="text-muted">The service is provided on an "as is" basis. To the extent permitted by law, ExploreGlob is not liable for indirect, incidental, or consequential damages arising from service use.</p>

    <h2 class="h5 mt-4">6) Changes to Terms</h2>
    <p class="text-muted">We may update these terms periodically. Continued use after updates means you accept the revised terms.</p>

    <h2 class="h5 mt-4">7) Contact</h2>
    <p class="text-muted mb-0">For legal questions, please use our <a href="{{ route('marketing.contact') }}">Contact page</a>.</p>
</div>
@endsection
