@extends('admin.layout')

@section('title', 'Google Drive Folders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Google Drive Folders</h1>
    <a href="{{ route('admin.facebook.drive-folders.create') }}" class="btn btn-primary">Add Folder</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Folder URL</th>
                        <th>Drive Key</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folders as $folder)
                        <tr>
                            <td>{{ $folder->name }}</td>
                            <td><small class="text-break">{{ $folder->folder_url }}</small></td>
                            <td>{{ $folder->driveApiKey?->name ?? '-' }}</td>
                            <td><span class="badge text-bg-{{ $folder->is_active ? 'success' : 'secondary' }}">{{ $folder->is_active ? 'Active' : 'Inactive' }}</span></td>
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
                        <tr><td colspan="5" class="text-center text-muted">No saved folders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $folders->links() }}
    </div>
</div>
@endsection
