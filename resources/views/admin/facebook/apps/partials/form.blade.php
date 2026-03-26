@php
    $isEdit = isset($app);
@endphp

<div class="mb-3">
    <label class="form-label">App Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $app->name : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Facebook App ID</label>
    <input type="text" name="app_id" class="form-control" value="{{ old('app_id', $isEdit ? $app->app_id : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">App Secret</label>
    <input type="text" name="app_secret" class="form-control" value="{{ old('app_secret', $isEdit ? $app->app_secret : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Redirect URI</label>
    <input type="url" name="redirect_uri" class="form-control" value="{{ old('redirect_uri', $isEdit ? $app->redirect_uri : '') }}" required>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="fbAppActive" @checked(old('is_active', $isEdit ? $app->is_active : true))>
    <label class="form-check-label" for="fbAppActive">Active</label>
</div>

<button class="btn btn-primary">{{ $isEdit ? 'Update App' : 'Create App' }}</button>
<a href="{{ route('admin.facebook.apps.index') }}" class="btn btn-outline-secondary">Cancel</a>
