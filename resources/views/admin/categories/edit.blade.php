@extends('admin.layout')

@section('title','Edit Category')

@section('content')
  <h2>Edit Category</h2>
  <form method="POST" action="{{ route('admin.categories.update', $category) }}">
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
      <button class="btn btn-primary">Update</button>
    </div>
  </form>
@endsection
