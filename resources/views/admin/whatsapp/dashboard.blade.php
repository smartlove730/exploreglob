@extends('admin.layout')

@section('title', 'WhatsApp Business API')

@section('content')
<style>
    /* Scoped styles for WhatsApp Dashboard */
    .wa-dashboard {
        --wa-brand: #25D366;
        --wa-dark: #128C7E;
        --wa-darker: #075E54;
        --wa-light: #DCF8C6;
        --dash-ink: #111827;
        --dash-muted: #6b7280;
        --dash-line: rgba(15, 23, 42, .08);
        color: var(--dash-ink);
    }
    .wa-hero {
        background: linear-gradient(135deg, var(--wa-darker) 0%, var(--wa-dark) 48%, var(--wa-brand) 100%);
        border-radius: 8px;
        color: #fff;
        padding: 1.4rem;
        overflow: hidden;
        position: relative;
    }
    .wa-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
        border-radius: 50%;
    }
    .wa-hero h1 { font-size: clamp(1.45rem, 2.6vw, 2.3rem); letter-spacing: 0; position: relative; z-index: 1;}
    .wa-hero p { color: rgba(255,255,255,.85); max-width: 740px; position: relative; z-index: 1;}
    .wa-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        border: 1px solid rgba(255,255,255,.3);
        background: rgba(255,255,255,.15);
        border-radius: 999px;
        color: #fff;
        padding: .38rem .7rem;
        font-size: .78rem;
        position: relative;
        z-index: 1;
    }
    
    .dash-stat-card {
        min-height: 138px;
        padding: 1rem;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--dash-line);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
    }
    .dash-stat-card::after {
        content: "";
        position: absolute;
        inset: auto -28px -34px auto;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        opacity: .08;
        background: currentColor;
    }
    .dash-stat-label {
        color: var(--dash-muted);
        font-size: .76rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .dash-stat-value {
        font-size: clamp(1.65rem, 3vw, 2.15rem);
        font-weight: 800;
        line-height: 1.1;
        margin-top: .45rem;
    }
    .dash-stat-hint {
        color: var(--dash-muted);
        font-size: .84rem;
        margin-top: .45rem;
    }
    
    .wa-card-primary { color: var(--wa-dark); }
    .wa-card-success { color: var(--wa-brand); }
    .wa-card-warning { color: #d97706; }
    .wa-card-info { color: #0891b2; }

    .dash-panel {
        padding: 1.25rem;
        border: 1px solid var(--dash-line);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
        min-height: 360px;
    }
    .dash-panel-header {
        margin-bottom: 1rem;
    }
    .dash-panel-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0;
    }
    .dash-panel-subtitle {
        color: var(--dash-muted);
        font-size: .84rem;
        margin: .15rem 0 0;
    }
    .dash-chart-wrap {
        position: relative;
        height: 275px;
    }

    /* Quick Actions */
    .wa-action-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem;
        background: #fff;
        border: 1px solid var(--dash-line);
        border-radius: 8px;
        text-decoration: none;
        color: var(--dash-ink);
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
        height: 100%;
    }
    .wa-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
        border-color: var(--wa-brand);
    }
    .wa-action-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: rgba(37, 211, 102, 0.12);
        color: var(--wa-dark);
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }
    .wa-action-icon svg { width: 22px; height: 22px; }
    .wa-action-content h3 { font-size: 1rem; font-weight: 700; margin: 0 0 0.25rem; }
    .wa-action-content p { font-size: 0.825rem; color: var(--dash-muted); margin: 0; }
    
    /* Table */
    .wa-table { margin-bottom: 0; }
    .wa-table th { 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        color: var(--dash-muted); 
        font-weight: 700; 
        border-bottom: 2px solid var(--dash-line);
        padding: 0.75rem 1rem;
    }
    .wa-table td { 
        vertical-align: middle; 
        padding: 1rem; 
        font-size: 0.9rem;
        border-bottom: 1px solid var(--dash-line);
    }
    .wa-table tr:last-child td { border-bottom: none; }
</style>

<div class="wa-dashboard">
    <!-- Hero Section -->
    <section class="wa-hero mb-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <div class="wa-chip mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Business API Platform
                </div>
                <h1 class="mb-2">WhatsApp Business API</h1>
                <p class="mb-0">
                    Manage messaging operations, monitor campaign delivery rates, and orchestrate automated conversations.
                </p>
            </div>
        </div>
    </section>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dash-stat-card wa-card-primary">
                <div class="dash-stat-label">Total Messages Sent</div>
                <div class="dash-stat-value">{{ number_format($totalMessagesSent) }}</div>
                <div class="dash-stat-hint">Across all active campaigns</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dash-stat-card wa-card-success">
                <div class="dash-stat-label">Messages Delivered</div>
                <div class="dash-stat-value">{{ number_format($messagesDelivered) }}</div>
                <div class="dash-stat-hint">{{ $deliveryRate }}% delivery rate</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dash-stat-card wa-card-info">
                <div class="dash-stat-label">Active Templates</div>
                <div class="dash-stat-value">{{ number_format($activeTemplates) }}</div>
                <div class="dash-stat-hint">Approved by Meta</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="dash-stat-card wa-card-warning">
                <div class="dash-stat-label">Active Conversations</div>
                <div class="dash-stat-value">{{ number_format($activeConversations) }}</div>
                <div class="dash-stat-hint">In 24hr service window</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">Message Volume</h2>
                        <p class="dash-panel-subtitle">Messages sent vs delivered over the last 7 days.</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="waMessageVolumeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">Delivery Status</h2>
                        <p class="dash-panel-subtitle">Current status of messages sent today.</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="waDeliveryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h4 class="mb-3 fs-5 fw-bold text-dark">Quick Actions</h4>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ route('admin.whatsapp.conversations') }}" class="wa-action-card">
                <div class="wa-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </div>
                <div class="wa-action-content">
                    <h3>Send Message</h3>
                    <p>Broadcast or send direct template messages.</p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ route('admin.whatsapp.templates') }}" class="wa-action-card">
                <div class="wa-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
                <div class="wa-action-content">
                    <h3>Create Template</h3>
                    <p>Submit new message templates for approval.</p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ route('admin.whatsapp.phone-numbers') }}" class="wa-action-card">
                <div class="wa-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="wa-action-content">
                    <h3>Manage Numbers</h3>
                    <p>Configure routing and business profiles.</p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="{{ route('admin.whatsapp.reports') }}" class="wa-action-card">
                <div class="wa-action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </div>
                <div class="wa-action-content">
                    <h3>View Analytics</h3>
                    <p>Deep dive into messaging performance.</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Messages -->
    <div class="dash-panel p-0 overflow-hidden mb-4">
        <div class="dash-panel-header p-4 pb-0 mb-3">
            <h2 class="dash-panel-title">Recent Messages</h2>
            <p class="dash-panel-subtitle">Latest template messages and broadcasts sent to users.</p>
        </div>
        <div class="table-responsive">
            <table class="table wa-table">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMessages as $msg)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $msg->conversation->contact->phone_number ?? 'Unknown' }}</div>
                            <small class="text-muted">{{ $msg->conversation->contact->name ?? 'Unknown' }}</small>
                        </td>
                        <td>
                            @if($msg->template)
                                <div class="fw-semibold">{{ $msg->template->name }}</div>
                                <small class="text-muted">{{ ucfirst($msg->template->category ?? 'Template') }}</small>
                            @else
                                <div class="fw-semibold">Free-form Message</div>
                                <small class="text-muted">{{ ucfirst($msg->type ?? 'text') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($msg->status === 'read')
                                <span class="badge bg-primary rounded-pill px-3">Read</span>
                            @elseif($msg->status === 'delivered')
                                <span class="badge bg-success rounded-pill px-3">Delivered</span>
                            @elseif($msg->status === 'failed')
                                <span class="badge bg-danger rounded-pill px-3">Failed</span>
                            @elseif($msg->status === 'sent')
                                <span class="badge bg-info rounded-pill px-3">Sent</span>
                            @else
                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ ucfirst($msg->status) }}</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $msg->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No recent messages found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(() => {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { boxWidth: 12, usePointStyle: true } }
        }
    };

    // Message Volume Line Chart
    new Chart(document.getElementById('waMessageVolumeChart'), {
        type: 'line',
        data: {
            labels: @json($last7Days),
            datasets: [
                {
                    label: 'Sent',
                    data: @json($sentData),
                    borderColor: '#128C7E',
                    backgroundColor: 'rgba(18, 140, 126, .1)',
                    borderWidth: 3,
                    tension: .35,
                    fill: true,
                    pointRadius: 3
                },
                {
                    label: 'Delivered',
                    data: @json($deliveredData),
                    borderColor: '#25D366',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: .35,
                    borderDash: [5, 5],
                    pointRadius: 2
                }
            ]
        },
        options: {
            ...chartDefaults,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // Delivery Status Doughnut Chart
    new Chart(document.getElementById('waDeliveryChart'), {
        type: 'doughnut',
        data: {
            labels: ['Delivered', 'Read', 'Failed', 'Pending'],
            datasets: [{
                data: [
                    {{ $deliveryStatusData['delivered'] ?? 0 }}, 
                    {{ $deliveryStatusData['read'] ?? 0 }}, 
                    {{ $deliveryStatusData['failed'] ?? 0 }}, 
                    {{ $deliveryStatusData['pending'] ?? 0 }}
                ],
                backgroundColor: ['#25D366', '#34B7F1', '#ef4444', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '70%'
        }
    });
})();
</script>
@endpush
