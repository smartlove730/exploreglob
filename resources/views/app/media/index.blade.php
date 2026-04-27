<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">My Media Library</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('app.calendar.index') }}" class="btn btn-outline-secondary">Open Calendar</a>
            <a href="{{ route('app.dashboard') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('app.media.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-9">
                    <label class="form-label">Upload images/videos</label>
                    <input class="form-control" type="file" name="files[]" accept="image/*,video/mp4,video/quicktime" multiple required>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($mediaItems as $item)
            <div class="col-md-4 col-lg-3">
                <div class="card h-100">
                    @if($item->type === 'image')
                        <img src="{{ $item->public_url }}" class="card-img-top" alt="{{ $item->original_name }}" style="height:180px;object-fit:cover;">
                    @else
                        <video class="card-img-top" style="height:180px;object-fit:cover;" controls>
                            <source src="{{ $item->public_url }}" type="{{ $item->mime_type }}">
                        </video>
                    @endif
                    <div class="card-body d-flex flex-column">
                        <div class="fw-semibold small mb-2">{{ \Illuminate\Support\Str::limit($item->original_name, 35) }}</div>
                        <div class="text-muted small mb-2">{{ strtoupper($item->type) }} · {{ number_format($item->size / 1024, 1) }} KB</div>
                        <input class="form-control form-control-sm mb-2" value="{{ $item->public_url }}" readonly>
                        <form method="POST" action="{{ route('app.media.destroy', $item->id) }}" class="mt-auto" onsubmit="return confirm('Delete this media file?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm w-100">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">No media uploaded yet.</div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $mediaItems->links() }}</div>
</div>
</body>
</html>
