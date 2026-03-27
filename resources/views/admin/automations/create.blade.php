@extends('admin.layout')

@section('title', 'Create Automation')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Create Automation</h1>
        <form method="POST" action="{{ route('admin.automations.store') }}">
            @csrf
            @include('admin.automations.partials.form')
        </form>
    </div>
</div>
@endsection
