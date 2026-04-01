@extends('admin.layout')

@section('title', 'SaaS Overview')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">SaaS Overview</h1>
    <div class="btn-group">
        <a href="{{ route('admin.saas.users') }}" class="btn btn-outline-secondary btn-sm">Users</a>
        <a href="{{ route('admin.saas.plans') }}" class="btn btn-outline-secondary btn-sm">Plans</a>
        <a href="{{ route('admin.saas.subscriptions') }}" class="btn btn-outline-secondary btn-sm">Subscriptions</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Customers</div><div class="h4 mb-0">{{ $stats['customers'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Admins</div><div class="h4 mb-0">{{ $stats['admins'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Active Subscriptions</div><div class="h4 mb-0">{{ $stats['subscriptions_active'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Cancelled Subscriptions</div><div class="h4 mb-0">{{ $stats['subscriptions_cancelled'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Plans</div><div class="h4 mb-0">{{ $stats['plans'] }} ({{ $stats['active_plans'] }} active)</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Published Posts</div><div class="h4 mb-0">{{ $stats['posts_published'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Failed Posts</div><div class="h4 mb-0">{{ $stats['posts_failed'] }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Recent Subscriptions</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Status</th><th>Usage</th><th>Period End</th></tr></thead>
                <tbody>
                @forelse($recentSubscriptions as $subscription)
                    <tr>
                        <td>#{{ $subscription->id }}</td>
                        <td>{{ $subscription->user?->name }}<br><small class="text-muted">{{ $subscription->user?->email }}</small></td>
                        <td>{{ $subscription->plan?->name ?? '—' }}</td>
                        <td>{{ $subscription->status }}</td>
                        <td>{{ $subscription->posts_used }}</td>
                        <td>{{ optional($subscription->current_period_end)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No subscriptions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
