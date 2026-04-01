@extends('admin.layout')

@section('title', 'SaaS Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Plans</h1>
    <a href="{{ route('admin.saas.overview') }}" class="btn btn-outline-secondary btn-sm">Back to Overview</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Plan</th><th>Price</th><th>Limit</th><th>Platforms</th><th>Subscriptions</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td>{{ $plan->name }}<br><small class="text-muted">{{ $plan->slug }}</small></td>
                        <td>{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }} / {{ $plan->interval }}</td>
                        <td>{{ $plan->post_limit }}</td>
                        <td class="small">
                            FB: {{ $plan->facebook_enabled ? 'Y' : 'N' }},
                            IG: {{ $plan->instagram_enabled ? 'Y' : 'N' }},
                            GB: {{ $plan->google_business_enabled ? 'Y' : 'N' }}
                        </td>
                        <td>{{ $plan->subscriptions_count }}</td>
                        <td>
                            <span class="badge {{ $plan->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.saas.plans.toggle', $plan) }}">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $plan->is_active ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
