@php
    $isEdit = isset($automation);
    $sourceType = old('media_source_type', $isEdit ? $automation->media_source_type : 'urls');
    $payload = $isEdit ? ($automation->media_source_payload ?? []) : [];
    $selectedPages = collect(old('page_ids', $isEdit ? $automation->page_ids : []))->map(fn ($id) => (int) $id)->all();
    $selectedPlatforms = collect(old('platforms', $isEdit ? $automation->platforms : ['facebook']))->all();
@endphp

<div class="row g-4">
    <div class="col-lg-3">
        <div class="list-group position-sticky" style="top: 1rem;">
            <a class="list-group-item list-group-item-action active" href="#stepBasics">1. Basics</a>
            <a class="list-group-item list-group-item-action" href="#stepMedia">2. Media</a>
            <a class="list-group-item list-group-item-action" href="#stepSchedule">3. Schedule</a>
            <a class="list-group-item list-group-item-action" href="#stepTemplates">4. Templates</a>
        </div>
    </div>
    <div class="col-lg-9">
        <section id="stepBasics" class="mb-4">
            <h2 class="h5">Basics</h2>
            <div class="mb-3">
                <label class="form-label">Automation Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $automation->name : '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Facebook App</label>
                <select name="app_id" class="form-select" required>
                    @foreach($apps as $app)
                        <option value="{{ $app->id }}" @selected((int) old('app_id', $isEdit ? $automation->app_id : 0) === $app->id)>{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Connected Pages / Accounts</label>
                <select name="page_ids[]" class="form-select" multiple size="6" required>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" @selected(in_array($page->id, $selectedPages, true))>{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Platforms</label>
                <div class="d-flex gap-3">
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="platforms[]" value="facebook" @checked(in_array('facebook', $selectedPlatforms, true))> Facebook</label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="platforms[]" value="instagram" @checked(in_array('instagram', $selectedPlatforms, true))> Instagram</label>
                </div>
            </div>
        </section>

        <section id="stepMedia" class="mb-4">
            <h2 class="h5">Media Source</h2>
            <div class="mb-3">
                <select name="media_source_type" class="form-select" id="mediaSourceType">
                    <option value="urls" @selected($sourceType === 'urls')>Public media URLs</option>
                    <option value="drive" @selected($sourceType === 'drive')>Google Drive folder/link</option>
                </select>
            </div>
            <div class="mb-3 media-source media-source-urls">
                <label class="form-label">Media URLs</label>
                <textarea name="media_urls" class="form-control" rows="6" placeholder="One image or video URL per line">{{ old('media_urls', implode("\n", $payload['urls'] ?? [])) }}</textarea>
            </div>
            <div class="media-source media-source-drive">
                <div class="mb-3">
                    <label class="form-label">Drive Account</label>
                    <select name="drive_api_key_id" class="form-select">
                        <option value="">Select Drive account</option>
                        @foreach($driveApiKeys as $key)
                            <option value="{{ $key->id }}" @selected((int) old('drive_api_key_id', $payload['drive_api_key_id'] ?? 0) === $key->id)>{{ $key->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Drive Link</label>
                    <input type="url" name="drive_link" class="form-control" value="{{ old('drive_link', $payload['drive_link'] ?? '') }}">
                </div>
            </div>
        </section>

        <section id="stepSchedule" class="mb-4">
            <h2 class="h5">Schedule</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Post Frequency</label>
                    <input type="number" min="1" max="3" name="post_frequency" class="form-control" value="{{ old('post_frequency', $isEdit ? $automation->post_frequency : 1) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Daily Limit Per Page</label>
                    <input type="number" min="1" max="3" name="daily_limit" class="form-control" value="{{ old('daily_limit', $isEdit ? $automation->daily_limit : 3) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Schedule Times</label>
                    @php($times = collect(old('schedule_times', $isEdit ? $automation->schedule_times : [now()->format('H:i')]))->values())
                    @for($i = 0; $i < 3; $i++)
                        <input type="time" name="schedule_times[]" class="form-control mb-2" value="{{ $times[$i] ?? '' }}" @if($i === 0) required @endif>
                    @endfor
                </div>
            </div>
            <p class="small text-muted mb-0">Automation is capped at 3 posts per day per page.</p>
        </section>

        <section id="stepTemplates" class="mb-4">
            <h2 class="h5">Captions & Hashtags</h2>
            <div class="mb-3">
                <label class="form-label">Caption Templates</label>
                <textarea name="caption_templates" rows="5" class="form-control" required>{{ old('caption_templates', implode("\n", $isEdit ? ($automation->caption_templates ?? []) : [])) }}</textarea>
                <small class="text-muted">One template per line. The queue rotates through them.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Hashtag Templates</label>
                <textarea name="hashtag_templates" rows="3" class="form-control">{{ old('hashtag_templates', implode("\n", $isEdit ? ($automation->hashtag_templates ?? []) : [])) }}</textarea>
            </div>
        </section>

        <button class="btn btn-primary">{{ $isEdit ? 'Update Automation' : 'Create & Queue' }}</button>
        <a href="{{ route('admin.automations.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sourceType = document.getElementById('mediaSourceType');
    const refresh = () => {
        document.querySelectorAll('.media-source').forEach(node => node.classList.add('d-none'));
        document.querySelectorAll(`.media-source-${sourceType.value}`).forEach(node => node.classList.remove('d-none'));
    };
    sourceType.addEventListener('change', refresh);
    refresh();
});
</script>
@endpush
