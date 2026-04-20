@extends('admin.layout')

@section('title', 'Post History')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Post History</h1>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" disabled>Delete Selected</button>
        <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Create Post</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAllPosts" aria-label="Select all posts">
                        </th>
                        <th>App</th>
                        <th>Page</th>
                        <th>Message</th>
                        <th>Media</th>
                        <th>Images</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr id="post-row-{{ $post->id }}" data-post-id="{{ $post->id }}">
                            <td>
                                <input type="checkbox" class="post-checkbox" value="{{ $post->id }}" aria-label="Select post {{ $post->id }}">
                            </td>
                            <td>{{ $post->page?->facebookAccount?->app?->name ?? '-' }}</td>
                            <td>{{ $post->page?->page_name }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($post->message, 100) }}</td>
                            <td>
                                <span class="badge text-bg-{{ ($post->media_type ?? 'image') === 'video' ? 'warning' : 'info' }}">
                                    {{ ucfirst($post->media_type ?? 'image') }}
                                </span>
                            </td>
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

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger delete-post-btn"
                                    data-post-id="{{ $post->id }}"
                                >
                                    Delete
                                </button>
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
                        <tr><td colspan="9" class="text-center text-muted">No posts yet.</td></tr>
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
    const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content');
    const selectAll = document.getElementById('selectAllPosts');
    const checkboxes = Array.from(document.querySelectorAll('.post-checkbox'));
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    const getSelectedIds = () => checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => Number(checkbox.value));

    const refreshBulkState = () => {
        const selectedCount = getSelectedIds().length;
        bulkDeleteBtn.disabled = selectedCount === 0;
        bulkDeleteBtn.textContent = selectedCount > 0 ? `Delete Selected (${selectedCount})` : 'Delete Selected';
    };

    const markRowDeleting = (postId) => {
        const row = document.getElementById(`post-row-${postId}`);
        if (!row) return;

        row.classList.add('table-warning');
        const actionCell = row.querySelector('td:last-child');
        if (actionCell) {
            actionCell.innerHTML = '<span class=\"badge text-bg-warning\">Deleting...</span>';
        }
    };

    const removeRow = (postId) => {
        const row = document.getElementById(`post-row-${postId}`);
        if (row) {
            row.remove();
        }
    };

    const notify = (message, isError = false) => {
        if (isError) {
            window.alert(message);
            return;
        }
        window.alert(message);
    };

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            refreshBulkState();
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const selectedCount = getSelectedIds().length;
            if (selectAll) {
                selectAll.checked = selectedCount > 0 && selectedCount === checkboxes.length;
            }
            refreshBulkState();
        });
    });

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

    document.querySelectorAll('.delete-post-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const postId = Number(button.dataset.postId);
            if (!postId) return;

            if (!window.confirm('Delete this post from Facebook/Instagram and queue local deletion?')) {
                return;
            }

            markRowDeleting(postId);

            try {
                const response = await fetch(`{{ url('/admin/posts') }}/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Unable to queue deletion.');
                }

                removeRow(postId);
                refreshBulkState();
                notify(payload.message || 'Deletion queued.');
            } catch (error) {
                notify(error.message || 'Deletion failed.', true);
                window.location.reload();
            }
        });
    });

    bulkDeleteBtn?.addEventListener('click', async () => {
        const selectedIds = getSelectedIds();
        if (!selectedIds.length) return;

        if (!window.confirm(`Delete ${selectedIds.length} selected post(s)?`)) {
            return;
        }

        selectedIds.forEach((id) => markRowDeleting(id));

        try {
            const response = await fetch(`{{ route('admin.posts.bulk-destroy') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ post_ids: selectedIds }),
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Bulk deletion failed.');
            }

            (payload.accepted || []).forEach((id) => removeRow(Number(id)));
            refreshBulkState();

            if ((payload.skipped || []).length > 0 || (payload.not_found || []).length > 0) {
                notify(`${payload.message} Some posts were skipped/not found.`, true);
                return;
            }

            notify(payload.message || 'Bulk deletion queued.');
        } catch (error) {
            notify(error.message || 'Bulk deletion failed.', true);
            window.location.reload();
        }
    });

    refreshBulkState();
});
</script>
@endpush
