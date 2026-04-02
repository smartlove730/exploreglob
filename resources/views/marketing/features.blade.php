@extends('marketing.layout')
@section('title', 'Features | ExploreGlob')
@section('content')
<section class="mx-auto w-full max-w-7xl px-4 pb-14 pt-16 sm:px-6 lg:px-8">
  <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-700">Features</p>
  <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">Everything you need to run social media on autopilot</h1>
</section>
<section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-20 sm:px-6 lg:grid-cols-2 lg:px-8">
@foreach([
['Auto publishing engine',['Schedule once','Publish everywhere','Never miss posting again']],
['Visual content calendar',['Drag and drop planner','Monthly overview','Campaign scheduling']],
['Multi-account manager',['Manage multiple brands','Switch accounts instantly','Agency friendly']],
['Team collaboration',['Invite teammates','Assign roles','Approve content before publishing']],
['Analytics dashboard',['Track engagement','Measure growth','Optimize posting strategy']],
] as [$title,$items])
<article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm"><h2 class="text-2xl font-bold text-slate-900">{{ $title }}</h2><ul class="mt-4 space-y-2 text-sm text-slate-600">@foreach($items as $item)<li>• {{ $item }}</li>@endforeach</ul></article>
@endforeach
</section>
<section class="bg-gradient-to-r from-indigo-600 to-violet-600"><div class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8"><p class="inline-flex rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white">Coming soon</p><h3 class="mt-4 text-3xl font-bold text-white">Google Business Profile posting</h3><p class="mt-2 text-indigo-100">Expand local visibility with automated Google Business Profile updates.</p></div></section>
@endsection
