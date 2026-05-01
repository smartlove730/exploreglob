@extends('admin.layout')

@section('title', 'Edit Google Account Connection')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Edit Google Account Connection</h1>
        <form method="POST" action="{{ route('admin.facebook.google-drive-keys.update', $driveKey) }}">
            @csrf
            @method('PUT')
            @include('admin.google-drive-keys.partials.form', ['driveKey' => $driveKey])
        </form>
    </div>
</div>
@endsection
