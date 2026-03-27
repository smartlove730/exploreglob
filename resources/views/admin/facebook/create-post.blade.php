@extends('admin.layout')

@section('title', 'Create Social Post')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Create Social Post</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.posts.index') }}">View History</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.posts.create') }}" class="mb-3 row g-3">
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
            <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
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
                    <label class="form-label d-block">Platforms</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="platforms[]" value="facebook" checked>
                        <label class="form-check-label">Facebook</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="platforms[]" value="instagram">
                        <label class="form-check-label">Instagram</label>
                    </div>
                    <small class="text-muted d-block">Instagram requires at least one image (uploaded or URL).</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prompt for AI Caption</label>
                    <div class="input-group">
                        <input type="text" name="prompt" id="ai_prompt" class="form-control" value="{{ old('prompt') }}" placeholder="e.g. New hiking guide in California for weekend travelers">
                        <button type="button" class="btn btn-outline-primary" id="generate_caption_btn" data-generate-url="{{ route('admin.facebook.posts.generate-caption') }}">Generate Caption</button>
                    </div>
                    <small id="generate_caption_status" class="text-muted d-block mt-1"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message / Caption</label>
                    <textarea name="message" id="message" rows="6" class="form-control" required>{{ old('message') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (optional)</label>
                    <input type="url" name="image_url" class="form-control" value="{{ old('image_url') }}" placeholder="https://example.com/image.jpg">
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Images</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                    <small class="text-muted">You can upload multiple images.</small>
                </div>
                <button class="btn btn-primary">Publish Post</button>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const promptInput = document.getElementById('ai_prompt');
    const messageInput = document.getElementById('message');
    const generateBtn = document.getElementById('generate_caption_btn');
    const statusNode = document.getElementById('generate_caption_status');

    if (!promptInput || !messageInput || !generateBtn || !statusNode) return;

    generateBtn.addEventListener('click', async () => {
        const prompt = promptInput.value.trim();
        if (!prompt) {
            statusNode.textContent = 'Please enter a prompt first.';
            statusNode.className = 'text-danger d-block mt-1';
            return;
        }

        statusNode.textContent = 'Generating caption...';
        statusNode.className = 'text-muted d-block mt-1';
        generateBtn.disabled = true;

        try {
            const response = await fetch(generateBtn.dataset.generateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ prompt }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Failed to generate caption.');

            messageInput.value = result.data.caption || '';
            statusNode.textContent = 'Caption generated successfully.';
            statusNode.className = 'text-success d-block mt-1';
        } catch (error) {
            statusNode.textContent = error.message || 'Unexpected error while generating caption.';
            statusNode.className = 'text-danger d-block mt-1';
        } finally {
            generateBtn.disabled = false;
        }
    });
});
</script>
@endpush
