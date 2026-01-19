@extends('admin.layout')

@section('title','Blogs')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Blogs</h2>
    <button class="btn btn-success" data-modal-url="{{ route('admin.blogs.createModal') }}">New Blog</button>
  </div>

  <table class="table table-striped">
    <thead>
      <tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      @foreach($blogs as $b)
        <tr>
          <td>{{ $b->id }}</td>
          <td>{{ $b->title }}</td>
          <td>{{ $b->category?->name }}</td>
          <td>{{ $b->status ? 'Published' : 'Draft' }}</td>
          <td>
            <button class="btn btn-sm btn-primary" data-modal-url="{{ route('admin.blogs.editModal', $b) }}">Edit</button>
            <form action="{{ route('admin.blogs.destroy', $b) }}" method="POST" style="display:inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{ $blogs->links() }}

@endsection
