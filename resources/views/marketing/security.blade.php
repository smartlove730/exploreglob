@extends('marketing.layout')
@section('title', 'Security | ExploreGlob')
@section('content')
<section class="mx-auto w-full max-w-7xl px-4 pb-12 pt-16 sm:px-6 lg:px-8"><h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">Your accounts stay protected at every step</h1></section>
<section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pb-24 sm:px-6 md:grid-cols-2 lg:px-8">
@foreach([
['Encrypted connections','All traffic is encrypted in transit.'],
['OAuth secure login','Connect Facebook and Instagram without sharing passwords.'],
['Role-based permissions','Set permissions for creators, editors, and approvers.'],
['Workspace access control','Limit visibility per client or brand workspace.'],
['Cloud infrastructure reliability','Monitored workers keep queued posts reliable.'],
] as [$title,$copy])
<article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm md:odd:translate-y-1"><h2 class="text-xl font-bold text-slate-900">{{ $title }}</h2><p class="mt-3 text-sm text-slate-600">{{ $copy }}</p></article>
@endforeach
</section>
@endsection
