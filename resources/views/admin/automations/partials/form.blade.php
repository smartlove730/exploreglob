@php($isEdit = isset($automation))

<div class="mb-3">
    <label class="form-label">Config Name (optional)</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $automation->name : '') }}">
</div>

<div class="mb-3">
    <label class="form-label">Prompt</label>
    <textarea name="prompt" rows="5" class="form-control" required>{{ old('prompt', $isEdit ? $automation->prompt : '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Google Drive Link</label>
    <input type="url" name="drive_link" class="form-control" value="{{ old('drive_link', $isEdit ? $automation->drive_link : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Google Drive Key</label>
    <select name="drive_api_key_id" class="form-select" required>
        <option value="">Select key</option>
        @foreach($driveApiKeys as $driveApiKey)
            <option value="{{ $driveApiKey->id }}" @selected((int) old('drive_api_key_id', $isEdit ? $automation->drive_api_key_id : 0) === $driveApiKey->id)>
                {{ $driveApiKey->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">App ID</label>
        <select name="app_id" class="form-select" required>
            <option value="">Select app</option>
            @foreach($apps as $app)
                <option value="{{ $app->id }}" @selected((int) old('app_id', $isEdit ? $automation->app_id : 0) === $app->id)>{{ $app->name }} ({{ $app->app_id }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Page ID</label>
        <select name="page_id" class="form-select" required>
            <option value="">Select page</option>
            @foreach($pages as $page)
                <option value="{{ $page->id }}" @selected((int) old('page_id', $isEdit ? $automation->page_id : 0) === $page->id)>{{ $page->page_name }} ({{ $page->page_id }})</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-4">
        <label class="form-label">Platforms</label>
        <select name="platforms" class="form-select" required>
            <option value="facebook" @selected(old('platforms', $isEdit ? $automation->platforms : 'both') === 'facebook')>Facebook</option>
            <option value="instagram" @selected(old('platforms', $isEdit ? $automation->platforms : 'both') === 'instagram')>Instagram</option>
            <option value="both" @selected(old('platforms', $isEdit ? $automation->platforms : 'both') === 'both')>Both</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Runs per day</label>
        <input type="number" min="1" max="24" name="runs_per_day" class="form-control" value="{{ old('runs_per_day', $isEdit ? $automation->runs_per_day : 1) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Post limit per day</label>
        <input type="number" min="1" max="100" name="post_limit_per_day" class="form-control" value="{{ old('post_limit_per_day', $isEdit ? $automation->post_limit_per_day : 1) }}" required>
    </div>
</div>

<div class="form-check my-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="automationActive" @checked(old('is_active', $isEdit ? $automation->is_active : true))>
    <label class="form-check-label" for="automationActive">Active</label>
</div>

<button class="btn btn-primary">{{ $isEdit ? 'Update Automation' : 'Create Automation' }}</button>
<a href="{{ route('admin.automations.index') }}" class="btn btn-outline-secondary">Cancel</a>
