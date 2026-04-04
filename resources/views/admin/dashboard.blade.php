@extends('admin.layout')

@section('title','Dashboard')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h3 mb-2">{{ auth()->user()?->isAdmin() ? 'Admin Dashboard' : 'User Dashboard' }}</h1>
        <p class="text-muted mb-0">
            @if(auth()->user()?->isAdmin())
                Manage platform content, integrations, automation, and SaaS controls from one place.
            @else
                Manage your own integrations, app keys, automations, social posts, and subscription from one place.
            @endif
        </p>
    </div>
</div>
@endsection
