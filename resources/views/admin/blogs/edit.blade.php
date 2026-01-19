@extends('admin.layout')

@section('title','Edit Blog')

@section('content')
  <h2>Edit Blog</h2>
  <form method="POST" action="{{ route('admin.blogs.update', $blog) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" value="{{ $blog->title }}" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Content</label>
      <textarea name="content" class="form-control" rows="6">{{ $blog->content }}</textarea>
    </div>
    <div class="mb-3">
      <button class="btn btn-primary">Update</button>
    </div>
  </form>
@endsection
