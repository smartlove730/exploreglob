@extends('admin.layout')

@section('title', 'Facebook Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Facebook Settings</h1>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.facebook.connect', ['app_id' => $selectedAppId]) }}" class="btn btn-primary">Connect Facebook</a>

            @if($account)
            <form method="POST" action="{{ route('admin.facebook.refresh-token') }}">
                @csrf
                <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                <button class="btn btn-outline-primary">Refresh Token</button>
            </form>
            <form method="POST" action="{{ route('admin.facebook.sync-pages') }}">
                @csrf
                <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                <button class="btn btn-outline-secondary">Resync Pages</button>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Connection Status</h2>
                @if($account)
                    @if($account->reauthorization_required)
                        <p class="mb-1"><span class="badge text-bg-warning">Reconnect Required</span></p>
                        <p class="mb-1 text-muted">{{ $account->reauthorization_reason ?: 'Your Facebook token could not be refreshed. Reconnect to continue posting.' }}</p>
                        <a href="{{ route('admin.facebook.connect', ['app_id' => $selectedAppId]) }}" class="btn btn-sm btn-primary">Reconnect Facebook</a>
                    @else
                        <p class="mb-1"><span class="badge text-bg-success">Connected</span></p>
                    @endif
                    <p class="mb-0 text-muted">Token expires: {{ optional($account->token_expires_at)->format('M d, Y H:i') ?? 'Unknown' }}</p>
                @else
                    <p class="mb-0"><span class="badge text-bg-danger">Not Connected</span></p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Synced Facebook Pages</h2>
                        <p class="text-muted mb-0 small">Review access, token health, sync timing, and page management actions.</p>
                    </div>
                    @if($account)
                        <form method="POST" action="{{ route('admin.facebook.sync-pages') }}">
                            @csrf
                            <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                            <button class="btn btn-sm btn-outline-secondary">Resync Pages</button>
                        </form>
                    @endif
                </div>

                @if($pages->isEmpty())
                    <p class="text-muted mb-0">No pages available yet. Select app, connect, and sync.</p>
                @else
                    <x-data-table id="facebook-pages-table" order='[[6, "desc"]]' no-export="7">
                            <thead>
                                <tr>
                                    <th>Page ID</th>
                                    <th>Page Name</th>
                                    <th>Category</th>
                                    <th>Access Status</th>
                                    <th>Token Status</th>
                                    <th>Connected Date</th>
                                    <th>Last Sync</th>
                                    <th class="no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    @php
                                        $tokenExpiresAt = $page->facebookAccount?->token_expires_at;
                                        $tokenIsExpired = $tokenExpiresAt && $tokenExpiresAt->isPast();
                                        $tokenExpiresSoon = $tokenExpiresAt && $tokenExpiresAt->isFuture() && $tokenExpiresAt->lte(now()->addDays(7));
                                        $tokenStatus = $page->page_access_token
                                            ? ($page->facebookAccount?->reauthorization_required || $tokenIsExpired
                                                ? 'Reconnect Required'
                                                : ($tokenExpiresSoon ? 'Expiring Soon' : 'Valid'))
                                            : 'Missing';
                                        $tokenBadge = match ($tokenStatus) {
                                            'Valid' => 'success',
                                            'Expiring Soon' => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="font-monospace small">{{ $page->page_id }}</span></td>
                                        <td>
                                            <div class="fw-semibold">{{ $page->page_name }}</div>
                                            @if($page->instagram_business_account_id)
                                                <small class="text-muted">Instagram: {{ $page->instagram_business_account_id }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $page->category ?: 'Uncategorized' }}</td>
                                        <td>
                                            <span class="badge text-bg-{{ $page->is_active ? 'success' : 'secondary' }}">
                                                {{ $page->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td><span class="badge text-bg-{{ $tokenBadge }}">{{ $tokenStatus }}</span></td>
                                        <td data-order="{{ optional($page->created_at)->timestamp }}">
                                            {{ optional($page->created_at)->format('M d, Y H:i') ?? 'Unknown' }}
                                        </td>
                                        <td data-order="{{ optional($page->last_synced_at ?? $page->updated_at)->timestamp }}">
                                            {{ optional($page->last_synced_at ?? $page->updated_at)->format('M d, Y H:i') ?? 'Never' }}
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Page actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary"
                                                    data-modal-url="{{ route('admin.facebook.pages.details', $page) }}"
                                                >
                                                    View Details
                                                </button>
                                                <form method="POST" action="{{ route('admin.facebook.pages.destroy', $page) }}" onsubmit="return confirm('Remove this synced Facebook page?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger rounded-start-0">Remove Page</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                    </x-data-table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
