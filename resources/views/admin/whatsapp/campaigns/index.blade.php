@extends('admin.layout')

@section('title', 'Campaign Reports')

@push('styles')
<style>
    .stat-badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
        font-weight: 500;
        border-radius: 6px;
    }
    .stat-total { background-color: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
    .stat-sent { background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; }
    .stat-delivered { background-color: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
    .stat-read { background-color: #e0f7fa; color: #006064; border: 1px solid #b2ebf2; }
    .stat-failed { background-color: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-dark">Campaign Reports</h1>
        <p class="text-muted mb-0">Analytics and delivery status for your sent campaigns.</p>
    </div>
    <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        New Campaign
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-4 fw-semibold border-0 py-3">Campaign ID</th>
                        <th class="fw-semibold border-0 py-3">Template</th>
                        <th class="fw-semibold border-0 py-3">Status</th>
                        <th class="fw-semibold border-0 py-3">Metrics</th>
                        <th class="fw-semibold border-0 py-3">Date</th>
                        <th class="fw-semibold border-0 py-3 text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($campaigns as $campaign)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-medium text-dark">{{ $campaign->campaign_id }}</span>
                        </td>
                        <td>
                            @if($campaign->template)
                                {{ $campaign->template->name }} <span class="text-muted small">({{ $campaign->template->language }})</span>
                            @else
                                <span class="text-muted">Unknown</span>
                            @endif
                        </td>
                        <td>
                            @if($campaign->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($campaign->status === 'scheduled')
                                <span class="badge bg-info">Scheduled</span>
                                <div class="small text-muted mt-1">{{ $campaign->scheduled_at->format('M d, H:i') }}</div>
                            @elseif($campaign->status === 'processing')
                                <span class="badge bg-primary">Processing</span>
                            @else
                                <span class="badge bg-success">Completed</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="stat-badge stat-total" title="Total Messages">{{ $campaign->total_messages }} Total</span>
                                <span class="stat-badge stat-sent" title="Sent">{{ $campaign->sent_count }} Sent</span>
                                <span class="stat-badge stat-delivered" title="Delivered">{{ $campaign->delivered_count }} Delivered</span>
                                <span class="stat-badge stat-read" title="Read">{{ $campaign->read_count }} Read</span>
                                @if($campaign->failed_count > 0)
                                    <span class="stat-badge stat-failed" title="Failed">{{ $campaign->failed_count }} Failed</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span title="{{ $campaign->created_at }}">
                                {{ $campaign->created_at->format('M d, Y') }}<br>
                                <small class="text-muted">{{ $campaign->created_at->format('h:i A') }}</small>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.whatsapp.campaigns.export', $campaign->id) }}" class="btn btn-sm btn-outline-secondary" title="Export CSV">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Export
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">No campaigns have been created yet.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())
    <div class="card-footer bg-white border-top p-3">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>
@endsection
