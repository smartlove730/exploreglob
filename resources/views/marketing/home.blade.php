@extends('marketing.layout')

@section('title', 'ExploreGlob | Social Media Automation Platform')

@section('content')
<section class="hero-grid">
    <div>
        <p class="eyebrow">Automate every social post</p>
        <h1 class="hero-title">A cleaner, faster way to plan and publish your content calendar.</h1>
        <p class="lead">ExploreGlob gives your team one unified space for ideas, approvals, scheduling, and publishing across Facebook and Instagram.</p>
        <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-top:1.4rem;">
            <a href="{{ route('register') }}" class="cta-btn cta-btn-primary">Start Free Trial</a>
            <a href="{{ route('marketing.features') }}" class="cta-btn cta-btn-secondary">Explore Features</a>
        </div>
    </div>
    <div class="card">
        <h3>Publishing Dashboard</h3>
        <p class="lead" style="margin-top:.8rem;">Track queued posts, approvals, and engagement from one timeline.</p>
        <div class="cards-3" style="margin-top:1rem;">
            <div class="card"><strong>128</strong><p>Queued Posts</p></div>
            <div class="card"><strong>4.2k</strong><p>Published</p></div>
            <div class="card"><strong>8.9%</strong><p>Engagement</p></div>
        </div>
    </div>
</section>

<section class="band">
    <strong>Trusted by agencies, creators, and local brands.</strong>
    <p class="lead">StudioPilot · LocalMint · ScaleCraft · CreatorLoop</p>
</section>

<section style="padding:2rem 0;">
    <p class="eyebrow">Core capabilities</p>
    <h2 class="page-title">Everything your team needs to stay consistent online</h2>
    <div class="cards-3">
        @foreach([
            ['Auto publishing', 'Schedule once and publish automatically to all selected channels.'],
            ['Smart scheduling', 'Use best-time recommendations to improve post visibility.'],
            ['Team collaboration', 'Invite teammates and approve content before it goes live.'],
            ['Analytics snapshots', 'Review engagement trends without switching tools.'],
            ['Reusable templates', 'Turn your best post ideas into repeatable workflows.'],
            ['Calendar control', 'Plan monthly campaigns with a simple visual calendar.'],
        ] as [$title, $description])
            <article class="card">
                <h3>{{ $title }}</h3>
                <p>{{ $description }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="highlight" style="margin:2rem 0 2.5rem;">
    <h2 style="font-size:2rem;font-weight:800;">Launch campaigns faster with less manual work</h2>
    <p>Switch from scattered spreadsheets and reminders to automated workflows in minutes.</p>
    <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-top:1.2rem;">
        <a href="{{ route('register') }}" class="cta-btn cta-btn-secondary">Start Free Trial</a>
        <a href="{{ route('marketing.contact') }}" class="cta-btn" style="background:rgba(255,255,255,.18);color:#fff;">Talk to Sales</a>
    </div>
</section>
@endsection
