@extends('admin.layout')

@section('title', 'Automations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Automations</h1>
    <a href="{{ route('admin.automations.create') }}" class="btn btn-primary">Add Automation</a>
</div>

@php
    $isAdmin = auth()->user()?->isAdmin();
@endphp

@if($isAdmin)
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Queued Jobs</div><div class="h4 mb-0">{{ $queueStats['pending_jobs'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Failed Jobs</div><div class="h4 mb-0">{{ $queueStats['failed_jobs'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Last Automation Activity</div><div class="small">{{ $queueStats['last_activity'] ? \Illuminate\Support\Carbon::parse($queueStats['last_activity'])->diffForHumans() : 'No activity yet' }}</div></div></div></div>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if($isAdmin)
        <p class="small text-muted">
            Run all for one user: <code>{{ url('/run-automations/{userId}') }}</code> |
            Run one config for one user: <code>{{ url('/run-automations/{userId}/{id}') }}</code> |
            Force run (ignore schedule/limit): <code>{{ url('/run-automations/{userId}/{id}?force=1') }}</code>
        </p>
        @endif
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>App / Page</th>
                        <th>Drive Key</th>
                        <th>Platforms</th>
                        <th>Runs/Day</th>
                        <th>Daily Limit</th>
                        <th>Last Run</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($configs as $config)
                    <tr>
                        <td>{{ $config->name ?: 'Automation #'.$config->id }}</td>
                        <td>
                            <div>{{ $config->app?->name ?? '-' }}</div>
                            <small class="text-muted d-block">{{ $config->page?->page_name ?? '-' }}</small>
                            <small class="text-muted d-block">
                                Instagram:
                                {{ $config->page?->id && !empty($instagramUsernames[$config->page->id]) ? '@'.$instagramUsernames[$config->page->id] : 'Instagram not connected' }}
                            </small>
                        </td>
                        <td>{{ $config->driveApiKey?->name ?? '-' }}</td>
                        <td class="text-capitalize">{{ $config->platforms }}</td>
                        <td>{{ $config->runs_per_day }}</td>
                        <td>{{ $config->post_limit_per_day }}</td>
                        <td>{{ $config->last_run_at?->diffForHumans() ?? 'Never' }}</td>
                        <td><span class="badge text-bg-{{ $config->is_active ? 'success' : 'secondary' }}">{{ $config->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <form action="{{ route('admin.automations.toggle', $config) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning">{{ $config->is_active ? 'Pause' : 'Activate' }}</button>
                            </form>
                            <a href="{{ route('admin.automations.edit', $config) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.automations.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this automation?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No automation configs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $configs->links() }}
    </div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        @if(!empty($noPostableContentWarning))
            <div class="alert alert-warning">
                Your selected Drive folder in Automations does not contain any postable material.
            </div>
        @endif
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h5 mb-0">Scheduled / In-Progress Automations</h2>
            <div class="d-flex gap-2">
                <form id="bulk-run-now-form" action="{{ route('admin.automations.executions.bulk-run-now') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success" data-bulk-action disabled>Execute Selected Immediately</button>
                </form>
                <form id="bulk-delete-form" action="{{ route('admin.automations.executions.bulk-destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete selected executions?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-bulk-action disabled>Delete Selected</button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 36px;">
                            <input type="checkbox" id="executions-select-all" aria-label="Select all executions">
                        </th>
                        <th>Automation</th>
                        <th>Page</th>
                        <th>Execution Time</th>
                        <th>Status</th>
                        <th>Caption</th>
                        <th>Image</th>
                        <th>Details</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inProgressLogs as $log)
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    class="execution-checkbox"
                                    value="{{ $log->id }}"
                                    data-status="{{ $log->status }}"
                                    aria-label="Select execution {{ $log->id }}"
                                >
                            </td>
                            <td>{{ $log->automationConfig?->name ?: 'Automation #'.$log->automation_config_id }}</td>
                            <td>{{ $log->page?->page_name ?? '-' }}</td>
                            <td>{{ $log->scheduled_for?->toDateTimeString() ?? $log->created_at?->toDateTimeString() }}</td>
                            <td><span class="badge text-bg-{{ $log->status === 'in_progress' ? 'warning' : 'info' }}">{{ str_replace('_', ' ', ucfirst($log->status)) }}</span></td>
                            <td>{{ $log->caption ? \Illuminate\Support\Str::limit($log->caption, 80) : '-' }}</td>
                            <td>
                                @if($log->image_url)
                                    <a href="{{ $log->image_url }}" target="_blank" rel="noopener">View</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->message ?? '-' }}</td>
                            <td class="text-end">
                                @if($log->status === 'scheduled')
                                    <form action="{{ route('admin.automations.executions.run-now', $log) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Execute Immediately</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.automations.executions.destroy', $log) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this execution?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete Execution</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No scheduled or in-progress automations.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('executions-select-all');
        const checkboxes = Array.from(document.querySelectorAll('.execution-checkbox'));
        const bulkActionButtons = Array.from(document.querySelectorAll('[data-bulk-action]'));
        const bulkRunForm = document.getElementById('bulk-run-now-form');
        const bulkDeleteForm = document.getElementById('bulk-delete-form');

        if (!selectAll || checkboxes.length === 0 || !bulkRunForm || !bulkDeleteForm) {
            return;
        }

        const syncBulkSelectionInputs = () => {
            bulkRunForm.querySelectorAll('input[name="execution_ids[]"]').forEach((node) => node.remove());
            bulkDeleteForm.querySelectorAll('input[name="execution_ids[]"]').forEach((node) => node.remove());

            const selected = checkboxes.filter((checkbox) => checkbox.checked);

            selected.forEach((checkbox) => {
                const runInput = document.createElement('input');
                runInput.type = 'hidden';
                runInput.name = 'execution_ids[]';
                runInput.value = checkbox.value;
                bulkRunForm.appendChild(runInput);

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'execution_ids[]';
                deleteInput.value = checkbox.value;
                bulkDeleteForm.appendChild(deleteInput);
            });

            bulkActionButtons.forEach((button) => {
                button.disabled = selected.length === 0;
            });

            selectAll.checked = selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        };

        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            syncBulkSelectionInputs();
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncBulkSelectionInputs);
        });
    })();
</script>
@endpush
