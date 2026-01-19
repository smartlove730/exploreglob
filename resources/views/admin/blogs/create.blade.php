@extends('admin.layout')

@section('title','Create Blog')

@section('content')
  <h2>Create Blog</h2>
  <form method="POST" action="{{ route('admin.blogs.store') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Content</label>
      <textarea name="content" class="form-control" rows="6"></textarea>
    </div>
    <div class="mb-3">
      <button class="btn btn-primary">Save</button>
    </div>
  </form>
@endsection
