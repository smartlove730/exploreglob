@extends('admin.layout')

@section('title', 'Post History')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Post History</h1>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-warning d-none" id="bulkRetryBtn">Retry Failed</button>
        <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" disabled>Delete Selected</button>
        <a class="btn btn-primary" href="{{ route('admin.posts.create') }}">Create Post</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.posts.index') }}" class="border rounded p-3 mb-3 bg-light-subtle">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Advanced Filters</h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-sm btn-primary">Apply Filters</button>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control form-control-sm" placeholder="ID or message" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="app_id">App</label>
                    <select id="app_id" name="app_id" class="form-select form-select-sm">
                        <option value="">All apps</option>
                        @foreach($apps as $app)
                            <option value="{{ $app->id }}" {{ (int) $filters['app_id'] === (int) $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="page_id">Page</label>
                    <select id="page_id" name="page_id" class="form-select form-select-sm">
                        <option value="">All pages</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}" data-app-id="{{ $page->facebook_app_id }}" {{ (int) $filters['page_id'] === (int) $page->id ? 'selected' : '' }}>{{ $page->page_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="status">Status</label>
                    <select id="status" name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="media_type">Media</label>
                    <select id="media_type" name="media_type" class="form-select form-select-sm">
                        <option value="">All media types</option>
                        @foreach($mediaTypeOptions as $mediaType)
                            <option value="{{ $mediaType }}" {{ $filters['media_type'] === $mediaType ? 'selected' : '' }}>{{ ucfirst($mediaType) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="platform">Platform</label>
                    <select id="platform" name="platform" class="form-select form-select-sm">
                        <option value="">All platforms</option>
                        @foreach($platformOptions as $platform)
                            <option value="{{ $platform }}" {{ $filters['platform'] === $platform ? 'selected' : '' }}>{{ str($platform)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="posted_from">Posted from</label>
                    <input type="date" id="posted_from" name="posted_from" class="form-control form-control-sm" value="{{ $filters['posted_from'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="posted_to">Posted to</label>
                    <input type="date" id="posted_to" name="posted_to" class="form-control form-control-sm" value="{{ $filters['posted_to'] }}">
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle table-hover" id="postHistoryTable">
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
                                <input type="checkbox" class="post-checkbox" data-status="{{ $post->status }}" value="{{ $post->id }}" aria-label="Select post {{ $post->id }}">
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
                                @if($post->status !== 'published')
                                    <form method="POST" action="{{ route('admin.posts.execute-now', $post->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Execute Immediately</button>
                                    </form>
                                @endif
                                @if($post->status === 'failed')
                                    <form method="POST" action="{{ route('admin.posts.retry', $post->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Retry Post</button>
                                    </form>
                                @endif
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

        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center">
            <small class="text-muted">
                Showing {{ $posts->firstItem() ?? 0 }} to {{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} posts
            </small>
            {{ $posts->links() }}
        </div>
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
    const bulkRetryBtn = document.getElementById('bulkRetryBtn');
    const appFilter = document.getElementById('app_id');
    const pageFilter = document.getElementById('page_id');

    const getSelectedIds = () => checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => Number(checkbox.value));
    const getSelectedFailedIds = () => checkboxes
        .filter((checkbox) => checkbox.checked && checkbox.dataset.status === 'failed')
        .map((checkbox) => Number(checkbox.value));

    const refreshBulkState = () => {
        const selectedCount = getSelectedIds().length;
        const selectedFailedCount = getSelectedFailedIds().length;
        bulkDeleteBtn.disabled = selectedCount === 0;
        bulkDeleteBtn.textContent = selectedCount > 0 ? `Delete Selected (${selectedCount})` : 'Delete Selected';

        if (!bulkRetryBtn) return;

        const hasFailedSelection = selectedFailedCount > 0;
        bulkRetryBtn.classList.toggle('d-none', !hasFailedSelection);
        bulkRetryBtn.disabled = !hasFailedSelection;
        bulkRetryBtn.textContent = hasFailedSelection ? `Retry Failed (${selectedFailedCount})` : 'Retry Failed';
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

    const syncPageOptionsByApp = () => {
        if (!appFilter || !pageFilter) return;

        const selectedAppId = Number(appFilter.value || 0);
        const options = Array.from(pageFilter.options);

        options.forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionAppId = Number(option.dataset.appId || 0);
            option.hidden = selectedAppId > 0 && optionAppId !== selectedAppId;
        });

        if (selectedAppId > 0) {
            const selectedOption = pageFilter.options[pageFilter.selectedIndex];
            if (selectedOption && selectedOption.hidden) {
                pageFilter.value = '';
            }
        }
    };

    appFilter?.addEventListener('change', syncPageOptionsByApp);
    syncPageOptionsByApp();

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
            checkboxes.forEach((checkbox) => {
                if (selectedIds.includes(Number(checkbox.value))) {
                    checkbox.checked = false;
                }
            });
            if (selectAll) {
                selectAll.checked = false;
            }
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

    bulkRetryBtn?.addEventListener('click', async () => {
        const selectedFailedIds = getSelectedFailedIds();
        if (!selectedFailedIds.length) return;

        if (!window.confirm(`Retry ${selectedFailedIds.length} failed post(s)? They will be scheduled with a 2-5 minute gap.`)) {
            return;
        }

        try {
            const response = await fetch(`{{ route('admin.posts.bulk-retry') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ post_ids: selectedFailedIds }),
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Bulk retry failed.');
            }

            notify(payload.message || 'Retry jobs queued.');
            window.location.reload();
        } catch (error) {
            notify(error.message || 'Bulk retry failed.', true);
        }
    });

    refreshBulkState();
});
</script>
@endpush
