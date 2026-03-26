@extends('admin.layout')

@section('title','Categories')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Categories</h1>
    <button class="btn btn-success" data-modal-url="{{ route('admin.categories.createModal') }}">New Category</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td>{{ $c->name }}</td>
                            <td>{{ $c->status ? 'Active' : 'Inactive' }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" data-modal-url="{{ route('admin.categories.editModal', $c) }}">Edit</button>
                                <form action="{{ route('admin.categories.destroy', $c) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->links() }}
    </div>
</div>
@endsection
