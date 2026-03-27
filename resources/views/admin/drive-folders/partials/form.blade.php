@php($isEdit = isset($driveFolder))

<div class="mb-3">
    <label class="form-label">Folder Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $driveFolder->name : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Google Drive Folder Link</label>
    <input type="url" name="folder_url" class="form-control" value="{{ old('folder_url', $isEdit ? $driveFolder->folder_url : '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Default Drive API Key (optional)</label>
    <select class="form-select" name="drive_api_key_id">
        <option value="">Select key</option>
        @foreach($driveApiKeys as $driveApiKey)
            <option value="{{ $driveApiKey->id }}" {{ (int) old('drive_api_key_id', $isEdit ? $driveFolder->drive_api_key_id : 0) === $driveApiKey->id ? 'selected' : '' }}>{{ $driveApiKey->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-control">{{ old('description', $isEdit ? $driveFolder->description : '') }}</textarea>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="driveFolderActive" @checked(old('is_active', $isEdit ? $driveFolder->is_active : true))>
    <label class="form-check-label" for="driveFolderActive">Active</label>
</div>

<button class="btn btn-primary">{{ $isEdit ? 'Update Folder' : 'Create Folder' }}</button>
<a href="{{ route('admin.facebook.drive-folders.index') }}" class="btn btn-outline-secondary">Cancel</a>
