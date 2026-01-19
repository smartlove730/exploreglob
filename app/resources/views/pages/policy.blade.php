@extends('layouts.app')

@section('content')

<!-- Hero Section -->
<section class="hero-section" style="min-height: 30vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Privacy Policy</h1>
            <p class="hero-subtitle">Your privacy matters to us. Learn how we protect your information.</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="animated-card">
                <div class="card-body p-5">
                    <p class="text-muted mb-5"><small>Last updated: {{ date('F d, Y') }}</small></p>
                    
                    <div class="blog-section">
                        <h3>Introduction</h3>
                        <p>Welcome to our blog platform. We respect your privacy and are committed to protecting your personal data. This privacy policy explains how we collect, use, and safeguard your information.</p>
                    </div>
                    
                    <div class="blog-section">
                        <h3>Information We Collect</h3>
                        <p>We may collect the following types of information:</p>
                        <ul style="line-height: 2;">
                            <li>Personal information (name, email address) when you contact us</li>
                            <li>Browser information and cookies for analytics</li>
                            <li>Country preference for content personalization</li>
                        </ul>
                    </div>
                    
                    <div class="blog-section">
                        <h3>How We Use Your Information</h3>
                        <p>We use the information we collect to:</p>
                        <ul style="line-height: 2;">
                            <li>Provide and improve our services</li>
                            <li>Personalize your experience</li>
                            <li>Respond to your inquiries</li>
                            <li>Analyze usage patterns</li>
                        </ul>
                    </div>
                    
                    <div class="blog-section">
                        <h3>Cookies</h3>
                        <p>We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. You can control cookies through your browser settings.</p>
                    </div>
                    
                    <div class="blog-section">
                        <h3>Contact Us</h3>
                        <p>If you have any questions about this Privacy Policy, please <a href="{{ route('contact') }}" class="text-decoration-none" style="color: var(--primary-color); font-weight: 600;">contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

