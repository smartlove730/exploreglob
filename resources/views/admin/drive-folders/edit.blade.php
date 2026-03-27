@extends('admin.layout')

@section('title', 'Edit Drive Folder')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Edit Drive Folder</h1>
        <form method="POST" action="{{ route('admin.facebook.drive-folders.update', $driveFolder) }}">
            @csrf
            @method('PUT')
            @include('admin.drive-folders.partials.form', ['driveFolder' => $driveFolder])
        </form>
    </div>
</div>
@endsection
