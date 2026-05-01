@extends('admin.layout')

@section('title','Dashboard')

@section('content')
@php
    $toneClasses = [
        'primary' => 'dash-card-primary',
        'success' => 'dash-card-success',
        'facebook' => 'dash-card-facebook',
        'google' => 'dash-card-google',
        'info' => 'dash-card-info',
        'dark' => 'dash-card-dark',
        'warning' => 'dash-card-warning',
        'danger' => 'dash-card-danger',
        'purple' => 'dash-card-purple',
    ];

    $quickActions = [
        ['label' => 'Create Post', 'url' => route('admin.posts.create'), 'variant' => 'primary'],
        ['label' => 'Schedule Calendar', 'url' => route('app.calendar.index'), 'variant' => 'dark'],
        ['label' => 'Sync Posts', 'url' => route('admin.facebook.manage-posts.index'), 'variant' => 'outline-primary'],
        ['label' => 'New Automation', 'url' => route('admin.automations.create'), 'variant' => 'outline-dark'],
    ];

    if ($isAdmin) {
        $quickActions[] = ['label' => 'Manage Users', 'url' => route('admin.saas.users'), 'variant' => 'outline-secondary'];
    }
@endphp

<style>
    .business-dashboard {
        --dash-ink: #111827;
        --dash-muted: #6b7280;
        --dash-line: rgba(15, 23, 42, .08);
        color: var(--dash-ink);
    }
    .dash-hero {
        background:
            radial-gradient(circle at 12% 10%, rgba(59, 130, 246, .18), transparent 32%),
            radial-gradient(circle at 92% 20%, rgba(16, 185, 129, .16), transparent 28%),
            linear-gradient(135deg, #0f172a 0%, #172554 48%, #0f766e 100%);
        border-radius: 8px;
        color: #fff;
        padding: 1.4rem;
        overflow: hidden;
    }
    .dash-hero h1 { font-size: clamp(1.45rem, 2.6vw, 2.3rem); letter-spacing: 0; }
    .dash-hero p { color: rgba(255,255,255,.76); max-width: 740px; }
    .dash-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        border: 1px solid rgba(255,255,255,.22);
        background: rgba(255,255,255,.10);
        border-radius: 999px;
        color: rgba(255,255,255,.86);
        padding: .38rem .7rem;
        font-size: .78rem;
    }
    .dash-stat-card,
    .dash-panel,
    .dash-activity {
        border: 1px solid var(--dash-line);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
    }
    .dash-stat-card {
        min-height: 138px;
        padding: 1rem;
        position: relative;
        overflow: hidden;
    }
    .dash-stat-card::after {
        content: "";
        position: absolute;
        inset: auto -28px -34px auto;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        opacity: .14;
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
    .dash-card-primary { color: #2563eb; }
    .dash-card-success { color: #059669; }
    .dash-card-facebook { color: #1877f2; }
    .dash-card-google { color: #db4437; }
    .dash-card-info { color: #0891b2; }
    .dash-card-dark { color: #111827; }
    .dash-card-warning { color: #d97706; }
    .dash-card-danger { color: #dc2626; }
    .dash-card-purple { color: #7c3aed; }
    .dash-panel {
        padding: 1rem;
        min-height: 360px;
    }
    .dash-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .dash-panel-title {
        font-size: 1rem;
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
    .dash-action-bar {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }
    .dash-activity-item {
        display: grid;
        grid-template-columns: 38px 1fr auto;
        gap: .75rem;
        align-items: center;
        padding: .85rem 0;
        border-bottom: 1px solid var(--dash-line);
    }
    .dash-activity-item:last-child { border-bottom: 0; }
    .dash-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
    }
    .dash-activity-action {
        font-weight: 700;
        font-size: .9rem;
    }
    .dash-activity-meta {
        color: var(--dash-muted);
        font-size: .8rem;
    }
    @media (max-width: 575.98px) {
        .dash-hero { padding: 1rem; }
        .dash-panel { min-height: 320px; }
        .dash-chart-wrap { height: 235px; }
        .dash-activity-item { grid-template-columns: 34px 1fr; }
        .dash-activity-time { grid-column: 2; }
    }
</style>

<div class="business-dashboard">
    <section class="dash-hero mb-4">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
            <div>
                <div class="dash-chip mb-3">Live SaaS Control Center</div>
                <h1 class="mb-2">{{ $isAdmin ? 'Business Dashboard' : 'Workspace Dashboard' }}</h1>
                <p class="mb-0">
                    Track users, integrations, publishing health, automation throughput, and recent operational activity from one focused command view.
                </p>
            </div>
            <div class="dash-action-bar align-self-xl-end">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="btn btn-{{ $action['variant'] }} btn-sm">{{ $action['label'] }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        @foreach($stats as $stat)
            <div class="col-12 col-sm-6 col-xl-3 col-xxl">
                <div class="dash-stat-card {{ $toneClasses[$stat['tone']] ?? 'dash-card-primary' }}">
                    <div class="dash-stat-label">{{ $stat['label'] }}</div>
                    <div class="dash-stat-value">{{ number_format($stat['value']) }}</div>
                    <div class="dash-stat-hint">{{ $stat['hint'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">Posts Per Day</h2>
                        <p class="dash-panel-subtitle">Publishing volume created over the last 14 days.</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="postsPerDayChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">Success vs Failed Posts</h2>
                        <p class="dash-panel-subtitle">Publishing health across scheduled and social posts.</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="successFailedChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">User Growth</h2>
                        <p class="dash-panel-subtitle">{{ $isAdmin ? 'Cumulative platform registrations.' : 'Available for admins.' }}</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <div>
                        <h2 class="dash-panel-title">Connected Accounts Stats</h2>
                        <p class="dash-panel-subtitle">Facebook, Google, and synced social pages.</p>
                    </div>
                </div>
                <div class="dash-chart-wrap">
                    <canvas id="accountsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-activity p-3">
        <div class="dash-panel-header mb-0">
            <div>
                <h2 class="dash-panel-title">Recent Activities</h2>
                <p class="dash-panel-subtitle">Latest operational events captured by the app.</p>
            </div>
        </div>

        @forelse($recentActivities as $activity)
            <div class="dash-activity-item">
                <div class="dash-avatar">{{ strtoupper(mb_substr($activity->user?->name ?? 'S', 0, 1)) }}</div>
                <div>
                    <div class="dash-activity-action">{{ str_replace('.', ' ', $activity->action) }}</div>
                    <div class="dash-activity-meta">
                        {{ $activity->user?->name ?? 'System' }}
                        @if($activity->user?->email)
                            <span class="mx-1">/</span>{{ $activity->user->email }}
                        @endif
                    </div>
                </div>
                <div class="dash-activity-meta dash-activity-time text-end">{{ $activity->created_at?->diffForHumans() }}</div>
            </div>
        @empty
            <div class="text-muted py-4">No recent activity has been recorded yet.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(() => {
    const postsPerDay = @json($postsPerDay);
    const userGrowth = @json($userGrowth);
    const successVsFailed = @json($successVsFailed);
    const connectedAccountStats = @json($connectedAccountStats);

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { boxWidth: 12, usePointStyle: true } }
        }
    };

    new Chart(document.getElementById('postsPerDayChart'), {
        type: 'line',
        data: {
            labels: postsPerDay.map(item => item.label),
            datasets: [{
                label: 'Posts',
                data: postsPerDay.map(item => item.value),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, .12)',
                borderWidth: 3,
                tension: .35,
                fill: true,
                pointRadius: 3
            }]
        },
        options: {
            ...chartDefaults,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('successFailedChart'), {
        type: 'doughnut',
        data: {
            labels: ['Posted Successfully', 'Failed Posts', 'Scheduled Posts'],
            datasets: [{
                data: [successVsFailed.success, successVsFailed.failed, successVsFailed.scheduled],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '68%'
        }
    });

    new Chart(document.getElementById('userGrowthChart'), {
        type: 'bar',
        data: {
            labels: userGrowth.map(item => item.label),
            datasets: [{
                label: 'Users',
                data: userGrowth.map(item => item.value),
                backgroundColor: '#0f766e',
                borderRadius: 6
            }]
        },
        options: {
            ...chartDefaults,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('accountsChart'), {
        type: 'bar',
        data: {
            labels: ['Facebook', 'Google', 'Pages'],
            datasets: [{
                label: 'Connected',
                data: [
                    connectedAccountStats.facebook,
                    connectedAccountStats.google,
                    connectedAccountStats.pages
                ],
                backgroundColor: ['#1877f2', '#db4437', '#0891b2'],
                borderRadius: 6
            }]
        },
        options: {
            ...chartDefaults,
            indexAxis: 'y',
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endpush
