@extends('admin.layout')

@section('title', 'Connect Google Account Manually')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Connect Google Account Manually</h1>
        <form method="POST" action="{{ route('admin.facebook.google-drive-keys.store') }}">
            @csrf
            @include('admin.google-drive-keys.partials.form')
        </form>
    </div>
</div>
@endsection
