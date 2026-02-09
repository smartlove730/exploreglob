@extends('layouts.app')

@section('content')
    <section class="hero-section" style="min-height: 30vh;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">About Global Explorer</h1>
                <p class="hero-subtitle">Connecting readers with authentic voices, local insight, and global perspectives.</p>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="animated-card">
                    <div class="card-body p-5">
                        <h2 class="mb-4">Our Mission</h2>
                        <p>Global Explorer is a curated blog platform built to surface meaningful stories from around the world. We bring together writers, travelers, researchers, and local experts who share on-the-ground insights about culture, food, travel, history, and everyday life.</p>

                        <h2 class="mb-4 mt-5">Why We Exist</h2>
                        <p>We believe great stories help people understand each other. Our platform is designed to highlight credible, experience-driven content that showcases real places and real people. Whether you're planning a trip, learning about a new region, or simply exploring from home, Global Explorer helps you discover voices you can trust.</p>

                        <h2 class="mb-4 mt-5">Editorial Standards</h2>
                        <ul>
                            <li><strong>Experience:</strong> We prioritize writers with lived or on-location experience.</li>
                            <li><strong>Expertise:</strong> Articles are reviewed to ensure accuracy and clarity.</li>
                            <li><strong>Authoritativeness:</strong> We highlight author bios and sources when relevant.</li>
                            <li><strong>Trustworthiness:</strong> We maintain transparent policies and protect user data.</li>
                        </ul>

                        <h2 class="mb-4 mt-5">Get In Touch</h2>
                        <p>Have feedback, ideas, or collaboration requests? Visit our <a href="{{ route('contact') }}" class="text-decoration-none" style="color: var(--primary-color); font-weight: 600;">Contact Us</a> page and we’ll respond as quickly as possible.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
