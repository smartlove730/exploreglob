<div class="row g-3">
    @if(($mode ?? 'create') === 'edit')
        <div class="col-12">
            <span class="me-2">Status:</span><span id="editStatusBadge" class="badge status-badge text-bg-secondary">pending</span>
            <div class="small text-muted mt-2">Last error: <span id="editLastError">None</span></div>
        </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">Page</label>
        <select class="form-select" name="page_id" required>
            <option value="">Select page</option>
            @foreach($pages as $page)
                <option value="{{ $page->id }}">{{ $page->page_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Schedule Time</label>
        <input type="datetime-local" class="form-control" name="scheduled_for" required>
    </div>

    <div class="col-12">
        <label class="form-label">Message</label>
        <textarea class="form-control" name="message" rows="4" required></textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">Media Type</label>
        <select class="form-select" name="media_type">
            <option value="image">Image</option>
            <option value="video">Video</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Image URL</label>
        <input type="url" class="form-control" name="image_url" placeholder="https://...">
    </div>

    <div class="col-md-4">
        <label class="form-label">Video URL</label>
        <input type="url" class="form-control" name="video_url" placeholder="https://...">
    </div>

    <div class="col-12">
        <label class="form-label">Or pick from Media Library</label>
        <select class="form-select" name="media_id">
            <option value="">No media selected</option>
            @foreach(($mediaItems ?? []) as $item)
                <option value="{{ $item->id }}" data-type="{{ $item->type }}" data-url="{{ url(\Illuminate\Support\Facades\Storage::disk('public')->url($item->path)) }}">
                    [{{ strtoupper($item->type) }}] {{ $item->original_name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Tip: selecting media auto-fills image/video URL in the calendar form.</div>
    </div>

    <div class="col-12">
        <label class="form-label d-block">Platforms</label>
        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="platforms[]" value="facebook" checked><label class="form-check-label">Facebook</label></div>
        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="platforms[]" value="instagram"><label class="form-check-label">Instagram</label></div>
    </div>
</div>
