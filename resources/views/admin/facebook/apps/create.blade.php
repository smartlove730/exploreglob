@extends('admin.layout')

@section('title', 'Add Facebook App')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Add Facebook App</h1>
        <form method="POST" action="{{ route('admin.facebook.apps.store') }}">
            @csrf
            @include('admin.facebook.apps.partials.form')
        </form>
    </div>
</div>
@endsection
