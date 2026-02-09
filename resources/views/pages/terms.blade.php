@extends('layouts.app')

@section('content')
    <section class="hero-section" style="min-height: 30vh;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Terms & Conditions</h1>
                <p class="hero-subtitle">Please read these terms carefully before using Global Explorer.</p>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="animated-card">
                    <div class="card-body p-5">
                        <h2 class="mb-4">Acceptance of Terms</h2>
                        <p>By accessing or using Global Explorer, you agree to comply with these Terms & Conditions. If you do not agree, please discontinue use of the platform.</p>

                        <h2 class="mb-4 mt-5">Content Disclaimer</h2>
                        <p>Content on Global Explorer is provided for informational purposes only. While we strive for accuracy, we do not guarantee that all information is complete, up-to-date, or error-free. Use the content at your own discretion.</p>

                        <h2 class="mb-4 mt-5">User Conduct</h2>
                        <ul>
                            <li>You may not submit unlawful, abusive, or misleading content.</li>
                            <li>You are responsible for the accuracy of the information you provide.</li>
                            <li>You agree not to attempt to compromise the security or integrity of the platform.</li>
                        </ul>

                        <h2 class="mb-4 mt-5">Intellectual Property</h2>
                        <p>All site content, including text, graphics, and branding, is owned by Global Explorer or its contributors and may not be reused without permission.</p>

                        <h2 class="mb-4 mt-5">Changes to These Terms</h2>
                        <p>We may update these terms periodically. Continued use of the platform after changes means you accept the revised terms.</p>

                        <h2 class="mb-4 mt-5">Contact</h2>
                        <p>If you have questions about these terms, please <a href="{{ route('contact') }}" class="text-decoration-none" style="color: var(--primary-color); font-weight: 600;">contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
