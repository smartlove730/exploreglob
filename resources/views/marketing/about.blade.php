@extends('marketing.layout')
@section('title', 'About | ExploreGlob')

@section('content')
<section style="padding:3rem 0 1.2rem;">
    <p class="eyebrow">About ExploreGlob</p>
    <h1 class="page-title">We help teams publish consistently without burnout.</h1>
    <p class="lead">ExploreGlob was built to replace fragmented social workflows with one clear operating system for planning, automation, and collaboration.</p>
</section>

<section class="card" style="margin-bottom:1.2rem;">
    <h2 style="font-size:1.6rem;font-weight:800;">Why we started</h2>
    <p>Most teams were juggling spreadsheets, scattered notes, and manual reminders. We built ExploreGlob so any business can run social campaigns with the same confidence and consistency as top agencies.</p>
</section>

<section style="padding:0 0 1.2rem;">
    <h2 style="font-size:1.6rem;font-weight:800;">Our values</h2>
    <div class="cards-4" style="margin-top:.85rem;">
        @foreach(['Automation-first mindset','Simplicity in every workflow','Reliable publishing infrastructure','Customer-focused execution'] as $value)
            <article class="card"><h3>{{ $value }}</h3></article>
        @endforeach
    </div>
</section>

<section style="padding-bottom:2rem;">
    <h2 style="font-size:1.6rem;font-weight:800;">Team highlights</h2>
    <div class="cards-4" style="margin-top:.85rem;">
        @foreach(['Product','Engineering','Customer Success','Growth'] as $role)
            <article class="card" style="text-align:center;">
                <div style="width:64px;height:64px;margin:0 auto;border-radius:999px;background:linear-gradient(130deg,#4f46e5,#7c3aed);"></div>
                <h3 style="margin-top:.8rem;">{{ $role }} Lead</h3>
                <p>Team member placeholder</p>
            </article>
        @endforeach
    </div>
</section>
@endsection
