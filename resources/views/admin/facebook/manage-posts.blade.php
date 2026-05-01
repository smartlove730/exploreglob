@extends('admin.layout')

@section('title', 'Manage Posts')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Manage Posts</h1>
        <p class="text-muted mb-0">Load Facebook and Instagram posts, review sync results, and manage deletion queues.</p>
    </div>
    <button class="btn btn-primary btn-lg" id="loadPostsBtn">Load Posts</button>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label class="form-label">Pages</label>
                <select class="form-select" id="pageSelector" multiple size="5">
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Leave empty to load every active page.</small>
            </div>
            <div class="col-lg-8">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Platform</label>
                        <select id="platformFilter" class="form-select form-select-sm">
                            <option value="">All platforms</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="ready">Ready</option>
                            <option value="pending">Queued</option>
                            <option value="processing">Deleting</option>
                            <option value="failed">Failed</option>
                            <option value="completed">Deleted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Created From</label>
                        <input type="date" id="createdFromFilter" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Created To</label>
                        <input type="date" id="createdToFilter" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Search posts</label>
                        <input type="search" id="searchFilter" class="form-control" placeholder="Search content, page name, or post ID">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="loadProgress" class="card border-0 shadow-sm mb-3 d-none">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="fw-semibold" id="progressTitle">Loading posts...</div>
                <div class="small text-muted" id="progressMessage">Fetching posts from connected pages.</div>
            </div>
            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
        </div>
        <div class="progress" role="progressbar" aria-label="Loading posts">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3" id="summaryCards">
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">Shown</div><div class="h4 mb-0" data-stat="shown">0</div></div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">Loaded</div><div class="h4 mb-0" data-stat="loaded">0</div></div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">New</div><div class="h4 mb-0" data-stat="created">0</div></div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">Updated</div><div class="h4 mb-0" data-stat="updated">0</div></div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">Skipped</div><div class="h4 mb-0" data-stat="skipped">0</div></div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3"><div class="text-muted small">Failed</div><div class="h4 mb-0 text-danger" data-stat="failed">0</div></div></div>
    </div>
</div>

<div id="alerts" class="mb-3"></div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Loaded Posts</h2>
                <p class="text-muted mb-0 small" id="tableHint">Click Load Posts to fetch the latest posts. Results are sorted newest first.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-warning btn-sm" id="retryFailedBtn" disabled>Retry Failed</button>
                <button class="btn btn-outline-danger btn-sm" id="bulkDeleteBtn" disabled>Delete Selected</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="postsTable">
                <thead>
                    <tr>
                        <th class="no-export"><input type="checkbox" id="selectAllPosts" aria-label="Select all posts"></th>
                        <th>Post</th>
                        <th>Platform</th>
                        <th>Page</th>
                        <th>Created</th>
                        <th>Last Sync</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody id="postsTableBody">
                    <tr><td colspan="8" class="text-center text-muted py-4">No posts loaded yet.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="mediaPreviewBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const pageSelector = document.getElementById('pageSelector');
    const loadPostsBtn = document.getElementById('loadPostsBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const retryFailedBtn = document.getElementById('retryFailedBtn');
    const tableBody = document.getElementById('postsTableBody');
    const alerts = document.getElementById('alerts');
    const selectAllPosts = document.getElementById('selectAllPosts');
    const loadProgress = document.getElementById('loadProgress');
    const progressTitle = document.getElementById('progressTitle');
    const progressMessage = document.getElementById('progressMessage');
    const tableHint = document.getElementById('tableHint');
    const statusPollMap = new Map();
    let dataTable = null;
    let latestPosts = [];
    let lastStats = { loaded: 0, created: 0, updated: 0, skipped: 0, failed: 0 };

    const filters = {
        platform: document.getElementById('platformFilter'),
        status: document.getElementById('statusFilter'),
        createdFrom: document.getElementById('createdFromFilter'),
        createdTo: document.getElementById('createdToFilter'),
        search: document.getElementById('searchFilter'),
    };

    const getSelectedPageIds = () => [...pageSelector.selectedOptions].map(option => Number(option.value)).filter(Boolean);
    const selectedPostIds = () => [...document.querySelectorAll('.post-checkbox:checked')].map(checkbox => Number(checkbox.value)).filter(Boolean);

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));

    const formatDate = (value) => {
        if (!value) return '-';
        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return escapeHtml(value);

        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const showAlert = (message, type = 'info') => {
        alerts.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
    };

    const setProgress = (visible, title = 'Loading posts...', message = 'Fetching posts from connected pages.') => {
        loadProgress.classList.toggle('d-none', !visible);
        progressTitle.textContent = title;
        progressMessage.textContent = message;
        loadPostsBtn.disabled = visible;
        loadPostsBtn.textContent = visible ? 'Loading...' : 'Load Posts';
    };

    const updateStats = (stats = lastStats, shown = latestPosts.length) => {
        lastStats = { loaded: 0, created: 0, updated: 0, skipped: 0, failed: 0, ...stats };
        document.querySelector('[data-stat="shown"]').textContent = shown;
        Object.entries(lastStats).forEach(([key, value]) => {
            const node = document.querySelector(`[data-stat="${key}"]`);
            if (node) node.textContent = value;
        });
    };

    const statusBadge = (status) => {
        const map = {
            ready: ['Ready', 'success'],
            pending: ['Queued', 'secondary'],
            processing: ['Deleting', 'warning'],
            completed: ['Deleted', 'dark'],
            failed: ['Failed', 'danger'],
        };
        const [label, variant] = map[status] || ['Ready', 'success'];

        return `<span class="badge text-bg-${variant}">${label}</span>`;
    };

    const platformBadge = (platform) => {
        const variant = platform === 'instagram' ? 'danger' : 'primary';
        return `<span class="badge text-bg-${variant} text-capitalize">${escapeHtml(platform)}</span>`;
    };

    const refreshBulkState = () => {
        const selected = selectedPostIds();
        const selectedPosts = latestPosts.filter(post => selected.includes(Number(post.id)));
        const failedSelected = selectedPosts.filter(post => post.deletion_status === 'failed').length;
        const hasFailedAnywhere = latestPosts.some(post => post.deletion_status === 'failed');

        bulkDeleteBtn.disabled = selected.length === 0;
        bulkDeleteBtn.textContent = selected.length ? `Delete Selected (${selected.length})` : 'Delete Selected';
        retryFailedBtn.disabled = selected.length ? failedSelected === 0 : !hasFailedAnywhere;
        retryFailedBtn.textContent = selected.length && failedSelected
            ? `Retry Failed (${failedSelected})`
            : (hasFailedAnywhere ? 'Retry Failed' : 'Retry Failed');
    };

    const destroyDataTable = () => {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const initializeDataTable = () => {
        if (!window.jQuery?.fn?.DataTable || !latestPosts.length) return;
        destroyDataTable();

        dataTable = window.jQuery('#postsTable').DataTable({
            order: [[4, 'desc']],
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
                { orderable: false, searchable: false, targets: [0, 7], className: 'no-export' },
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search loaded table...',
            },
        });
    };

    const renderPosts = (posts) => {
        latestPosts = posts || [];
        destroyDataTable();
        selectAllPosts.checked = false;

        if (!latestPosts.length) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No posts match the current filters.</td></tr>';
            tableHint.textContent = 'No posts found. Adjust filters or load posts again.';
            updateStats(lastStats, 0);
            refreshBulkState();
            return;
        }

        tableBody.innerHTML = latestPosts.map((post) => {
            const content = post.content ? escapeHtml(post.content).slice(0, 180) : '<span class="text-muted">No caption text</span>';
            const preview = post.media_preview_url
                ? `<button class="btn btn-sm btn-outline-secondary preview-media-btn" data-src="${escapeHtml(post.media_preview_url)}">Preview Media</button>`
                : '<span class="text-muted small">No media</span>';
            const openPost = post.permalink
                ? `<a class="btn btn-sm btn-outline-primary" href="${escapeHtml(post.permalink)}" target="_blank" rel="noopener">Open</a>`
                : '';
            const error = post.deletion_error ? `<div class="small text-danger mt-1">${escapeHtml(post.deletion_error)}</div>` : '';

            return `
                <tr data-post-id="${post.id}">
                    <td class="no-export"><input type="checkbox" class="post-checkbox" value="${post.id}" aria-label="Select post ${post.id}"></td>
                    <td>
                        <div class="fw-semibold small text-muted">${escapeHtml(post.external_post_id)}</div>
                        <div>${content}</div>
                        <div class="mt-2">${preview}</div>
                    </td>
                    <td>${platformBadge(post.platform)}</td>
                    <td>${escapeHtml(post.page_name || '-')}</td>
                    <td data-order="${post.created_time || ''}">${formatDate(post.created_time)}</td>
                    <td data-order="${post.last_synced_at || ''}">${formatDate(post.last_synced_at)}</td>
                    <td class="deletion-status">${statusBadge(post.deletion_status)}${error}</td>
                    <td class="text-nowrap">
                        <div class="btn-group btn-group-sm">
                            ${openPost}
                            <button class="btn btn-outline-danger single-delete-btn" data-post-id="${post.id}" ${['pending', 'processing', 'completed'].includes(post.deletion_status) ? 'disabled' : ''}>
                                ${post.deletion_status === 'failed' ? 'Retry Delete' : 'Delete'}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tableBody.querySelectorAll('.post-checkbox').forEach(checkbox => checkbox.addEventListener('change', refreshBulkState));
        tableBody.querySelectorAll('.single-delete-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const postId = Number(button.dataset.postId || 0);
                if (!postId || !window.confirm('Queue this post for deletion?')) return;
                await queueDelete([postId]);
            });
        });
        tableBody.querySelectorAll('.preview-media-btn').forEach(button => {
            button.addEventListener('click', () => {
                const src = button.dataset.src;
                const body = document.getElementById('mediaPreviewBody');
                body.innerHTML = `<img src="${escapeHtml(src)}" alt="Media preview" class="img-fluid rounded">`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaPreviewModal')).show();
            });
        });

        tableHint.textContent = `${latestPosts.length} post(s) shown. Results are sorted newest first.`;
        updateStats(lastStats, latestPosts.length);
        initializeDataTable();
        refreshBulkState();
    };

    const listPosts = async () => {
        const response = await fetch(`{{ route('admin.facebook.manage-posts.list') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                page_ids: getSelectedPageIds(),
                platform: filters.platform.value || null,
                deletion_status: filters.status.value || null,
                search: filters.search.value || null,
                created_from: filters.createdFrom.value || null,
                created_to: filters.createdTo.value || null,
            }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load posts.');
        }

        renderPosts(payload.posts || []);
        return payload.posts || [];
    };

    const loadPosts = async () => {
        setProgress(true, 'Loading posts...', 'Fetching posts from Meta and skipping duplicates.');

        const syncResponse = await fetch(`{{ route('admin.facebook.manage-posts.sync') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                page_ids: getSelectedPageIds(),
                platform: filters.platform.value || null,
            }),
        });

        const syncPayload = await syncResponse.json();
        if (!syncResponse.ok || !syncPayload.success) {
            throw new Error(syncPayload.message || 'Unable to load posts from Meta.');
        }

        lastStats = syncPayload.stats || lastStats;
        progressMessage.textContent = 'Rendering loaded posts and applying filters.';
        const posts = await listPosts();
        updateStats(lastStats, posts.length);

        const warning = (syncPayload.errors || []).length
            ? `<div class="mt-2 small">${(syncPayload.errors || []).map(escapeHtml).join('<br>')}</div>`
            : '';
        showAlert(`${escapeHtml(syncPayload.message)}${warning}`, (syncPayload.errors || []).length ? 'warning' : 'success');
    };

    const startPollingStatuses = (jobIds = []) => {
        jobIds.filter(Boolean).forEach(id => statusPollMap.set(id, true));
        if (!statusPollMap.size) return;

        const tick = async () => {
            const activeIds = [...statusPollMap.keys()];
            if (!activeIds.length) return;

            const response = await fetch(`{{ route('admin.facebook.manage-posts.statuses') }}?ids=${activeIds.join(',')}`);
            const payload = await response.json();

            (payload.jobs || []).forEach(job => {
                const index = latestPosts.findIndex(post => Number(post.id) === Number(job.synced_social_post_id));
                if (index >= 0) {
                    latestPosts[index].deletion_status = job.status;
                    latestPosts[index].deletion_error = job.error_message;
                }

                if (['completed', 'failed'].includes(job.status)) {
                    statusPollMap.delete(job.id);
                }
            });

            renderPosts(latestPosts);

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
            throw new Error(payload.message || 'Unable to queue deletion.');
        }

        (payload.jobs || []).forEach(job => {
            const index = latestPosts.findIndex(post => Number(post.id) === Number(job.synced_social_post_id));
            if (index >= 0) latestPosts[index].deletion_status = job.status;
        });

        renderPosts(latestPosts);
        startPollingStatuses((payload.jobs || []).map(job => job.id));
        showAlert(escapeHtml(payload.message || 'Deletion jobs queued.'), 'success');
    };

    const retryFailed = async () => {
        const selected = selectedPostIds();
        const failedSelected = latestPosts
            .filter(post => selected.includes(Number(post.id)) && post.deletion_status === 'failed')
            .map(post => Number(post.id));
        const postIds = failedSelected.length ? failedSelected : [];

        const response = await fetch(`{{ route('admin.facebook.manage-posts.retry-failed') }}`, {
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
            throw new Error(payload.message || 'Unable to retry failed deletions.');
        }

        (payload.jobs || []).forEach(job => {
            const index = latestPosts.findIndex(post => Number(post.id) === Number(job.synced_social_post_id));
            if (index >= 0) {
                latestPosts[index].deletion_status = job.status;
                latestPosts[index].deletion_error = null;
            }
        });

        renderPosts(latestPosts);
        startPollingStatuses((payload.jobs || []).map(job => job.id));
        showAlert(escapeHtml(payload.message || 'Retry jobs queued.'), 'success');
    };

    loadPostsBtn.addEventListener('click', async () => {
        try {
            await loadPosts();
        } catch (error) {
            showAlert(escapeHtml(error.message || 'Unable to load posts.'), 'danger');
        } finally {
            setProgress(false);
        }
    });

    bulkDeleteBtn.addEventListener('click', async () => {
        const ids = selectedPostIds();
        if (!ids.length || !window.confirm(`Queue ${ids.length} selected post(s) for deletion?`)) return;

        try {
            await queueDelete(ids);
        } catch (error) {
            showAlert(escapeHtml(error.message || 'Unable to queue selected posts.'), 'danger');
        }
    });

    retryFailedBtn.addEventListener('click', async () => {
        if (!window.confirm('Retry failed deletion jobs?')) return;

        try {
            await retryFailed();
        } catch (error) {
            showAlert(escapeHtml(error.message || 'Unable to retry failed jobs.'), 'danger');
        }
    });

    selectAllPosts.addEventListener('change', () => {
        document.querySelectorAll('.post-checkbox').forEach(checkbox => {
            checkbox.checked = selectAllPosts.checked;
        });
        refreshBulkState();
    });

    Object.values(filters).forEach(input => {
        input.addEventListener('change', async () => {
            if (!latestPosts.length) return;
            try {
                await listPosts();
            } catch (error) {
                showAlert(escapeHtml(error.message || 'Unable to apply filters.'), 'danger');
            }
        });
    });

    filters.search.addEventListener('input', () => {
        if (dataTable) {
            dataTable.search(filters.search.value).draw();
        }
    });
});
</script>
@endpush
