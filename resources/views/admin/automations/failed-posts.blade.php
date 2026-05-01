@extends('admin.layout')

@section('title', 'Failed Automation Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Failed Automation Posts</h1>
    <a href="{{ route('admin.automations.index') }}" class="btn btn-outline-secondary">Back to Automations</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Automation</th>
                        <th>Drive File ID</th>
                        <th>Folder ID</th>
                        <th>Drive Media Link</th>
                        <th>Platform</th>
                        <th>Failure Reason</th>
                        <th>Status</th>
                        <th>Failed At</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failedPosts as $failedPost)
                        <tr>
                            <td>{{ $failedPost->id }}</td>
                            <td>{{ $failedPost->automation?->name ?: 'Automation #'.$failedPost->automation_id }}</td>
                            <td><code>{{ $failedPost->file_id }}</code></td>
                            <td><code>{{ $failedPost->folder_id ?: '-' }}</code></td>
                            <td>
                                <a href="{{ 'https://drive.google.com/file/d/'.$failedPost->file_id.'/view' }}" target="_blank" rel="noopener">
                                    Open media
                                </a>
                            </td>
                            <td>{{ $failedPost->platform ?: '-' }}</td>
                            <td class="small text-danger">{{ $failedPost->last_error ?: '-' }}</td>
                            <td><span class="badge text-bg-danger">{{ $failedPost->status }}</span></td>
                            <td>{{ ($failedPost->failed_at ?: $failedPost->updated_at)?->diffForHumans() }}</td>
                            <td>{{ optional($failedPost->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($failedPost->updated_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">No failed automation posts found.</td>
                        </tr>
                    @endforelse
                </tbody>
        </x-data-table>

        <div class="mt-3">{{ $failedPosts->links() }}</div>
    </div>
</div>
@endsection
