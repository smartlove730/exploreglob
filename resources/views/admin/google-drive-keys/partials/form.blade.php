@php
    $isEdit = isset($driveKey);
    $callbackUrl = route('admin.google-drive.callback');
@endphp

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $driveKey->name : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Drive API Key</label>
    <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $isEdit ? $driveKey->api_key : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $driveKey->email : '') }}" placeholder="owner@company.com">
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-control">{{ old('description', $isEdit ? $driveKey->description : '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Redirect URL (for Google OAuth app config)</label>
    <input type="url" name="redirect_url" class="form-control" value="{{ old('redirect_url', $isEdit ? $driveKey->redirect_url : $callbackUrl) }}">
    <small class="text-muted d-block mt-1">Suggested callback URL: <code>{{ $callbackUrl }}</code></small>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="driveKeyActive" @checked(old('is_active', $isEdit ? $driveKey->is_active : true))>
    <label class="form-check-label" for="driveKeyActive">Active</label>
</div>

<button class="btn btn-primary">{{ $isEdit ? 'Update Key' : 'Create Key' }}</button>
<a href="{{ route('admin.facebook.google-drive-keys.index') }}" class="btn btn-outline-secondary">Cancel</a>
