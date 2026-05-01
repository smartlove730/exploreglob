@extends('admin.layout')

@section('title', 'Facebook Apps')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Facebook Apps</h1>
    <a href="{{ route('admin.facebook.apps.create') }}" class="btn btn-primary">Add App</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table no-export="7">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>App ID</th>
                        <th>Redirect URI</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apps as $app)
                        <tr>
                            <td>{{ $app->id }}</td>
                            <td>{{ $app->name }}</td>
                            <td><code>{{ $app->app_id }}</code></td>
                            <td><small>{{ $app->redirect_uri }}</small></td>
                            <td>
                                <span class="badge text-bg-{{ $app->is_active ? 'success' : 'secondary' }}">
                                    {{ $app->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ optional($app->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($app->updated_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-end no-export">
                                <a href="{{ route('admin.facebook.apps.edit', $app) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.facebook.apps.destroy', $app) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this app?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted text-center">No Facebook apps found.</td></tr>
                    @endforelse
                </tbody>
        </x-data-table>

        <div class="mt-3">{{ $apps->links() }}</div>
    </div>
</div>
@endsection
