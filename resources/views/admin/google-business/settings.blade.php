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
        <h2 class="h5">Business Profiles & Locations</h2>
        @if(isset($businessProfiles) && $businessProfiles->isNotEmpty())
            <div class="mb-3">
                <p class="text-muted mb-2">Business profiles connected via this Google login:</p>
                <ul class="mb-0">
                    @foreach($businessProfiles as $connectedAccount)
                        <li><small>{{ $connectedAccount->google_account_id }}</small></li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($locations->isEmpty())
            <p class="text-muted mb-0">No business locations available for the connected profiles.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Business Profile</th>
                            <th>Name</th>
                            <th>Location ID</th>
                            <th>Default</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($locations as $location)
                        <tr>
                            <td><small>{{ $location->googleAccount->google_account_id ?? '-' }}</small></td>
                            <td>{{ $location->name }}</td>
                            <td><small>{{ $location->location_id }}</small></td>
                            <td>
                                @if($location->is_default)
                                    <span class="badge text-bg-success">Default</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$location->is_default)
                                    <form method="POST" action="{{ route('admin.google.locations.default', $location) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Make Default</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
