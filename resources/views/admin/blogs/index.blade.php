@extends('admin.layout')

@section('title','Blogs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0">Blogs</h1>
    <button class="btn btn-success" data-modal-url="{{ route('admin.blogs.createModal') }}">New Blog</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table class="table-striped" no-export="7">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Created At</th><th>Updated At</th><th>Read</th><th class="text-end no-export">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($blogs as $b)
                        <tr>
                            <td>{{ $b->id }}</td>
                            <td>{{ $b->title }}</td>
                            <td>{{ $b->category?->name }}</td>
                            <td>{{ $b->status ? 'Published' : 'Draft' }}</td>
                            <td>{{ optional($b->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($b->updated_at)->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('blog.show', $b->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a></td>
                            <td class="text-end no-export">
                                <button class="btn btn-sm btn-primary" data-modal-url="{{ route('admin.blogs.editModal', $b) }}">Edit</button>
                                <form action="{{ route('admin.blogs.destroy', $b) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" onclick="return confirm('Delete this blog?')">Delete</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No blogs found.</td></tr>
                    @endforelse
                </tbody>
        </x-data-table>

        <div class="mt-3">{{ $blogs->links() }}</div>
    </div>
</div>
@endsection
