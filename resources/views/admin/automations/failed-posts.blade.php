@extends('admin.layout')

@section('title', 'Failed Automation Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Failed Automation Posts</h1>
    <a href="{{ route('admin.automations.index') }}" class="btn btn-outline-secondary">Back to Automations</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Automation</th>
                        <th>Drive File ID</th>
                        <th>Folder ID</th>
                        <th>Drive Media Link</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failedPosts as $failedPost)
                        <tr>
                            <td>{{ $failedPost->automation?->name ?: 'Automation #'.$failedPost->automation_id }}</td>
                            <td><code>{{ $failedPost->file_id }}</code></td>
                            <td><code>{{ $failedPost->folder_id ?: '-' }}</code></td>
                            <td>
                                <a href="{{ 'https://drive.google.com/file/d/'.$failedPost->file_id.'/view' }}" target="_blank" rel="noopener">
                                    Open media
                                </a>
                            </td>
                            <td><span class="badge text-bg-danger">{{ $failedPost->status }}</span></td>
                            <td>{{ $failedPost->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No failed automation posts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $failedPosts->links() }}
    </div>
</div>
@endsection
