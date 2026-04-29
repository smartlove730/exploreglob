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
        <form method="POST" action="{{ route('admin.facebook.drive-folders.bulk-status') }}" id="bulk-status-form" class="mb-3 d-flex gap-2">
            @csrf
            <select name="status" class="form-select" style="max-width: 220px;" required>
                <option value="">Change selected status...</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="submit" class="btn btn-outline-primary">Apply</button>
        </form>

        <div class="table-responsive">
            <table class="table align-middle" id="driveFoldersTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-folders"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>From Account</th>
                        <th>Connected Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folders as $folder)
                        <tr>
                            <td><input type="checkbox" class="folder-select" name="folder_ids[]" value="{{ $folder->id }}" form="bulk-status-form"></td>
                            <td>{{ $folder->id }}</td>
                            <td>{{ $folder->name }}</td>
                            <td>{{ $folder->driveApiKey?->name ?? '-' }}</td>
                            <td>{{ optional($folder->created_at)->format('Y-m-d H:i') }}</td>
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
                        <tr><td colspan="7" class="text-center text-muted">No saved folders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.jQuery?.fn?.DataTable) {
        window.jQuery('#driveFoldersTable').DataTable({
            pageLength: 25,
            order: [[1, 'desc']],
            columnDefs: [{ targets: [0, 6], orderable: false }],
        });
    }

    const selectAll = document.getElementById('select-all-folders');
    selectAll?.addEventListener('change', () => {
        document.querySelectorAll('.folder-select').forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
    });
});
</script>
@endpush
