@extends('admin.layout')

@section('title', 'Manage Social Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Manage & Delete Social Posts</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-8">
                <label class="form-label">Pages / Accounts</label>
                <select class="form-select" id="pageSelector" multiple>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Use Ctrl/Cmd to select multiple pages.</small>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary" id="fetchPostsBtn">Fetch Posts</button>
                <button class="btn btn-outline-danger" id="bulkDeleteBtn" disabled>Bulk Delete</button>
            </div>
        </div>

        <div id="alerts" class="mb-2"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="postsTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllPosts"></th>
                        <th>Post ID</th>
                        <th>Platform</th>
                        <th>Page Name</th>
                        <th>Content</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="postsTableBody">
                    <tr><td colspan="8" class="text-center text-muted">Select one or more pages and click "Fetch Posts".</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const pageSelector = document.getElementById('pageSelector');
    const fetchPostsBtn = document.getElementById('fetchPostsBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const tableBody = document.getElementById('postsTableBody');
    const alerts = document.getElementById('alerts');
    const selectAllPosts = document.getElementById('selectAllPosts');
    const statusPollMap = new Map();

    const getSelectedPageIds = () => [...pageSelector.selectedOptions].map(opt => Number(opt.value));
    const decodePayload = (encoded) => JSON.parse(decodeURIComponent(escape(window.atob(encoded))));
    const encodePayload = (payload) => window.btoa(unescape(encodeURIComponent(JSON.stringify(payload))));
    const getSelectedRows = () => [...document.querySelectorAll('.post-checkbox:checked')].map(chk => decodePayload(chk.dataset.payload));

    const showAlert = (message, type = 'info') => {
        alerts.innerHTML = `<div class="alert alert-${type} py-2 mb-2">${message}</div>`;
    };

    const statusLabel = (status) => {
        if (status === 'pending') return '<span class="badge text-bg-secondary">Scheduled</span>';
        if (status === 'processing') return '<span class="badge text-bg-warning">In Progress</span>';
        if (status === 'completed') return '<span class="badge text-bg-success">Deleted</span>';
        if (status === 'failed') return '<span class="badge text-bg-danger">Failed</span>';

        return '<span class="badge text-bg-light text-dark">-</span>';
    };

    const renderPosts = (posts) => {
        if (!posts.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No posts found.</td></tr>';
            return;
        }

        tableBody.innerHTML = posts.map((post, index) => {
            const encoded = encodePayload(post);
            return `
            <tr id="row-${index}">
                <td><input type="checkbox" class="post-checkbox" data-payload="${encoded}"></td>
                <td class="small">${post.external_post_id}</td>
                <td><span class="badge text-bg-info text-capitalize">${post.platform}</span></td>
                <td>${post.page_name}</td>
                <td>
                    ${post.content ? `<div>${post.content.substring(0, 120)}</div>` : '<span class="text-muted">No text</span>'}
                    ${post.media_preview_url ? `<div class="mt-1"><img src="${post.media_preview_url}" alt="preview" style="max-width:70px; max-height:70px; border-radius:6px;"></div>` : ''}
                </td>
                <td>${post.created_time || '-'}</td>
                <td class="deletion-status">${statusLabel('none')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger single-delete-btn" data-payload="${encoded}">Delete</button>
                </td>
            </tr>
        `;
        }).join('');

        bindRowHandlers();
    };

    const refreshBulkButton = () => {
        const selected = document.querySelectorAll('.post-checkbox:checked').length;
        bulkDeleteBtn.disabled = selected === 0;
        bulkDeleteBtn.textContent = selected ? `Bulk Delete (${selected})` : 'Bulk Delete';
    };

    const startPollingStatuses = (jobIds = []) => {
        const ids = jobIds.filter(Boolean);
        if (!ids.length) return;

        ids.forEach(id => statusPollMap.set(id, true));

        const tick = async () => {
            const activeIds = [...statusPollMap.keys()];
            if (!activeIds.length) return;

            const response = await fetch(`{{ route('admin.facebook.manage-posts.statuses') }}?ids=${activeIds.join(',')}`);
            const payload = await response.json();
            (payload.jobs || []).forEach(job => {
                const rowBtn = document.querySelector(`.single-delete-btn[data-job-id="${job.id}"]`);
                const row = rowBtn?.closest('tr');
                if (!row) return;

                const statusCell = row.querySelector('.deletion-status');
                statusCell.innerHTML = statusLabel(job.status);

                if (job.status === 'completed' || job.status === 'failed') {
                    statusPollMap.delete(job.id);
                    if (job.status === 'failed' && job.error_message) {
                        statusCell.innerHTML += `<div class="small text-danger">${job.error_message}</div>`;
                    }
                }
            });

            if (statusPollMap.size > 0) {
                setTimeout(tick, 5000);
            }
        };

        tick();
    };

    const queueDelete = async (posts) => {
        if (!posts.length) return;

        const response = await fetch(`{{ route('admin.facebook.manage-posts.delete') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ posts }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to schedule deletions.');
        }

        const jobs = payload.jobs || [];

        jobs.forEach(job => {
            const rowBtn = [...document.querySelectorAll('.single-delete-btn')].find(btn => {
                const rowPayload = decodePayload(btn.dataset.payload);
                return rowPayload.external_post_id === job.external_post_id
                    && rowPayload.platform === job.platform;
            });

            if (!rowBtn) return;

            rowBtn.dataset.jobId = String(job.id);
            rowBtn.disabled = true;
            rowBtn.textContent = 'Scheduled';
            rowBtn.closest('tr').querySelector('.deletion-status').innerHTML = statusLabel(job.status);
        });

        startPollingStatuses(jobs.map(job => job.id));
        showAlert(payload.message || 'Deletion scheduled.', 'success');
    };

    const bindRowHandlers = () => {
        document.querySelectorAll('.post-checkbox').forEach(chk => {
            chk.addEventListener('change', refreshBulkButton);
        });

        document.querySelectorAll('.single-delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const post = decodePayload(btn.dataset.payload);
                if (!window.confirm(`Schedule deletion for ${post.external_post_id}?`)) return;

                try {
                    await queueDelete([post]);
                } catch (error) {
                    showAlert(error.message || 'Deletion scheduling failed.', 'danger');
                }
            });
        });
    };

    fetchPostsBtn.addEventListener('click', async () => {
        const pageIds = getSelectedPageIds();
        if (!pageIds.length) {
            showAlert('Please select at least one page/account.', 'warning');
            return;
        }

        fetchPostsBtn.disabled = true;
        tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading posts...</td></tr>';

        try {
            const response = await fetch(`{{ route('admin.facebook.manage-posts.fetch') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ page_ids: pageIds }),
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to fetch posts.');
            }

            renderPosts(payload.posts || []);
            refreshBulkButton();

            if ((payload.errors || []).length) {
                showAlert(`Posts loaded with warnings: ${(payload.errors || []).join(' | ')}`, 'warning');
            } else {
                showAlert(`Fetched ${(payload.posts || []).length} post(s).`, 'success');
            }
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load posts.</td></tr>';
            showAlert(error.message || 'Failed to fetch posts.', 'danger');
        } finally {
            fetchPostsBtn.disabled = false;
        }
    });

    bulkDeleteBtn.addEventListener('click', async () => {
        const selected = getSelectedRows();
        if (!selected.length) return;

        if (!window.confirm(`Schedule deletion for ${selected.length} selected post(s)?`)) {
            return;
        }

        try {
            await queueDelete(selected);
        } catch (error) {
            showAlert(error.message || 'Bulk deletion scheduling failed.', 'danger');
        }
    });

    selectAllPosts?.addEventListener('change', () => {
        document.querySelectorAll('.post-checkbox').forEach(chk => chk.checked = selectAllPosts.checked);
        refreshBulkButton();
    });
});
</script>
@endpush
