@extends('admin.layout')

@section('title', 'SaaS Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Customer Users</h1>
    <a href="{{ route('admin.saas.overview') }}" class="btn btn-outline-secondary btn-sm">Back to Overview</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <x-data-table no-export="9">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Email Verified</th>
                    <th>Subscription</th>
                    <th>FB</th>
                    <th>Google</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small></td>
                    <td><span class="badge text-bg-{{ $user->isAdmin() ? 'dark' : 'primary' }}">{{ $user->isAdmin() ? 'Admin' : 'Customer' }}</span></td>
                    <td>
                        @if($user->hasVerifiedEmail())
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning text-dark">Unverified</span>
                        @endif
                    </td>
                    <td>
                        @if($user->activeSubscription)
                            {{ $user->activeSubscription->plan?->name ?? '-' }}<br>
                            <small class="text-muted">{{ $user->activeSubscription->status }}</small>
                        @else
                            <span class="text-muted">No active subscription</span>
                        @endif
                    </td>
                    <td>{{ $user->facebook_accounts_count }}</td>
                    <td>{{ $user->google_accounts_count }}</td>
                    <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional($user->updated_at)->format('Y-m-d H:i') }}</td>
                    <td class="no-export">
                        @unless($user->hasVerifiedEmail())
                            <form method="POST" action="{{ route('admin.saas.users.verify-email', $user) }}" class="d-inline" onsubmit="return confirm('Manually verify email for {{ $user->name }}?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success mb-1" title="Manually verify this user's email">
                                    Verify
                                </button>
                            </form>
                        @endunless

                        <form method="POST" action="{{ route('admin.saas.users.toggle-whatsapp', $user) }}" class="d-inline" onsubmit="return confirm('Change WhatsApp module access for {{ $user->name }}?')">
                            @csrf
                            <input type="hidden" name="has_whatsapp_access" value="{{ $user->has_whatsapp_access ? '0' : '1' }}">
                            @if($user->has_whatsapp_access)
                                <button type="submit" class="btn btn-sm btn-success mb-1" title="Disable WhatsApp Module">
                                    <i class="bi bi-whatsapp"></i> Enabled
                                </button>
                            @else
                                <button type="submit" class="btn btn-sm btn-outline-secondary mb-1" title="Enable WhatsApp Module">
                                    <i class="bi bi-whatsapp"></i> Disabled
                                </button>
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-muted">No customers found.</td></tr>
            @endforelse
            </tbody>
        </x-data-table>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection
