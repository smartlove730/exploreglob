@extends('admin.layout')

@section('title', 'Automations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Automations</h1>
    <a href="{{ route('admin.automations.create') }}" class="btn btn-primary">Add Automation</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Queued Jobs</div><div class="h4 mb-0">{{ $queueStats['pending_jobs'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Failed Jobs</div><div class="h4 mb-0">{{ $queueStats['failed_jobs'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Last Automation Activity</div><div class="small">{{ $queueStats['last_activity'] ? \Illuminate\Support\Carbon::parse($queueStats['last_activity'])->diffForHumans() : 'No activity yet' }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p class="small text-muted">
            Run all for one user: <code>{{ url('/run-automations/{userId}') }}</code> |
            Run one config for one user: <code>{{ url('/run-automations/{userId}/{id}') }}</code> |
            Force run (ignore schedule/limit): <code>{{ url('/run-automations/{userId}/{id}?force=1') }}</code>
        </p>
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
                        <td><div>{{ $config->app?->name ?? '-' }}</div><small class="text-muted">{{ $config->page?->page_name ?? '-' }}</small></td>
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
        <h2 class="h5">Scheduled / In-Progress Automations</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
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
                            <td colspan="8" class="text-center text-muted">No scheduled or in-progress automations.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
