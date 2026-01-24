@extends('admin.layout')

@section('title','Create Category')

@section('content')
  <h2>Create Category</h2>
  <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4"></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Image</label>
      <input type="file" name="image" class="form-control" accept="image/*">
      <small class="form-text text-muted">Supported formats: JPEG, PNG, JPG, GIF. Max size: 2MB.</small>
    </div>
    <div class="mb-3">
      <button class="btn btn-primary">Save</button>
    </div>
  </form>
@endsection
