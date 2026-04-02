@extends('admin.layout')

@section('title', 'SaaS Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Plans</h1>
    <a href="{{ route('admin.saas.overview') }}" class="btn btn-outline-secondary btn-sm">Back to Overview</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Create Plan</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.saas.plans.store') }}" class="row g-2">
            @csrf
            @include('admin.saas.partials.plan-fields')
            <div class="col-12">
                <button class="btn btn-primary btn-sm">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Plan</th><th>Pricing & Limits</th><th>Platforms</th><th>Subscriptions</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($plans as $plan)
                    <tr>
                        <td>{{ $plan->name }}<br><small class="text-muted">{{ $plan->slug }}</small></td>
                        <td class="small">
                            {{ $plan->currency }} {{ number_format((float) $plan->price, 2) }} / {{ $plan->interval }}<br>
                            Period posts: {{ $plan->post_limit }}<br>
                            Day/Week/Month: {{ $plan->posts_per_day_limit ?: \App\Models\Plan::DEFAULT_POSTS_PER_DAY_LIMIT }}/{{ $plan->posts_per_week_limit ?: \App\Models\Plan::DEFAULT_POSTS_PER_WEEK_LIMIT }}/{{ $plan->posts_per_month_limit ?: \App\Models\Plan::DEFAULT_POSTS_PER_MONTH_LIMIT }}<br>
                            Automations: {{ $plan->automation_limit ?: \App\Models\Plan::DEFAULT_AUTOMATION_LIMIT }}<br>
                            Apps: {{ $plan->connected_apps_limit ?: \App\Models\Plan::DEFAULT_CONNECTED_APPS_LIMIT }}, Pages: {{ $plan->synced_pages_limit ?: \App\Models\Plan::DEFAULT_SYNCED_PAGES_LIMIT }}
                        </td>
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
                        <td style="min-width:340px;">
                            <form method="POST" action="{{ route('admin.saas.plans.toggle', $plan) }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $plan->is_active ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <details>
                                <summary class="small">Edit plan</summary>
                                <form method="POST" action="{{ route('admin.saas.plans.update', $plan) }}" class="row g-2 mt-2">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.saas.partials.plan-fields', ['plan' => $plan])
                                    <div class="col-12"><button class="btn btn-primary btn-sm">Save</button></div>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
