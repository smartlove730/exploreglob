@extends('marketing.layout')
@section('title', 'Features | ExploreGlob')

@section('content')
<section style="padding:3rem 0 1.5rem;">
  <p class="eyebrow">Features</p>
  <h1 class="page-title">Run social media operations from one streamlined workspace</h1>
  <p class="lead">From planning to publishing, every feature is designed to remove busywork and improve team consistency.</p>
</section>

<section class="cards-2" style="padding-bottom:2rem;">
@foreach([
['Auto publishing engine',['Schedule once and publish everywhere','Handle recurring campaigns automatically','Reduce manual posting errors']],
['Visual content calendar',['Plan weeks ahead in one view','Drag and adjust dates quickly','Track campaign progress']],
['Multi-brand workspace',['Separate content by brand/client','Switch profiles instantly','Agency-ready layout']],
['Approvals and collaboration',['Invite team members securely','Assign draft/review states','Keep comments and edits in one place']],
['Performance dashboard',['See top posts by channel','Measure growth trends','Turn data into next-week actions']],
['Asset library',['Store captions and visuals together','Reuse winning assets','Save team production time']],
] as [$title,$items])
<article class="card">
    <h2>{{ $title }}</h2>
    <ul style="margin-top:.8rem;padding-left:1rem;display:grid;gap:.35rem;">
        @foreach($items as $item)<li>{{ $item }}</li>@endforeach
    </ul>
</article>
@endforeach
</section>

<section class="highlight" style="margin-bottom:2rem;">
    <p class="eyebrow" style="color:#e0e7ff;">Coming soon</p>
    <h3 style="font-size:1.7rem;font-weight:800;">Google Business Profile automation</h3>
    <p>Publish location-focused updates and promotions from the same ExploreGlob dashboard.</p>
</section>
@endsection
