@extends('admin.layout')

@section('title', 'Facebook Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Facebook Settings</h1>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.facebook.settings') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Facebook App</label>
                <select name="app_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select an app</option>
                    @foreach($apps as $app)
                        <option value="{{ $app->id }}" {{ $selectedAppId === $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 d-flex gap-2">
                <a href="{{ route('admin.facebook.connect', ['app_id' => $selectedAppId]) }}" class="btn btn-primary {{ $selectedAppId ? '' : 'disabled' }}">Connect Facebook</a>
            </div>
        </form>

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
                    <p class="mb-1"><span class="badge text-bg-success">Connected</span></p>
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
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $page)
                                    <tr>
                                        <td>{{ $page->page_name }}<br><small class="text-muted">ID: {{ $page->page_id }}</small></td>
                                        <td>
                                            @if($page->is_active)
                                                <span class="badge text-bg-success">Active</span>
                                            @else
                                                <span class="badge text-bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.facebook.pages.activate', $page) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" {{ $page->is_active ? 'disabled' : '' }}>Set Active</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
