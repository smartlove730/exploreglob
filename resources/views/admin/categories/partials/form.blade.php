@php $isEdit = isset($category); @endphp
<div class="modal-header">
    <h5 class="modal-title">{{ $isEdit ? 'Edit Category' : 'Create Category' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form method="POST" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" value="{{ old('name', $isEdit ? $category->name : '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input name="slug" class="form-control" value="{{ old('slug', $isEdit ? $category->slug : '') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $isEdit ? $category->description : '') }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Image</label>
            <input name="image" class="form-control" type="file" />
            @if($isEdit && $category->image)
                <img src="{{ asset('storage/' . $category->image) }}" alt="Category image" class="img-thumbnail mt-2" width="120">
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($countries as $co)
                    <option value="{{ $co->id }}" @selected((string) old('country_id', $isEdit ? $category->country_id : '') === (string) $co->id)>{{ $co->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="catStatus" @checked(old('status', $isEdit ? $category->status : false))>
            <label class="form-check-label" for="catStatus">Active</label>
        </div>

        <button class="btn btn-primary">Save</button>
    </form>
</div>
