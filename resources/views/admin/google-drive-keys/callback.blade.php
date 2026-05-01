@extends('admin.layout')

@section('title', 'Google Account Callback')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-3">Google Account Callback</h1>

        @if($error)
            <div class="alert alert-danger">Google returned an error: {{ $error }}</div>
        @elseif($code)
            <div class="alert alert-success">Authorization code received successfully.</div>
            <p class="mb-2"><strong>Code:</strong></p>
            <textarea class="form-control" rows="4" readonly>{{ $code }}</textarea>
            @if($scope)
                <p class="mt-3 mb-1"><strong>Scope:</strong></p>
                <code>{{ $scope }}</code>
            @endif
        @else
            <div class="alert alert-info mb-0">Callback endpoint is active. Use this URL in Google Cloud redirect URL settings.</div>
        @endif
    </div>
</div>
@endsection
