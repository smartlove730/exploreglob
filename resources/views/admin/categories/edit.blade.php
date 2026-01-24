@extends('admin.layout')

@section('title','Edit Category')

@section('content')
  <h2>Edit Category</h2>
  <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" value="{{ $category->name }}" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4">{{ $category->description }}</textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Image</label>
      @if($category->image)
        <div class="mb-2">
          <img src="{{ asset('storage/' . $category->image) }}" alt="Current Image" style="max-width: 200px;">
        </div>
      @endif
      <input type="file" name="image" class="form-control" accept="image/*">
      <small class="form-text text-muted">Leave empty to keep current image. Supported formats: JPEG, PNG, JPG, GIF. Max size: 2MB.</small>
    </div>
    <div class="mb-3">
      <button class="btn btn-primary">Update</button>
    </div>
  </form>
@endsection
