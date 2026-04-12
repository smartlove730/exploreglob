@extends('admin.layout')

@section('title', 'SaaS Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Customer Users</h1>
    <a href="{{ route('admin.saas.overview') }}" class="btn btn-outline-secondary btn-sm">Back to Overview</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email Verified</th>
                        <th>Subscription</th>
                        <th>FB</th>
                        <th>Google</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small></td>
                        <td>
                            @if($user->hasVerifiedEmail())
                                <span class="badge bg-success">✅ Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">❌ Unverified</span>
                            @endif
                        </td>
                        <td>
                            @if($user->activeSubscription)
                                {{ $user->activeSubscription->plan?->name ?? '—' }}<br>
                                <small class="text-muted">{{ $user->activeSubscription->status }}</small>
                            @else
                                <span class="text-muted">No active subscription</span>
                            @endif
                        </td>
                        <td>{{ $user->facebook_accounts_count }}</td>
                        <td>{{ $user->google_accounts_count }}</td>
                        <td>{{ optional($user->created_at)->format('Y-m-d') }}</td>
                        <td>
                            @unless($user->hasVerifiedEmail())
                                <form method="POST" action="{{ route('admin.saas.users.verify-email', $user) }}" class="d-inline" onsubmit="return confirm('Manually verify email for {{ $user->name }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Manually verify this user's email">
                                        ✉️ Verify
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No customers found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>
@endsection
