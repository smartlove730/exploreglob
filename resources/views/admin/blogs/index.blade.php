@extends('admin.layout')

@section('title','Blogs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Blogs</h1>
    <button class="btn btn-success" data-modal-url="{{ route('admin.blogs.createModal') }}">New Blog</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($blogs as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->title }}</td>
                            <td>{{ $b->category?->name }}</td>
                            <td>{{ $b->status ? 'Published' : 'Draft' }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" data-modal-url="{{ route('admin.blogs.editModal', $b) }}">Edit</button>
                                <form action="{{ route('admin.blogs.destroy', $b) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this blog?')">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No blogs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $blogs->links() }}
    </div>
</div>
@endsection
