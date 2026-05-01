@extends('admin.layout')

@section('title', 'Facebook Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Facebook Settings</h1>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex gap-2">
            <a href="{{ route('admin.facebook.connect', ['app_id' => $selectedAppId]) }}" class="btn btn-primary">Connect Facebook</a>
        </div>

        @if($account)
            <form method="POST" action="{{ route('admin.facebook.sync-pages') }}" class="mt-2">
                @csrf
                <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                <button class="btn btn-outline-secondary">Sync Pages</button>
            </form>
        @endif
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
                <h2 class="h5 mb-3">Managed Facebook Pages</h2>

                @if($pages->isEmpty())
                    <p class="text-muted mb-0">No pages available yet. Select app, connect, and sync.</p>
                @else
                    <x-data-table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Page</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    <tr>
                                        <td>{{ $page->id }}</td>
                                        <td>{{ $page->page_name }}<br><small class="text-muted">ID: {{ $page->page_id }}</small></td>
                                        <td><span class="badge text-bg-success">Active</span></td>
                                        <td>{{ optional($page->created_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ optional($page->updated_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                    </x-data-table>
                    <p class="small text-muted mb-0">All synced pages are marked active and available for posting.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
