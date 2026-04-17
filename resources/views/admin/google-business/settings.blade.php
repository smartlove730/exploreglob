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
                <p class="mb-1"><span class="badge text-bg-success">Connected</span></p>
            @endif
            <p class="mb-2 text-muted">Account: {{ $account->google_account_id }}</p>
            <p class="mb-3 text-muted">Token expires: {{ optional($account->expires_at)->format('M d, Y H:i') }}</p>
            <button
                class="btn btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#syncBusinessModal"
                {{ ($connectedDriveAccounts ?? collect())->isEmpty() ? 'disabled' : '' }}
            >
                Sync Businesses
            </button>
        @else
            <p class="mb-0"><span class="badge text-bg-danger">Not Connected</span></p>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Connected Businesses</h2>
            <button
                class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#syncBusinessModal"
                {{ ($connectedDriveAccounts ?? collect())->isEmpty() ? 'disabled' : '' }}
            >
                Sync Businesses
            </button>
        </div>

        @if(empty($profiles ?? []))
            <p class="text-muted mb-0">No connected businesses found for this user. Click “Sync Businesses”.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Connected Gmail</th>
                            <th>Business Profile Name</th>
                            <th>Account Resource</th>
                            <th>Type</th>
                            <th>Locations</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($profiles as $profile)
                        @php
                            $accountData = (array) ($profile['account'] ?? []);
                            $profileLocations = collect($profile['locations'] ?? []);
                            $displayName = $accountData['accountName'] ?? $accountData['name'] ?? 'Unnamed';
                        @endphp
                        <tr>
                            <td>{{ $profile['connected_email'] ?? '-' }}</td>
                            <td>{{ $displayName }}</td>
                            <td><small>{{ $accountData['name'] ?? '-' }}</small></td>
                            <td>{{ $accountData['type'] ?? '-' }}</td>
                            <td>{{ $profileLocations->count() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Business Locations</h2>
        @if($locations->isEmpty())
            <p class="text-muted mb-0">No locations found. Connect and sync first.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location ID</th>
                            <th>Default</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($locations as $location)
                        <tr>
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

<div class="modal fade" id="syncBusinessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.google.sync-locations') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Sync Businesses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Select one connected Google account to fetch businesses for that account only.</p>
                    <select name="drive_api_key_id" class="form-select" required>
                        <option value="">Select connected account</option>
                        @foreach(($connectedDriveAccounts ?? collect()) as $driveAccount)
                            <option value="{{ $driveAccount->id }}">
                                {{ $driveAccount->email ?: $driveAccount->name ?: ('Account #'.$driveAccount->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Fetch Businesses</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
