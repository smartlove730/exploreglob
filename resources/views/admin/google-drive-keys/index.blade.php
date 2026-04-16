@extends('admin.layout')

@section('title', 'Google Drive Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Google Drive Accounts</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.google.connect') }}" class="btn btn-primary">Connect via OAuth</a>
        <a href="{{ route('admin.facebook.google-drive-keys.create') }}" class="btn btn-outline-secondary">Add Manually</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Description</th>
                        <th>Auth Mode</th>
                        <th>Redirect URL</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keys as $key)
                        <tr>
                            <td>{{ $key->name }}</td>
                            <td>{{ $key->email ?: '-' }}</td>
                            <td><small>{{ $key->description ?: '-' }}</small></td>
                            <td><small>{{ $key->oauth_refresh_token ? 'OAuth' : 'Manual API Key' }}</small></td>
                            <td><small>{{ $key->redirect_url ?: config('services.google.redirect_uri') ?: route('admin.google-drive.callback') }}</small></td>
                            <td>
                                <span class="badge text-bg-{{ $key->is_active ? 'success' : 'secondary' }}">
                                    {{ $key->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.facebook.google-drive-keys.edit', $key) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.facebook.google-drive-keys.destroy', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this key?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center">No Google Drive keys found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $keys->links() }}
    </div>
</div>
@endsection
