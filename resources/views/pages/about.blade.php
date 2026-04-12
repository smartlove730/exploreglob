@extends('layouts.app')

@section('content')
    <section class="hero-section" style="min-height: 35vh;">
        <div class="container text-center">
            <div class="hero-content">
                <h1 class="hero-title text-white">Behind Postzy</h1>
                <p class="hero-subtitle">Bridging the gap between local insights and a global audience through technology and storytelling.</p>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="animated-card shadow-sm border-0" style="opacity:1;transform:none">
                    <div class="card-body p-5">
                        <h2 class="mb-4">Our Vision</h2>
                        <p class="lead text-muted">In an era of information overload, finding authentic, localized perspectives is harder than ever. Postzy was founded to solve this by creating a structured, AI-enhanced platform that organizes world knowledge by country, category, and lived experience.</p>
                        
                        <p>Our mission is to surface meaningful stories that often get lost in the noise. By combining advanced web technology with a passion for global culture, we provide a gateway for readers to explore everything from emerging tech trends to spiritual traditions and local cuisines across the globe.</p>

                        <div class="row mt-5">
                            <div class="col-md-6 mb-4">
                                <h4 class="h5 font-weight-bold">Expert-Driven Content</h4>
                                <p>We don't just aggregate data; we curate insights. Our platform focuses on deep-dive topics ensuring every article adds unique value to the reader.</p>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h4 class="h5 font-weight-bold">AI-Enhanced Accuracy</h4>
                                <p>Leveraging the power of modern AI and Laravel, we ensure our resources are up-to-date, factually grounded, and organized for maximum readability.</p>
                            </div>
                        </div>

                        <h2 class="mb-4 mt-4">Our Editorial Core (E-E-A-T)</h2>
                        <p>Adhering to the highest digital standards, we ensure every piece of content on Postzy meets four key pillars:</p>
                        <ul class="list-unstyled custom-list">
                            <li class="mb-3"><strong>✅ Experience:</strong> We prioritize stories backed by real-world interaction and on-the-ground research.</li>
                            <li class="mb-3"><strong>✅ Expertise:</strong> Our guides and deep-dives are written by those who live and breathe the subject matter.</li>
                            <li class="mb-3"><strong>✅ Authoritativeness:</strong> We provide clear sourcing and professional context for every claim we make.</li>
                            <li class="mb-3"><strong>✅ Trustworthiness:</strong> Your privacy and data security are paramount. We maintain transparent policies to ensure a safe reading environment.</li>
                        </ul>

                        <div class="p-4 rounded-4 mt-5 text-center" style="background:var(--pz-slate-100)">
                            <h3 class="h4">Join Our Journey</h3>
                            <p>Postzy is a constantly evolving project. If you are a writer, a developer, or a curious mind with a story to tell, we'd love to hear from you.</p>
                            <a href="{{ url('/contact') }}" class="btn btn-primary px-4 py-2 mt-2">Get In Touch</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
