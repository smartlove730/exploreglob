@extends('admin.layout')

@section('title', 'SaaS Subscriptions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Subscriptions</h1>
    <a href="{{ route('admin.saas.overview') }}" class="btn btn-outline-secondary btn-sm">Back to Overview</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table class="table-sm" no-export="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Usage</th>
                    <th>Period</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th class="no-export">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($subscriptions as $subscription)
                <tr>
                    <td>#{{ $subscription->id }}</td>
                    <td>{{ $subscription->user?->name }}<br><small class="text-muted">{{ $subscription->user?->email }}</small></td>
                    <td>{{ $subscription->plan?->name ?? '-' }}</td>
                    <td><span class="badge text-bg-light">{{ $subscription->status }}</span></td>
                    <td>{{ $subscription->posts_used }} / {{ $subscription->plan?->post_limit ?? '-' }}</td>
                    <td>{{ optional($subscription->current_period_start)->format('Y-m-d') ?? '-' }} to {{ optional($subscription->current_period_end)->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ optional($subscription->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional($subscription->updated_at)->format('Y-m-d H:i') }}</td>
                    <td class="no-export">
                        <form method="POST" action="{{ route('admin.saas.subscriptions.toggle', $subscription) }}">
                            @csrf
                            <input type="hidden" name="is_active" value="{{ $subscription->status === \App\Models\Subscription::STATUS_ACTIVE ? 0 : 1 }}">
                            <button class="btn btn-sm {{ $subscription->status === \App\Models\Subscription::STATUS_ACTIVE ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                {{ $subscription->status === \App\Models\Subscription::STATUS_ACTIVE ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No subscriptions found.</td></tr>
            @endforelse
            </tbody>
        </x-data-table>
        <div class="mt-3">{{ $subscriptions->links() }}</div>
    </div>
</div>
@endsection
