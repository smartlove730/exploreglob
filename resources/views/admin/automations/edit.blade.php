@extends('admin.layout')

@section('title', 'Edit Automation')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Edit Automation</h1>
        <form method="POST" action="{{ route('admin.automations.update', $automation) }}">
            @csrf
            @method('PUT')
            @include('admin.automations.partials.form')
        </form>
    </div>
</div>
@endsection
