@extends('admin.layout')

@section('title','Categories')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Categories</h2>
    <button class="btn btn-success" data-modal-url="{{ route('admin.categories.createModal') }}">New Category</button>
  </div>

  <table class="table table-striped">
    <thead>
      <tr><th>ID</th><th>Name</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      @foreach($categories as $c)
        <tr>
          <td>{{ $c->id }}</td>
          <td>{{ $c->name }}</td>
          <td>{{ $c->status ? 'Active' : 'Inactive' }}</td>
          <td>
            <button class="btn btn-sm btn-primary" data-modal-url="{{ route('admin.categories.editModal', $c) }}">Edit</button>
            <form action="{{ route('admin.categories.destroy', $c) }}" method="POST" style="display:inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Delete</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{ $categories->links() }}

@endsection
