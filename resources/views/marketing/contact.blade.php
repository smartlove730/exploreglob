@extends('marketing.layout')

@section('title', 'Contact - Postzy')

@section('content')
<section class="hero-section" style="min-height:30vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Contact Us</h1>
            <p class="hero-subtitle">Questions about plans, onboarding, or integrations? Send us a message.</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="animated-card" style="opacity:1;transform:none">
                <div class="card-body p-5">
                    <form method="POST" action="{{ route('marketing.contact.send') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Name</label>
                                <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Subject</label>
                                <input name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required>
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Message</label>
                                <textarea name="message" rows="6" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
