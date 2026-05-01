<div class="modal-header">
    <h5 class="modal-title">Facebook Page Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <dl class="row mb-0">
        <dt class="col-sm-4">Page ID</dt>
        <dd class="col-sm-8 font-monospace">{{ $page->page_id }}</dd>

        <dt class="col-sm-4">Page Name</dt>
        <dd class="col-sm-8">{{ $page->page_name }}</dd>

        <dt class="col-sm-4">Category</dt>
        <dd class="col-sm-8">{{ $page->category ?: 'Uncategorized' }}</dd>

        <dt class="col-sm-4">Access Status</dt>
        <dd class="col-sm-8">
            <span class="badge text-bg-{{ $page->is_active ? 'success' : 'secondary' }}">
                {{ $page->is_active ? 'Active' : 'Inactive' }}
            </span>
        </dd>

        <dt class="col-sm-4">Connected Date</dt>
        <dd class="col-sm-8">{{ optional($page->created_at)->format('M d, Y H:i') ?? 'Unknown' }}</dd>

        <dt class="col-sm-4">Last Sync</dt>
        <dd class="col-sm-8">{{ optional($page->last_synced_at ?? $page->updated_at)->format('M d, Y H:i') ?? 'Never' }}</dd>

        <dt class="col-sm-4">Facebook App</dt>
        <dd class="col-sm-8">{{ $page->facebookAccount?->app?->name ?? 'Default App' }}</dd>

        <dt class="col-sm-4">Token Refreshed</dt>
        <dd class="col-sm-8">{{ optional($page->facebookAccount?->token_last_refreshed_at)->format('M d, Y H:i') ?? 'Unknown' }}</dd>

        <dt class="col-sm-4">Token Expires</dt>
        <dd class="col-sm-8">{{ optional($page->facebookAccount?->token_expires_at)->format('M d, Y H:i') ?? 'Unknown' }}</dd>

        <dt class="col-sm-4">Instagram Business ID</dt>
        <dd class="col-sm-8 font-monospace">{{ $page->instagram_business_account_id ?: 'Not linked' }}</dd>
    </dl>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
