@extends('layouts.app')

@section('content')
    <section class="hero-section" style="min-height: 35vh; display: flex; align-items: center; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('/path-to-your-banner.jpg') center/cover;">
        <div class="container text-center">
            <div class="hero-content">
                <h1 class="hero-title text-white">Behind Global Explorer</h1>
                <p class="hero-subtitle text-white-50">Bridging the gap between local insights and a global audience through technology and storytelling.</p>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="animated-card shadow-sm border-0">
                    <div class="card-body p-5">
                        <h2 class="mb-4">Our Vision</h2>
                        <p class="lead text-muted">In an era of information overload, finding authentic, localized perspectives is harder than ever. Global Explorer was founded in 2026 to solve this by creating a structured, AI-enhanced platform that organizes world knowledge by country, category, and lived experience.</p>
                        
                        <p>Our mission is to surface meaningful stories that often get lost in the noise. By combining advanced web technology with a passion for global culture, we provide a gateway for readers to explore everything from emerging tech trends in the US to spiritual traditions and local cuisines across the globe.</p>

                        <div class="row mt-5">
                            <div class="col-md-6 mb-4">
                                <h4 class="h5 font-weight-bold">Expert-Driven Content</h4>
                                <p>We don't just aggregate data; we curate insights. Our platform focuses on deep-dive topics like Web Development, Indian Mythology, and Global Business, ensuring every article adds unique value to the reader.</p>
                            </div>
                            <div class="col-md-6 mb-4">
                                <h4 class="h5 font-weight-bold">AI-Enhanced Accuracy</h4>
                                <p>Leveraging the power of the Gemini API and Laravel, we ensure our educational resources are up-to-date, factually grounded, and organized for maximum readability.</p>
                            </div>
                        </div>

                        <h2 class="mb-4 mt-4">Our Editorial Core (E-E-A-T)</h2>
                        <p>Adhering to the highest digital standards, we ensure every piece of content on Global Explorer meets four key pillars:</p>
                        <ul class="list-unstyled custom-list">
                            <li class="mb-3"><strong><i class="fas fa-check-circle text-success mr-2"></i> Experience:</strong> We prioritize stories backed by real-world interaction and on-the-ground research.</li>
                            <li class="mb-3"><strong><i class="fas fa-check-circle text-success mr-2"></i> Expertise:</strong> Our technical guides and cultural deep-dives are written by those who live and breathe the subject matter.</li>
                            <li class="mb-3"><strong><i class="fas fa-check-circle text-success mr-2"></i> Authoritativeness:</strong> We provide clear sourcing and professional context for every claim we make.</li>
                            <li class="mb-3"><strong><i class="fas fa-check-circle text-success mr-2"></i> Trustworthiness:</strong> Your privacy and data security are paramount. We maintain transparent policies to ensure a safe reading environment.</li>
                        </ul>

                        <div class="bg-light p-4 rounded mt-5 text-center">
                            <h3 class="h4">Join Our Journey</h3>
                            <p>Global Explorer is a constantly evolving project. If you are a writer, a developer, or a curious traveler with a story to tell, we’d love to hear from you.</p>
                            <a href="{{ route('contact') }}" class="btn btn-primary px-4 py-2 mt-2">Get In Touch</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
