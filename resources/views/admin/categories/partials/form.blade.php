@php $isEdit = isset($category); @endphp
<div class="modal-header">
    <h5 class="modal-title">{{ $isEdit ? 'Edit Category' : 'Create Category' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="admin-category-form" method="POST" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="{{ $isEdit ? $category->name : '' }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input name="slug" class="form-control" value="{{ $isEdit ? $category->slug : '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ $isEdit ? $category->description : '' }}</textarea>
        </div>    
        <div class="mb-3">
            <label class="form-label">Image</label>
            <input name="image" class="form-control" type="file" />

             <img src="{{ asset('storage/' . $category->image) }}" class="form-control" type="file" width="100" height="100" />
        </div>

        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($countries as $co)
                    <option value="{{ $co->id }}" @if($isEdit && $category->country_id == $co->id) selected @endif>{{ $co->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="catStatus" @if($isEdit && $category->status) checked @endif>
            <label class="form-check-label" for="catStatus">Active</label>
        </div>

        <div class="mb-3">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
