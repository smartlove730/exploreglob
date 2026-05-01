@extends('admin.layout')

@section('title', 'Manage Social Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Manage & Delete Social Posts</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-6">
                <label class="form-label">Pages / Accounts</label>
                <select class="form-select" id="pageSelector" multiple>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Use Ctrl/Cmd to select multiple pages.</small>
            </div>
            <div class="col-lg-6 d-flex gap-2">
                <button class="btn btn-outline-primary" id="syncPostsBtn">Sync Posts</button>
                <button class="btn btn-primary" id="loadPostsBtn">Load Posts</button>
                <button class="btn btn-outline-danger" id="bulkDeleteBtn" disabled>Bulk Delete</button>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small">Platform</label>
                <select id="platformFilter" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Search Content / ID</label>
                <input type="text" id="searchFilter" class="form-control form-control-sm" placeholder="Keyword">
            </div>
            <div class="col-md-2">
                <label class="form-label small">External Post ID</label>
                <input type="text" id="postIdFilter" class="form-control form-control-sm" placeholder="e.g. 123_456">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Created From</label>
                <input type="date" id="createdFromFilter" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Created To</label>
                <input type="date" id="createdToFilter" class="form-control form-control-sm">
            </div>
        </div>

        <div id="alerts" class="mb-2"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle data-table" id="postsTable" data-no-export="0,10">
                <thead>
                    <tr>
                        <th class="no-export"><input type="checkbox" id="selectAllPosts"></th>
                        <th>ID</th>
                        <th>Post ID</th>
                        <th>Platform</th>
                        <th>Page Name</th>
                        <th>Content</th>
                        <th>Created</th>
                        <th>Synced At</th>
                        <th>Updated At</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody id="postsTableBody">
                    <tr><td colspan="11" class="text-center text-muted">Sync and load posts to start managing them.</td></tr>
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
    const syncPostsBtn = document.getElementById('syncPostsBtn');
    const loadPostsBtn = document.getElementById('loadPostsBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const tableBody = document.getElementById('postsTableBody');
    const alerts = document.getElementById('alerts');
    const selectAllPosts = document.getElementById('selectAllPosts');
    const statusPollMap = new Map();
    let dataTable = null;

    const getSelectedPageIds = () => [...pageSelector.selectedOptions].map(opt => Number(opt.value));
    const selectedPostIds = () => [...document.querySelectorAll('.post-checkbox:checked')].map(chk => Number(chk.value));

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

    const refreshBulkButton = () => {
        const selected = selectedPostIds().length;
        bulkDeleteBtn.disabled = selected === 0;
        bulkDeleteBtn.textContent = selected ? `Bulk Delete (${selected})` : 'Bulk Delete';
    };

    const initializeDataTable = () => {
        if (!window.jQuery || !window.jQuery.fn.DataTable) {
            return;
        }

        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }

        dataTable = window.jQuery('#postsTable').DataTable({
            order: [[6, 'desc']],
            pageLength: 25,
            responsive: true,
            dom: "<'row g-2 align-items-center mb-2'<'col-md-6'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row g-2 align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
            buttons: [
                { extend: 'copy', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'csv', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'excel', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'print', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
            ],
            columnDefs: [
                { orderable: false, searchable: false, className: 'no-export', targets: [0, 10] },
                { orderable: false, targets: [9] },
            ],
        });
    };

    const renderPosts = (posts) => {
        if (!posts.length) {
            if (dataTable) {
                dataTable.destroy();
                dataTable = null;
            }

            tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">No posts found in database.</td></tr>';
            return;
        }

        tableBody.innerHTML = posts.map((post) => `
            <tr data-post-id="${post.id}">
                <td class="no-export"><input type="checkbox" class="post-checkbox" value="${post.id}"></td>
                <td>${post.id}</td>
                <td class="small">${post.external_post_id}</td>
                <td><span class="badge text-bg-info text-capitalize">${post.platform}</span></td>
                <td>${post.page_name || '-'}</td>
                <td class="no-export">
                    ${post.content ? `<div>${post.content.substring(0, 120)}</div>` : '<span class="text-muted">No text</span>'}
                    ${post.media_preview_url ? `<div class="mt-1"><img src="${post.media_preview_url}" alt="preview" style="max-width:70px; max-height:70px; border-radius:6px;"></div>` : ''}
                </td>
                <td>${post.created_time || '-'}</td>
                <td>${post.created_at || '-'}</td>
                <td>${post.updated_at || '-'}</td>
                <td class="deletion-status">${statusLabel('none')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger single-delete-btn" data-post-id="${post.id}">Delete</button>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.post-checkbox').forEach(chk => chk.addEventListener('change', refreshBulkButton));
        document.querySelectorAll('.single-delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const postId = Number(btn.dataset.postId || 0);
                if (!postId) return;
                if (!window.confirm('Schedule deletion for this post?')) return;

                await queueDelete([postId]);
            });
        });

        initializeDataTable();
        refreshBulkButton();
    };

    const loadPosts = async () => {
        const pageIds = getSelectedPageIds();

        const response = await fetch(`{{ route('admin.facebook.manage-posts.list') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                page_ids: pageIds,
                platform: document.getElementById('platformFilter').value || null,
                search: document.getElementById('searchFilter').value || null,
                external_post_id: document.getElementById('postIdFilter').value || null,
                created_from: document.getElementById('createdFromFilter').value || null,
                created_to: document.getElementById('createdToFilter').value || null,
            }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load posts from database.');
        }

        renderPosts(payload.posts || []);
        showAlert(`Loaded ${(payload.posts || []).length} posts from database.`, 'success');
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
                const row = document.querySelector(`tr[data-post-id="${job.synced_social_post_id}"]`);
                if (!row) {
                    statusPollMap.delete(job.id);
                    return;
                }

                const statusCell = row.querySelector('.deletion-status');
                statusCell.innerHTML = statusLabel(job.status);

                if (job.status === 'completed') {
                    if (dataTable) {
                        dataTable.row(row).remove().draw(false);
                    } else {
                        row.remove();
                    }
                    statusPollMap.delete(job.id);
                }

                if (job.status === 'failed') {
                    statusPollMap.delete(job.id);
                    if (job.error_message) {
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

    const queueDelete = async (postIds) => {
        const response = await fetch(`{{ route('admin.facebook.manage-posts.delete') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ post_ids: postIds }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to schedule deletions.');
        }

        (payload.jobs || []).forEach(job => {
            const row = document.querySelector(`tr[data-post-id="${job.synced_social_post_id}"]`);
            if (!row) return;

            row.querySelector('.deletion-status').innerHTML = statusLabel(job.status);
            const btn = row.querySelector('.single-delete-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Scheduled';
            }
        });

        startPollingStatuses((payload.jobs || []).map(job => job.id));
        showAlert(payload.message || 'Deletion scheduled.', 'success');
    };

    syncPostsBtn.addEventListener('click', async () => {
        const pageIds = getSelectedPageIds();
        if (!pageIds.length) {
            showAlert('Please select at least one page/account.', 'warning');
            return;
        }

        syncPostsBtn.disabled = true;
        try {
            const response = await fetch(`{{ route('admin.facebook.manage-posts.sync') }}`, {
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
                throw new Error(payload.message || 'Sync failed.');
            }

            if ((payload.errors || []).length) {
                showAlert(`${payload.message} Warnings: ${(payload.errors || []).join(' | ')}`, 'warning');
            } else {
                showAlert(payload.message, 'success');
            }

            await loadPosts();
        } catch (error) {
            showAlert(error.message || 'Unable to sync posts.', 'danger');
        } finally {
            syncPostsBtn.disabled = false;
        }
    });

    loadPostsBtn.addEventListener('click', async () => {
        loadPostsBtn.disabled = true;
        try {
            await loadPosts();
        } catch (error) {
            showAlert(error.message || 'Unable to load posts.', 'danger');
        } finally {
            loadPostsBtn.disabled = false;
        }
    });

    bulkDeleteBtn.addEventListener('click', async () => {
        const ids = selectedPostIds();
        if (!ids.length) return;
        if (!window.confirm(`Schedule deletion for ${ids.length} selected post(s)?`)) return;

        try {
            await queueDelete(ids);
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
