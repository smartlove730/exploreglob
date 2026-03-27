@extends('admin.layout')

@section('title', 'Add Drive Folder')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Add Drive Folder</h1>
        <form method="POST" action="{{ route('admin.facebook.drive-folders.store') }}">
            @csrf
            @include('admin.drive-folders.partials.form')
        </form>
    </div>
</div>
@endsection
