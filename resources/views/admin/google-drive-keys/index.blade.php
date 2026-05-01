@extends('admin.layout')

@section('title', 'Connect Google Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Connect Google Accounts</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.google-drive.connect') }}" class="btn btn-primary">Connect via OAuth</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table no-export="9">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Description</th>
                        <th>Connection Type</th>
                        <th>Redirect URL</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keys as $key)
                        <tr>
                            <td>{{ $key->id }}</td>
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
                            <td>{{ optional($key->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($key->updated_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-end no-export">
                                <a href="{{ route('admin.facebook.google-drive-keys.edit', $key) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.facebook.google-drive-keys.destroy', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this Google account connection?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted text-center">No Google accounts connected yet.</td></tr>
                    @endforelse
                </tbody>
        </x-data-table>

        <div class="mt-3">{{ $keys->links() }}</div>
    </div>
</div>
@endsection
