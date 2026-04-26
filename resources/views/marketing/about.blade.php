@extends('marketing.layout')

@section('title', 'About - Postzy')

@section('content')
<section class="hero-section" style="min-height:35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">About Postzy</h1>
            <p class="hero-subtitle">A modern platform for publishing consistent social content with less manual work.</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="animated-card" style="opacity:1;transform:none">
                <div class="card-body p-5">
                    <h2 class="card-title mb-3">Our Mission</h2>
                    <p class="card-text">Postzy is a lightweight SaaS platform focused on helping teams publish consistent social content with less manual work. We combine scheduling, media organization, and plan-based controls so businesses can scale publishing safely.</p>
                    <p class="card-text">Whether you're a solopreneur or a growing team, Postzy gives you the tools to manage your social presence across Facebook and Instagram — all in one place.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
