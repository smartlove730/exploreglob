@extends('admin.layout')

@section('title', 'Automations')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Automations</h1>
        <p class="text-muted mb-0">Create queue-safe automated posting flows using the same publishing pipeline as Social Posts.</p>
    </div>
    <a href="{{ route('admin.automations.create') }}" class="btn btn-primary">New Automation</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Active</div><div class="h4 mb-0">{{ $rules->getCollection()->where('status', 'active')->count() }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Queued</div><div class="h4 mb-0">{{ $queueItems->where('status', 'queued')->count() }}</div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Failed</div><div class="h4 mb-0 text-danger">{{ $queueItems->where('status', 'failed')->count() }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h2 class="h5 mb-3">Automation Rules</h2>
        <x-data-table no-export="8" order='[[0, "desc"]]'>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Pages</th>
                    <th>Platforms</th>
                    <th>Frequency</th>
                    <th>Status</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $rule->name }}</div>
                            <small class="text-muted">{{ ucfirst($rule->media_source_type) }} source</small>
                        </td>
                        <td>
                            @foreach(($rule->page_ids ?? []) as $pageId)
                                <span class="badge text-bg-light text-dark">{{ $pages[$pageId]->page_name ?? 'Page #'.$pageId }}</span>
                            @endforeach
                        </td>
                        <td>{{ implode(', ', $rule->platforms ?? []) }}</td>
                        <td>{{ $rule->post_frequency }}/day, max {{ $rule->daily_limit }}/page</td>
                        <td><span class="badge text-bg-{{ $rule->status === 'active' ? 'success' : ($rule->status === 'paused' ? 'warning' : 'secondary') }}">{{ ucfirst($rule->status) }}</span></td>
                        <td>{{ $rule->success_count }}</td>
                        <td>{{ $rule->failed_count }}</td>
                        <td class="text-nowrap">
                            <form method="POST" action="{{ route('admin.automations.queue-now', $rule) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary">Queue Now</button></form>
                            @if($rule->status === 'active')
                                <form method="POST" action="{{ route('admin.automations.pause', $rule) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning">Pause</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.automations.resume', $rule) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Resume</button></form>
                            @endif
                            <a href="{{ route('admin.automations.edit', $rule) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.automations.stop', $rule) }}" class="d-inline" onsubmit="return confirm('Stop this automation and cancel queued items?')">@csrf<button class="btn btn-sm btn-outline-dark">Stop</button></form>
                            <form method="POST" action="{{ route('admin.automations.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Delete this automation?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No automations yet.</td></tr>
                @endforelse
            </tbody>
        </x-data-table>
        {{ $rules->links() }}
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Queue Management</h2>
        <x-data-table no-export="" order='[[5, "desc"]]'>
            <thead>
                <tr>
                    <th>Automation</th>
                    <th>Page</th>
                    <th>Media</th>
                    <th>Status</th>
                    <th>Scheduled</th>
                    <th>Updated</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queueItems as $item)
                    <tr>
                        <td>{{ $item->rule?->name ?? 'Automation #'.$item->automation_rule_id }}</td>
                        <td>{{ $item->page?->page_name ?? 'Page #'.$item->page_id }}</td>
                        <td><a href="{{ $item->media_url }}" target="_blank" rel="noopener">Preview</a></td>
                        <td><span class="badge text-bg-{{ $item->status === 'published' ? 'success' : ($item->status === 'failed' ? 'danger' : ($item->status === 'processing' ? 'warning' : 'secondary')) }}">{{ ucfirst($item->status) }}</span></td>
                        <td>{{ optional($item->scheduled_for)->format('M d, Y H:i') }}</td>
                        <td>{{ optional($item->updated_at)->format('M d, Y H:i') }}</td>
                        <td class="small text-muted">{{ $item->last_error ?: 'Ready' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Queue is empty.</td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
@endsection
