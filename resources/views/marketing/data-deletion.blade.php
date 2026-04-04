@extends('marketing.layout')

@section('title', 'Data Deletion Instructions - ExploreGlob')

@section('content')
<div class="bg-white border rounded-3 p-4 p-md-5">
    <h1 class="h2 mb-2">Data Deletion Instructions</h1>
    <p class="text-muted mb-4">Last updated: {{ now()->format('F d, Y') }}</p>

    <p class="text-muted">If you connected ExploreGlob to Facebook (Meta), you can request deletion of your connected data at any time.</p>

    <h2 class="h5 mt-4">Option 1: Remove via Facebook Settings</h2>
    <ol class="text-muted">
        <li>Go to Facebook <strong>Settings &amp; privacy</strong> &gt; <strong>Settings</strong>.</li>
        <li>Open <strong>Apps and Websites</strong>.</li>
        <li>Select <strong>ExploreGlob</strong> and choose <strong>Remove</strong>.</li>
        <li>Confirm removal to revoke token access from our app.</li>
    </ol>

    <h2 class="h5 mt-4">Option 2: Request Deletion Directly</h2>
    <p class="text-muted">Submit a deletion request through our <a href="{{ route('marketing.contact') }}">Contact page</a> using the subject line <strong>Data Deletion Request</strong> and include the email used in your ExploreGlob account.</p>

    <h2 class="h5 mt-4">What We Delete</h2>
    <ul class="text-muted">
        <li>Stored Facebook access tokens and connected page references tied to your account.</li>
        <li>Associated app integration metadata needed only for publishing features.</li>
    </ul>

    <h2 class="h5 mt-4">Timeline</h2>
    <p class="text-muted mb-0">We process deletion requests within 7 business days, unless a longer retention period is required by law, fraud prevention, or financial compliance obligations.</p>
</div>
@endsection
