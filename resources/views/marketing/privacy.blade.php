@extends('marketing.layout')

@section('title', 'Privacy Policy - ExploreGlob')

@section('content')
<div class="bg-white border rounded-3 p-4 p-md-5">
    <h1 class="h2 mb-2">Privacy Policy</h1>
    <p class="text-muted mb-4">Last updated: {{ now()->format('F d, Y') }}</p>

    <p class="text-muted">This Privacy Policy explains how ExploreGlob collects, uses, and protects information when you use our website and connected social publishing features, including Meta (Facebook) integrations.</p>

    <h2 class="h5 mt-4">1) Information We Collect</h2>
    <ul class="text-muted">
        <li><strong>Account details:</strong> name, email address, and login credentials.</li>
        <li><strong>Connected platform data:</strong> Facebook Page IDs, access tokens, and permissions required to publish and manage posts you authorize.</li>
        <li><strong>Usage data:</strong> feature activity, logs, and diagnostic details used to operate and secure the platform.</li>
        <li><strong>Billing and communication data:</strong> subscription events and support inquiries.</li>
    </ul>

    <h2 class="h5 mt-4">2) How We Use Information</h2>
    <ul class="text-muted">
        <li>Provide requested services such as scheduling and publishing social content.</li>
        <li>Maintain account security, prevent abuse, and troubleshoot issues.</li>
        <li>Process subscriptions and send service-related communication.</li>
        <li>Comply with legal obligations and platform policy requirements.</li>
    </ul>

    <h2 class="h5 mt-4">3) Facebook/Meta Data Use</h2>
    <p class="text-muted">If you connect your Facebook account, we only access the data needed to deliver approved functionality. We do not sell your personal data. Access is limited to authorized system processes and administrators who support service operations.</p>

    <h2 class="h5 mt-4">4) Data Retention</h2>
    <p class="text-muted">We retain data only as long as needed to provide services, resolve disputes, enforce agreements, and meet legal obligations. You may request deletion of account-linked data at any time.</p>

    <h2 class="h5 mt-4">5) Your Rights</h2>
    <p class="text-muted">You may request access, correction, or deletion of your personal data by contacting us. For Facebook-connected data removal, use our <a href="{{ route('marketing.data-deletion') }}">Data Deletion Instructions</a> page.</p>

    <h2 class="h5 mt-4">6) Contact</h2>
    <p class="text-muted mb-0">If you have privacy questions or requests, please use our <a href="{{ route('marketing.contact') }}">Contact page</a>. We will respond within a reasonable timeframe.</p>
</div>
@endsection
