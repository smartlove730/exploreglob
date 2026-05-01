@extends('admin.layout')

@section('title', 'Mail Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Mail Settings</h1>
        <p class="text-muted mb-0">Configure SMTP for verification, password reset, alerts, and contact emails.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">SMTP Provider</h2>
                <form method="POST" action="{{ route('admin.mail-settings.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">Provider</label>
                        <select name="provider" class="form-select" id="providerSelect">
                            <option value="cpanel" @selected(old('provider', $setting->provider ?? 'cpanel') === 'cpanel')>cPanel Mailbox SMTP</option>
                            <option value="gmail" @selected(old('provider', $setting->provider ?? '') === 'gmail')>Google Workspace / Gmail SMTP</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mail Driver</label>
                        <select name="mailer" class="form-select">
                            <option value="smtp" @selected(old('mailer', $setting->mailer ?? 'smtp') === 'smtp')>SMTP</option>
                            <option value="log" @selected(old('mailer', $setting->mailer ?? '') === 'log')>Log</option>
                            <option value="array" @selected(old('mailer', $setting->mailer ?? '') === 'array')>Array</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="host" class="form-control" value="{{ old('host', $setting->host ?? '') }}" placeholder="mail.example.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" class="form-control" value="{{ old('port', $setting->port ?? 587) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Encryption</label>
                        <select name="encryption" class="form-select">
                            <option value="tls" @selected(old('encryption', $setting->encryption ?? 'tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('encryption', $setting->encryption ?? '') === 'ssl')>SSL</option>
                            <option value="" @selected(old('encryption', $setting->encryption ?? '') === '')>None</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username', $setting->username ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="{{ $setting?->password ? 'Leave blank to keep current password' : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $setting->from_name ?? config('app.name')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $setting->from_email ?? config('mail.from.address')) }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Save Mail Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Send Test Email</h2>
                <form method="POST" action="{{ route('admin.mail-settings.test') }}" class="d-flex gap-2">
                    @csrf
                    <input type="email" name="test_email" class="form-control" placeholder="you@example.com" required>
                    <button class="btn btn-outline-primary">Send Test</button>
                </form>
                @if($setting?->last_tested_at)
                    <p class="small text-muted mt-2 mb-0">Last tested {{ $setting->last_tested_at->diffForHumans() }}.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Email Logs</h2>
                <x-data-table order='[[5, "desc"]]'>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Error</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->type ?: '-' }}</td>
                                <td>{{ $log->recipient }}</td>
                                <td>{{ $log->subject ?: '-' }}</td>
                                <td><span class="badge text-bg-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'sent' ? 'success' : 'secondary') }}">{{ ucfirst($log->status) }}</span></td>
                                <td class="small text-danger">{{ $log->error_message }}</td>
                                <td>{{ optional($log->created_at)->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No email logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </x-data-table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const provider = document.getElementById('providerSelect');
    provider?.addEventListener('change', () => {
        const form = provider.closest('form');
        if (provider.value === 'gmail') {
            form.host.value = 'smtp.gmail.com';
            form.port.value = '587';
            form.encryption.value = 'tls';
        }
    });
});
</script>
@endpush
@endsection
