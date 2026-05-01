<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Media Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>URL</th>
                            <th class="no-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($mediaItems as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>
                                @if($item->type === 'image')
                                    <img src="{{ $item->public_url }}" alt="{{ $item->original_name }}" style="width:72px;height:54px;object-fit:cover;border-radius:6px;">
                                @else
                                    <video style="width:72px;height:54px;object-fit:cover;border-radius:6px;" controls>
                                        <source src="{{ $item->public_url }}" type="{{ $item->mime_type }}">
                                    </video>
                                @endif
                            </td>
                            <td>{{ $item->original_name }}</td>
                            <td>{{ strtoupper($item->type) }}</td>
                            <td>{{ number_format($item->size / 1024, 1) }} KB</td>
                            <td><span class="badge text-bg-success">Available</span></td>
                            <td>{{ optional($item->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($item->updated_at)->format('Y-m-d H:i') }}</td>
                            <td><input class="form-control form-control-sm" value="{{ $item->public_url }}" readonly></td>
                            <td class="no-export">
                                <form method="POST" action="{{ route('app.media.destroy', $item->id) }}" onsubmit="return confirm('Delete this media file?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No media uploaded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $mediaItems->links() }}</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.jQuery?.fn?.DataTable) {
        const table = window.jQuery('table.data-table');
        const firstBodyRow = table.find('tbody tr:first');
        if (firstBodyRow.length && firstBodyRow.children('td,th').length === 1 && firstBodyRow.children('[colspan]').length) return;

        table.DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            responsive: true,
            dom: "<'row g-2 align-items-center mb-2'<'col-md-6'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row g-2 align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
            buttons: [
                { extend: 'copy', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'csv', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'excel', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'print', exportOptions: { columns: ':visible:not(.no-export)' } },
            ],
            columnDefs: [{ targets: [9], orderable: false, searchable: false }],
        });
    }
});
</script>
</body>
</html>
