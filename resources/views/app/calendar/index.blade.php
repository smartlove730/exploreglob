<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Content Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <style>
        body { background: #f7f8fb; }
        .calendar-wrap { max-width: 1100px; margin: 24px auto; background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        .status-badge { font-size: .75rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Content Calendar</h2>
            <p class="text-muted mb-0">Manage scheduled posts for your account.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('app.dashboard') }}" class="btn btn-outline-secondary">Back to App</a>
            <a href="{{ route('app.media.index') }}" class="btn btn-outline-secondary">Media Library</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">Schedule Post</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title mb-2">Bulk schedule via CSV</h5>
            <form method="POST" action="{{ route('app.calendar.import') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">CSV file</label>
                    <input type="file" class="form-control" name="csv_file" accept=".csv,text/csv" required>
                </div>
                <div class="col-md-4 d-grid">
                    <button class="btn btn-outline-primary">Upload & Queue Import</button>
                </div>
            </form>
            <div class="small text-muted mt-2">
                Example columns: <code>message,scheduled_for,platforms,page_id,image_url,media_id,media_type,video_url</code><br>
                Example row: <code>"New launch post","2026-04-20 10:30:00","facebook,instagram",12,"https://cdn.example.com/photo.jpg",,image,</code>
            </div>
        </div>
    </div>

    @if(!empty($imports) && $imports->count())
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-2">Recent CSV imports</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>ID</th><th>Status</th><th>Rows</th><th>Success</th><th>Failed</th><th>Errors</th><th>Updated</th></tr></thead>
                        <tbody>
                        @foreach($imports as $import)
                            <tr>
                                <td>#{{ $import->id }}</td>
                                <td>{{ $import->status }}</td>
                                <td>{{ $import->total_rows }}</td>
                                <td>{{ $import->success_rows }}</td>
                                <td>{{ $import->failed_rows }}</td>
                                <td>
                                    @if($import->error_report_path)
                                        <a href="{{ route('app.calendar.import.errors', $import->id) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ optional($import->updated_at)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="calendar-wrap">
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST" action="{{ route('app.calendar.store') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Schedule Post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('app.calendar.partials.form', ['mode' => 'create'])</div>
            <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit Scheduled Post</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('app.calendar.partials.form', ['mode' => 'edit'])</div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" id="deleteBtn" class="btn btn-outline-danger">Delete</button>
                <button class="btn btn-primary">Update</button>
            </div>
        </form>
        <form id="deleteForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
const statusColors = { pending:'#0d6efd', processing:'#fd7e14', published:'#198754', failed:'#dc3545', cancelled:'#6c757d' };
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
    events: '{{ route('app.calendar.events') }}',
    eventDidMount: function(info) {
        const status = info.event.extendedProps.status || 'pending';
        info.el.style.borderColor = statusColors[status] || statusColors.pending;
        info.el.style.backgroundColor = statusColors[status] || statusColors.pending;
    },
    eventClick: function(info) {
        const e = info.event;
        const props = e.extendedProps;
        document.getElementById('editForm').action = `{{ url('/app/calendar') }}/${e.id}`;
        document.getElementById('deleteForm').action = `{{ url('/app/calendar') }}/${e.id}`;

        document.querySelector('#editForm [name="page_id"]').value = props.page_id || '';
        document.querySelector('#editForm [name="message"]').value = props.message || '';
        document.querySelector('#editForm [name="media_type"]').value = props.media_type || 'image';
        document.querySelector('#editForm [name="image_url"]').value = props.image_url || '';
        document.querySelector('#editForm [name="video_url"]').value = props.video_url || '';
        document.querySelector('#editForm [name="media_id"]').value = '';
        document.querySelector('#editForm [name="scheduled_for"]').value = e.startStr ? e.startStr.slice(0,16) : '';

        const selected = props.platforms || [];
        document.querySelectorAll('#editForm input[name="platforms[]"]').forEach((box) => {
            box.checked = selected.includes(box.value);
        });

        const badge = document.getElementById('editStatusBadge');
        badge.textContent = props.status || 'pending';
        badge.className = 'badge status-badge text-bg-secondary';
        if (props.status && statusColors[props.status]) {
            badge.style.backgroundColor = statusColors[props.status];
        }

        document.getElementById('editLastError').textContent = props.last_error || 'None';
        editModal.show();
    }
});
calendar.render();

function wireMediaAutoFill(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    const picker = form.querySelector('[name=\"media_id\"]');
    if (!picker) return;
    picker.addEventListener('change', function () {
        const selected = picker.options[picker.selectedIndex];
        const mediaType = selected?.dataset?.type || '';
        const mediaUrl = selected?.dataset?.url || '';
        if (!mediaUrl) return;
        if (mediaType === 'video') {
            form.querySelector('[name=\"media_type\"]').value = 'video';
            form.querySelector('[name=\"video_url\"]').value = mediaUrl;
        } else {
            form.querySelector('[name=\"media_type\"]').value = 'image';
            form.querySelector('[name=\"image_url\"]').value = mediaUrl;
        }
    });
}

wireMediaAutoFill('#createModal form');
wireMediaAutoFill('#editForm');

document.getElementById('deleteBtn').addEventListener('click', function() {
    if (confirm('Cancel this scheduled post?')) {
        document.getElementById('deleteForm').submit();
    }
});
</script>
</body>
</html>
