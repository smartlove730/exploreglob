@extends('admin.layout')

@section('title', 'Post History')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Post History</h1>
    <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Create Post</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>App</th>
                        <th>Page</th>
                        <th>Message</th>
                        <th>Images</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td>{{ $post->page?->facebookAccount?->app?->name ?? '-' }}</td>
                            <td>{{ $post->page?->page_name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($post->message, 100) }}</td>
                            <td>{{ $post->images->count() }}</td>
                            <td><span class="badge text-bg-{{ $post->status === 'published' ? 'success' : 'secondary' }}">{{ ucfirst($post->status) }}</span></td>
                            <td>{{ optional($post->posted_at)->format('M d, Y H:i') ?? '-' }}</td>
                            <td class="d-flex gap-1">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPostModal{{ $post->id }}"
                                >
                                    Edit
                                </button>

                                <form method="POST" action="{{ route('admin.posts.destroy', $post->id) }}" onsubmit="return confirm('Delete this post permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editPostModal{{ $post->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.posts.update', $post->id) }}" enctype="multipart/form-data" class="edit-post-form" data-status="{{ $post->status }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Post #{{ $post->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="app_id" value="{{ $post->page->facebook_app_id }}">
                                            <input type="hidden" name="page_id" value="{{ $post->page_id }}">

                                            <div class="mb-3">
                                                <label class="form-label">Message</label>
                                                <textarea name="message" rows="5" class="form-control" required>{{ $post->message }}</textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Platforms</label>
                                                <div>
                                                    <label class="me-3"><input type="checkbox" name="platforms[]" value="facebook" {{ in_array('facebook', $post->platforms ?? [], true) ? 'checked' : '' }}> Facebook</label>
                                                    <label class="me-3"><input type="checkbox" name="platforms[]" value="instagram" {{ in_array('instagram', $post->platforms ?? [], true) ? 'checked' : '' }}> Instagram</label>
                                                    <label><input type="checkbox" name="platforms[]" value="google_business" {{ in_array('google_business', $post->platforms ?? [], true) ? 'checked' : '' }}> Google Business</label>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Image URL (optional)</label>
                                                <input type="url" name="image_url" class="form-control" value="{{ $post->image_url }}">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Existing Images</label>
                                                @if($post->images->isEmpty())
                                                    <p class="text-muted mb-0">No uploaded images.</p>
                                                @else
                                                    <div class="row g-2">
                                                        @foreach($post->images as $image)
                                                            <div class="col-md-4">
                                                                <div class="border rounded p-2">
                                                                    <img src="{{ asset('storage/'.$image->image_path) }}" class="img-fluid rounded mb-2" alt="Post image">
                                                                    <label class="form-check-label small">
                                                                        <input class="form-check-input" type="checkbox" name="remove_images[]" value="{{ $image->id }}">
                                                                        Remove this image
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Add New Images</label>
                                                <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Update Post</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $posts->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.edit-post-form').forEach(form => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.status !== 'published') return;

            const confirmed = window.confirm(
                'Updating a post is not fully supported by Facebook/Instagram APIs. If you continue, the existing post will be deleted and a new post will be published with updated content.'
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
});
</script>
@endpush
