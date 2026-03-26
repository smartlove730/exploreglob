@extends('admin.layout')

@section('title', 'Facebook Post History')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Facebook Post History</h1>
    <a class="btn btn-primary" href="{{ route('admin.facebook.posts.create') }}">Create Post</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Posted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td>{{ $post->page?->page_name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($post->message, 100) }}</td>
                            <td><span class="badge text-bg-{{ $post->status === 'posted' ? 'success' : ($post->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucfirst($post->status) }}</span></td>
                            <td>{{ optional($post->scheduled_at)->format('M d, Y H:i') ?? '-' }}</td>
                            <td>{{ optional($post->posted_at)->format('M d, Y H:i') ?? '-' }}</td>
                            <td>
                                @if($post->status === 'failed')
                                    <form method="POST" action="{{ route('admin.facebook.posts.retry', $post) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary">Retry</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $posts->links() }}
    </div>
</div>
@endsection
