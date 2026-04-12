@extends('marketing.layout')

@section('title', 'Data Deletion Instructions - Postzy')

@section('content')
<section class="hero-section" style="min-height:25vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title" style="font-size:2.5rem">Data Deletion Instructions</h1>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="animated-card" style="opacity:1;transform:none">
                <div class="card-body p-5">
                    <p class="text-muted mb-4">Last updated: {{ now()->format('F d, Y') }}</p>

                    <p class="text-muted">If you connected Postzy to Facebook (Meta), you can request deletion of your connected data at any time.</p>

                    <h2 class="h5 mt-4">Option 1: Remove via Facebook Settings</h2>
                    <ol class="text-muted">
                        <li>Go to Facebook <strong>Settings &amp; privacy</strong> &gt; <strong>Settings</strong>.</li>
                        <li>Open <strong>Apps and Websites</strong>.</li>
                        <li>Select <strong>Postzy</strong> and choose <strong>Remove</strong>.</li>
                        <li>Confirm removal to revoke token access from our app.</li>
                    </ol>

                    <h2 class="h5 mt-4">Option 2: Request Deletion Directly</h2>
                    <p class="text-muted">Submit a deletion request through our <a href="{{ route('marketing.contact') }}">Contact page</a> using the subject line <strong>Data Deletion Request</strong> and include the email used in your Postzy account.</p>

                    <h2 class="h5 mt-4">What We Delete</h2>
                    <ul class="text-muted">
                        <li>Stored Facebook access tokens and connected page references tied to your account.</li>
                        <li>Associated app integration metadata needed only for publishing features.</li>
                    </ul>

                    <h2 class="h5 mt-4">Timeline</h2>
                    <p class="text-muted mb-0">We process deletion requests within 7 business days, unless a longer retention period is required by law, fraud prevention, or financial compliance obligations.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
