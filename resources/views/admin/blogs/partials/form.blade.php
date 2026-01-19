@php
    $isEdit = isset($blog);
@endphp
<div class="modal-header">
    <h5 class="modal-title">{{ $isEdit ? 'Edit Blog' : 'Create Blog' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form id="admin-blog-form" method="POST" action="{{ $isEdit ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" value="{{ $isEdit ? $blog->title : '' }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input name="slug" class="form-control" value="{{ $isEdit ? $blog->slug : '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Excerpt</label>
            <textarea name="excerpt" class="form-control">{{ $isEdit ? $blog->excerpt : '' }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="6">{{ $isEdit ? $blog->content : '' }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @if($isEdit && $blog->category_id == $c->id) selected @endif>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id" class="form-select">
                <option value="">-- None --</option>
                @foreach($countries as $co)
                    <option value="{{ $co->id }}" @if($isEdit && $blog->country_id == $co->id) selected @endif>{{ $co->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Featured Image (URL)</label>
            <div class="input-group mb-2">
                <input name="featured_image" class="form-control" value="{{ $isEdit ? $blog->featured_image : '' }}">
                <input type="file" class="form-control" id="featured_file" data-upload-url="{{ route('admin.uploads') }}">
            </div>
            <small class="text-muted">You can paste an external URL or upload an image using the file input.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">SEO Title</label>
            <input name="seo_title" class="form-control" value="{{ $isEdit ? $blog->seo_title : '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">SEO Description</label>
            <textarea name="seo_description" class="form-control">{{ $isEdit ? $blog->seo_description : '' }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Published At</label>
            <input type="datetime-local" name="published_at" class="form-control" value="{{ $isEdit && $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '' }}">
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="statusCheck" @if($isEdit && $blog->status) checked @endif>
            <label class="form-check-label" for="statusCheck">Published</label>
        </div>

        <div class="mb-3">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
