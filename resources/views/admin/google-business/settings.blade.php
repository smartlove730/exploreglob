@extends('admin.layout')

@section('title', 'Google Business Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Google Business Settings</h1>
    <a href="{{ route('admin.google.connect') }}" class="btn btn-primary">Connect Google</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        @if($account)
            @if($account->reauthorization_required)
                <p class="mb-1"><span class="badge text-bg-warning">Reconnect Required</span></p>
                <p class="mb-2 text-muted">{{ $account->reauthorization_reason ?: 'Google token refresh failed. Reconnect your account to continue posting/syncing.' }}</p>
                <a href="{{ route('admin.google.connect') }}" class="btn btn-sm btn-primary mb-2">Reconnect Google</a>
            @else
                <p class="mb-1"><span class="badge {{ $businessProfiles->isNotEmpty() ? 'text-bg-success' : 'text-bg-warning' }}">{{ $businessProfiles->isNotEmpty() ? 'Connected' : 'OAuth Connected (No Business Profiles)' }}</span></p>
            @endif
            <p class="mb-2 text-muted">Connected profiles: {{ $accounts->count() }}</p>
            <p class="mb-2 text-muted">Active account: {{ $account->google_account_id }}</p>
            <p class="mb-3 text-muted">Token expires: {{ optional($account->expires_at)->format('M d, Y H:i') }}</p>
            <form method="POST" action="{{ route('admin.google.sync-locations') }}">
                @csrf
                <button class="btn btn-outline-secondary">Sync Locations</button>
            </form>
        @else
            <p class="mb-0"><span class="badge text-bg-danger">Not Connected</span></p>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Business Profiles</h2>
        @if(isset($businessProfiles) && $businessProfiles->isNotEmpty())
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Profile ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($businessProfiles as $connectedAccount)
                        <tr>
                            <td><small>{{ $connectedAccount->google_account_id }}</small></td>
                            <td><span class="badge text-bg-success">Connected</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">No Business Profiles found for this Google account. Please reconnect with the correct account.</p>
        @endif
    </div>
</div>
@endsection
