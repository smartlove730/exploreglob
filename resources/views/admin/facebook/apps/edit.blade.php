@extends('admin.layout')

@section('title', 'Edit Facebook App')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Edit Facebook App</h1>
        <form method="POST" action="{{ route('admin.facebook.apps.update', $app) }}">
            @csrf
            @method('PUT')
            @include('admin.facebook.apps.partials.form', ['facebookApp' => $app])
        </form>
    </div>
</div>
@endsection
