@extends('admin.layout')

@section('title', 'Create Facebook Post')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Create Facebook Post</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.facebook.posts') }}">View History</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.facebook.posts.create') }}" class="mb-3 row g-3">
            <div class="col-md-6">
                <label class="form-label">Facebook App</label>
                <select name="app_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select an app</option>
                    @foreach($apps as $app)
                        <option value="{{ $app->id }}" {{ $selectedAppId === $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Facebook Page (Active)</label>
                <select name="page_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select a page</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" {{ $selectedPageId === $page->id ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($pages->isEmpty())
            <div class="alert alert-warning mb-0">No active pages found for selected app. Connect/sync pages in Facebook Settings.</div>
        @else
            <form method="POST" action="{{ route('admin.facebook.posts.store') }}">
                @csrf
                <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                <div class="mb-3">
                    <label class="form-label">Page</label>
                    <select name="page_id" class="form-select" required>
                        <option value="">Select page</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}" {{ (int) old('page_id', $selectedPageId) === $page->id ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="6" class="form-control" required>{{ old('message') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (optional)</label>
                    <input type="url" name="image_url" class="form-control" value="{{ old('image_url') }}" placeholder="https://example.com/image.jpg">
                </div>
                <button class="btn btn-primary">Save & Queue Post</button>
            </form>
        @endif
    </div>
</div>
@endsection
