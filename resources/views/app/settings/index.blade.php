@extends('admin.layout')

@section('title', 'Settings')

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Account Settings</h1>
                <p class="text-muted">Update your password.</p>

                <form method="POST" action="{{ route('app.settings.password') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-12">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">New password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Confirm new password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h4 mb-3">Subscription</h2>
                <p class="text-muted">Manage or cancel your current subscription.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('app.billing.plans') }}" class="btn btn-outline-primary">Manage Subscription</a>
                    <form method="POST" action="{{ route('app.billing.cancel') }}" onsubmit="return confirm('Cancel your active subscription?')">
                        @csrf
                        <button class="btn btn-outline-danger">Cancel Subscription</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
