<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .metric-card { border: 0; box-shadow: 0 6px 20px rgba(16, 24, 40, 0.06); }
        .metric-value { font-size: 1.8rem; font-weight: 700; line-height: 1; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-muted mb-0">Track posting performance and manage your social publishing workflow.</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Admin Panel</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-danger">Logout</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-2">Scheduled Posts</p>
                    <div class="metric-value text-primary">{{ $scheduledCount }}</div>
                    <p class="small text-muted mb-0">Pending + processing posts.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-2">Published Posts</p>
                    <div class="metric-value text-success">{{ $publishedCount }}</div>
                    <p class="small text-muted mb-0">Successfully published posts.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-2">Failed Posts</p>
                    <div class="metric-value text-danger">{{ $failedCount }}</div>
                    <p class="small text-muted mb-0">Posts needing retry or edits.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Subscription & Plan</h2>
                    @if($subscription && $plan)
                        <p class="mb-1"><strong>Plan:</strong> {{ $plan->name }}</p>
                        <p class="mb-1"><strong>Status:</strong> <span class="badge text-bg-success">{{ $subscription->status }}</span></p>
                        <p class="mb-3 text-muted"><strong>Billing period end:</strong> {{ optional($subscription->current_period_end)->format('M d, Y') ?? 'N/A' }}</p>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Usage this period</span>
                            <span>{{ $usage }} / {{ $limit }}</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Usage progress" aria-valuenow="{{ $usagePercent }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $usagePercent }}%"></div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">{{ $remaining }} posts remaining in current period.</p>
                    @else
                        <p class="text-muted mb-3">No active subscription found.</p>
                    @endif
                    <a href="{{ route('app.billing.plans') }}" class="btn btn-outline-primary mt-3">Manage Subscription</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Connected Accounts</h2>
                    <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2 bg-light">
                        <span>Facebook / Instagram</span>
                        <span class="badge {{ $facebookConnected ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $facebookConnected ? 'Connected' : 'Not connected' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-3 bg-light">
                        <span>Google Business</span>
                        <span class="badge {{ $googleConnected ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $googleConnected ? 'Connected' : 'Not connected' }}</span>
                    </div>
                    @if(auth()->user()->isAdmin())
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.facebook.settings') }}" class="btn btn-outline-secondary btn-sm">Facebook Settings</a>
                            <a href="{{ route('admin.google.settings') }}" class="btn btn-outline-secondary btn-sm">Google Settings</a>
                        </div>
                    @else
                        <p class="small text-muted mb-0">Need to connect accounts? Ask your workspace admin to complete setup.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card metric-card">
        <div class="card-body">
            <h2 class="h5 mb-3">Quick Actions</h2>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('app.calendar.index') }}" class="btn btn-primary">Create Post</a>
                <a href="{{ route('app.calendar.index') }}" class="btn btn-outline-primary">Schedule Post</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.facebook.settings') }}" class="btn btn-outline-secondary">Connect Accounts</a>
                @else
                    <button class="btn btn-outline-secondary" type="button" disabled>Connect Accounts</button>
                @endif
                <a href="{{ route('app.media.index') }}" class="btn btn-outline-secondary">Open Media Library</a>
                <a href="{{ route('app.billing.plans') }}" class="btn btn-outline-secondary">Manage Subscription</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
