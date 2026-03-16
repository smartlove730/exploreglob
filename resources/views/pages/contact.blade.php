@extends('layouts.app')

@section('content')

<!-- Hero Section -->
<section class="hero-section" style="min-height: 30vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Contact Us</h1>
            <p class="hero-subtitle">Have a question or want to get in touch? We'd love to hear from you!</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="animated-card">
                <div class="card-body p-5">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="POST" action="{{ url('/contact/submit') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">Your Name</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" value="{{ old('name') }}" placeholder="Enter your name" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email') }}" placeholder="your.email@example.com" required>
                        </div>
                        <div class="mb-4">
                            <label for="subject" class="form-label fw-bold">Subject</label>
                            <input type="text" class="form-control form-control-lg" id="subject" name="subject" value="{{ old('subject') }}" placeholder="What's this about?" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label fw-bold">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" placeholder="Tell us what's on your mind..." required style="font-size: 1rem;">{{ old('message') }}</textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                📧 Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(session('contact_submitted'))
        <script>
            Swal.fire({
                title: 'Message sent!',
                text: 'Thanks for reaching out. We will get back to you soon.',
                icon: 'success',
                confirmButtonColor: '#6366f1'
            });
        </script>
    @endif
@endpush
@endsection
