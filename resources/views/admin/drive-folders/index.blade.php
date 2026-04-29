@extends('admin.layout')

@section('title', 'Google Drive Folders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Google Drive Folders</h1>
    <form method="POST" action="{{ route('admin.facebook.drive-folders.sync') }}" class="d-inline">
        @csrf
        <button class="btn btn-primary">Sync Folders</button>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="document.querySelectorAll('.folder-select').forEach(cb => cb.checked = this.checked)"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>From Account</th>
                        <th>Connected Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folders as $folder)
                        <tr>
                            <td><input type="checkbox" class="folder-select" value="{{ $folder->id }}"></td>
                            <td>{{ $folder->id }}</td>
                            <td>{{ $folder->name }}</td>
                            <td>{{ $folder->driveApiKey?->name ?? '-' }}</td>
                            <td>{{ optional($folder->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.facebook.drive-folders.edit', $folder) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.facebook.drive-folders.destroy', $folder) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this folder?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No saved folders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $folders->links() }}
    </div>
</div>
@endsection
