@extends('admin.layout')

@section('title', 'Template Reports')

@push('styles')
<style>
    .status-badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
    }
    
    .status-sent {
        background-color: #e3f2fd;
        color: #0d47a1;
        border: 1px solid #bbdefb;
    }
    
    .status-delivered {
        background-color: #e8f5e9;
        color: #1b5e20;
        border: 1px solid #c8e6c9;
    }
    
    .status-read {
        background-color: #e0f7fa;
        color: #006064;
        border: 1px solid #b2ebf2;
    }
    
    .status-failed {
        background-color: #ffebee;
        color: #b71c1c;
        border: 1px solid #ffcdd2;
    }
    
    .status-pending {
        background-color: #fff8e1;
        color: #f57f17;
        border: 1px solid #ffecb3;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-dark">Template Reports</h1>
        <p class="text-muted mb-0">Track the delivery and read status of your sent template messages.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.whatsapp.reports') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small text-uppercase fw-semibold mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Template, Contact, Phone..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small text-uppercase fw-semibold mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small text-uppercase fw-semibold mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small text-uppercase fw-semibold mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Filter
                </button>
                <a href="{{ route('admin.whatsapp.reports') }}" class="btn btn-light flex-grow-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="reports-table" class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-4 fw-semibold border-0 py-3">Template</th>
                        <th class="fw-semibold border-0 py-3">Recipient</th>
                        <th class="fw-semibold border-0 py-3">Phone Number</th>
                        <th class="fw-semibold border-0 py-3">Sent At</th>
                        <th class="fw-semibold border-0 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($messages as $message)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-medium text-dark">{{ $message->content }}</div>
                        </td>
                        <td>
                            @if($message->conversation && $message->conversation->contact)
                                {{ $message->conversation->contact->name ?? 'Unknown' }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($message->conversation && $message->conversation->contact)
                                {{ $message->conversation->contact->phone_number }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            <span title="{{ $message->created_at }}">
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            @php
                                $status = strtolower($message->status);
                                $badgeClass = 'status-pending';
                                
                                if (in_array($status, ['sent'])) $badgeClass = 'status-sent';
                                elseif (in_array($status, ['delivered'])) $badgeClass = 'status-delivered';
                                elseif (in_array($status, ['read'])) $badgeClass = 'status-read';
                                elseif (in_array($status, ['failed'])) $badgeClass = 'status-failed';
                            @endphp
                            <span class="badge rounded-pill {{ $badgeClass }}">
                                {{ ucfirst($status) }}
                            </span>
                            @if($status === 'failed' && $message->error_message)
                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-danger" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $message->error_message }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                </button>
                                @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">No templates have been sent yet.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($messages->hasPages())
    <div class="card-footer bg-white border-top p-3">
        {{ $messages->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && window.jQuery.fn.DataTable) {
            window.jQuery('#reports-table').DataTable({
                paging: false,
                info: false,
                searching: false, // We have server-side search/filters
                order: [], // Server-side ordering is kept
                responsive: true,
                dom: "<'row g-2 align-items-center mb-2'<'col-md-12 text-end'B>>" +
                     "<'row'<'col-12'tr>>",
                buttons: [
                    { extend: 'copy', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'csv', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'print', className: 'btn btn-sm btn-outline-secondary' },
                ]
            });
        }
        
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>
@endpush
