@extends('admin.layout')

@section('title', 'Phone Numbers - WhatsApp')

@push('styles')
<style>
    .wa-text-green { color: #25D366 !important; }
    .wa-bg-green { background-color: #25D366 !important; color: white !important; }
    .wa-btn { background-color: #25D366; color: white; border: none; }
    .wa-btn:hover { background-color: #128C7E; color: white; }

    .number-card {
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }
    .number-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
    }
    .number-card.status-connected { border-left-color: #25D366; }
    .number-card.status-pending { border-left-color: #f59e0b; }
    .number-card.status-disconnected { border-left-color: #ef4444; }

    .quality-indicator {
        width: 10px; height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .quality-green { background-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
    .quality-yellow { background-color: #eab308; box-shadow: 0 0 0 3px rgba(234,179,8,.2); }
    .quality-red { background-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.2); }
    .quality-unknown { background-color: #9ca3af; box-shadow: 0 0 0 3px rgba(156,163,175,.2); }

    .status-pulse {
        display: inline-block; width: 8px; height: 8px;
        border-radius: 50%; margin-right: 6px;
        animation: pulse 2s ease-in-out infinite;
    }
    .status-pulse.connected { background: #25D366; }
    .status-pulse.pending { background: #f59e0b; }
    @keyframes pulse {
        0%,100% { opacity:1; transform:scale(1); }
        50% { opacity:.6; transform:scale(1.3); }
    }

    .loading-spinner {
        display: inline-block; width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.3); border-radius: 50%;
        border-top-color: #fff; animation: spin .6s linear infinite;
    }
    .loading-spinner.dark {
        border-color: rgba(0,0,0,.15);
        border-top-color: #333;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    .connect-hero {
        text-align: center;
        padding: 4rem 2rem 3rem;
    }
    .connect-hero-icon {
        width: 88px; height: 88px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(37,211,102,.15), rgba(18,140,126,.1));
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 1.75rem;
    }
    .connect-hero-icon svg { width: 40px; height: 40px; color: #25D366; }

    .meta-connect-btn {
        background: linear-gradient(135deg, #1877F2 0%, #0d65d9 100%);
        color: white; border: none;
        padding: .85rem 2.5rem;
        font-size: 1.05rem; font-weight: 700;
        border-radius: 10px;
        display: inline-flex; align-items: center; gap: .75rem;
        transition: all .25s ease;
        box-shadow: 0 4px 16px rgba(24,119,242,.3);
    }
    .meta-connect-btn:hover {
        background: linear-gradient(135deg, #166ae0 0%, #0b5ac5 100%);
        color: white; transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(24,119,242,.4);
    }
    .meta-connect-btn:disabled {
        opacity: .7; transform: none;
        box-shadow: 0 4px 16px rgba(24,119,242,.2);
    }
    .meta-connect-btn svg { flex-shrink: 0; }

    .flow-steps {
        display: flex; justify-content: center; gap: 2rem;
        margin-top: 2.5rem; flex-wrap: wrap;
    }
    .flow-step {
        display: flex; align-items: center; gap: .6rem;
        color: #6b7280; font-size: .85rem;
    }
    .flow-step-num {
        width: 28px; height: 28px;
        border-radius: 50%; border: 2px solid #d1d5db;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .75rem; color: #9ca3af;
        flex-shrink: 0;
    }
    .flow-step-arrow { color: #d1d5db; }

    .result-overlay {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
    }
    .result-card {
        background: white; border-radius: 16px;
        padding: 3rem; text-align: center;
        max-width: 420px; width: 90%;
        box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }
    .result-icon {
        width: 64px; height: 64px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 1.25rem;
    }
    .result-icon.success { background: rgba(37,211,102,.12); }
    .result-icon.error { background: rgba(239,68,68,.12); }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Phone Numbers</h1>
        <p class="text-muted mb-0">
            @if($hasNumber)
                Your registered WhatsApp Business API phone number.
            @else
                Connect your WhatsApp Business number through Meta's secure signup flow.
            @endif
        </p>
    </div>
    @if($canRegister)
        <button class="btn wa-btn d-flex align-items-center gap-2" onclick="launchEmbeddedSignup()" id="headerConnectBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path><line x1="12" y1="2" x2="12" y2="10"></line><line x1="8" y1="6" x2="16" y2="6"></line></svg>
            Register Number
        </button>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><polyline points="20 6 9 17 4 12"></polyline></svg>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- ====== STATE: No number registered ====== --}}
@if(!$hasNumber)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if(!$account || $account->use_embedded_signup !== false)
                {{-- EMBEDDED SIGNUP FLOW --}}
                <div class="connect-hero">
                    <div class="connect-hero-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        </svg>
                    </div>

                    <h3 class="fw-bold mb-2">Connect Your WhatsApp Business Number</h3>
                    <p class="text-muted mb-1 mx-auto" style="max-width: 520px;">
                        Click the button below to open Meta's secure signup flow. Everything — from business verification to phone number registration and OTP — happens inside Meta's popup.
                    </p>
                    <p class="text-muted small mb-4 mx-auto" style="max-width: 520px;">
                        <strong>Limit:</strong> 1 phone number per account.
                    </p>

                    <button class="meta-connect-btn" onclick="launchEmbeddedSignup()" id="mainConnectBtn">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Connect with Meta
                    </button>

                    <div class="flow-steps">
                        <div class="flow-step"><span class="flow-step-num">1</span>Login with Facebook</div>
                        <span class="flow-step-arrow d-none d-sm-block">→</span>
                        <div class="flow-step"><span class="flow-step-num">2</span>Select Business</div>
                        <span class="flow-step-arrow d-none d-sm-block">→</span>
                        <div class="flow-step"><span class="flow-step-num">3</span>Verify Phone Number</div>
                        <span class="flow-step-arrow d-none d-sm-block">→</span>
                        <div class="flow-step"><span class="flow-step-num">4</span>Connected!</div>
                    </div>
                </div>
            @else
                {{-- MANUAL REGISTRATION FLOW --}}
                <div class="p-4 p-md-5">
                    <h3 class="fw-bold mb-3">Manual Registration</h3>
                    <p class="text-muted mb-4">You have disabled Embedded Signup in your settings. Follow these steps to manually register your phone number via the Graph API.</p>
                    
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:32px;height:32px;flex-shrink:0;">1</div>
                                <div><h6 class="fw-bold mb-1">Add Phone Number ID</h6><p class="text-muted small">Enter the ID from your Meta App Dashboard.</p></div>
                            </div>
                            <form id="step1Form" onsubmit="manualRegister(event)">
                                <input type="text" class="form-control mb-2" id="manualPhoneNumberId" placeholder="Phone Number ID" required>
                                <button type="submit" class="btn btn-primary w-100" id="step1Btn">Add Number</button>
                            </form>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:32px;height:32px;flex-shrink:0;">2</div>
                                <div><h6 class="fw-bold mb-1">Request Verification</h6><p class="text-muted small">Meta will send a code to this number.</p></div>
                            </div>
                            <form id="step2Form" onsubmit="manualRequestCode(event)">
                                <select class="form-select mb-2" id="manualCodeMethod" required>
                                    <option value="SMS">SMS</option>
                                    <option value="VOICE">Phone Call (Voice)</option>
                                </select>
                                <button type="submit" class="btn btn-primary w-100" id="step2Btn">Request Code</button>
                            </form>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:32px;height:32px;flex-shrink:0;">3</div>
                                <div><h6 class="fw-bold mb-1">Verify Code</h6><p class="text-muted small">Enter the 6-digit code to connect.</p></div>
                            </div>
                            <form id="step3Form" onsubmit="manualVerifyCode(event)">
                                <input type="text" class="form-control mb-2" id="manualCode" placeholder="000000" maxlength="6" required>
                                <button type="submit" class="btn btn-success w-100" id="step3Btn">Verify & Connect</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ====== STATE: Number registered — show dynamic card + profile ====== --}}
@if($hasNumber && $phoneNumber)
    <div class="row g-4 mb-5">
        <div class="col-xl-6 col-lg-8">
            @php
                $statusLower = strtolower($phoneNumber->status);
                $isConnected = in_array($statusLower, ['connected', 'registered']);
                $isPending = in_array($statusLower, ['pending', 'pending_review']);
                $statusClass = $isConnected ? 'status-connected' : ($isPending ? 'status-pending' : 'status-disconnected');
                $statusLabel = $isConnected ? 'Connected' : ($isPending ? 'Pending' : ucfirst($statusLower));
                $iconColor = $isConnected ? '#25D366' : ($isPending ? '#f59e0b' : '#ef4444');

                $qualityLower = strtolower($phoneNumber->quality_rating ?? 'unknown');
                $qualityClass = match(true) {
                    in_array($qualityLower, ['green', 'high']) => 'quality-green',
                    in_array($qualityLower, ['yellow', 'medium']) => 'quality-yellow',
                    in_array($qualityLower, ['red', 'low', 'flagged']) => 'quality-red',
                    default => 'quality-unknown',
                };
                $qualityLabel = match(true) {
                    in_array($qualityLower, ['green', 'high']) => 'High',
                    in_array($qualityLower, ['yellow', 'medium']) => 'Medium',
                    in_array($qualityLower, ['red', 'low', 'flagged']) => 'Flagged',
                    default => 'Unknown',
                };

                $tier = $phoneNumber->messaging_limit_tier ?? '1K';
                $tierLabel = match($tier) {
                    '50' => 'Tier 0 (50/day)', '250' => 'Tier 1 (250/day)',
                    '1K' => 'Tier 1 (1K/day)', '10K' => 'Tier 2 (10K/day)',
                    '100K' => 'Tier 3 (100K/day)', 'UNLIMITED' => 'Unlimited',
                    default => 'Tier 1 (' . $tier . '/day)',
                };
            @endphp

            <div class="card border-0 shadow-sm h-100 number-card {{ $statusClass }}">
                <div class="card-body position-relative">
                    <span class="badge {{ $isConnected ? 'bg-success' : ($isPending ? 'bg-warning' : 'bg-danger') }} bg-opacity-10 {{ $isConnected ? 'text-success' : ($isPending ? 'text-warning' : 'text-danger') }} border border-{{ $isConnected ? 'success' : ($isPending ? 'warning' : 'danger') }} border-opacity-25 rounded-pill position-absolute top-0 end-0 mt-3 me-3">
                        <span class="status-pulse {{ $isConnected ? 'connected' : 'pending' }}"></span>
                        {{ $statusLabel }}
                    </span>

                    <div class="d-flex align-items-center gap-3 mb-4 mt-2">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="{{ $iconColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">{{ $phoneNumber->phone_number }}</h4>
                            <div class="text-muted small">{{ $phoneNumber->display_name }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small mb-1">Quality Rating</div>
                                <div class="d-flex align-items-center gap-2 fw-semibold">
                                    <span class="quality-indicator {{ $qualityClass }}"></span>
                                    {{ $qualityLabel }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small mb-1">Messaging Tier</div>
                                <div class="fw-semibold">{{ $tierLabel }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small mb-1">Phone Number ID</div>
                                <div class="fw-semibold small text-truncate" title="{{ $phoneNumber->phone_number_id }}">{{ $phoneNumber->phone_number_id }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small mb-1">Registered</div>
                                <div class="fw-semibold small">{{ $phoneNumber->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light flex-grow-1" onclick="syncPhoneNumber()" id="syncBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                            Sync Status
                        </button>
                        <button class="btn btn-sm btn-light px-3 text-danger" onclick="disconnectNumber()" title="Disconnect">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Account Details</h6>
                    <div class="mb-3"><div class="text-muted small">WABA ID</div><div class="fw-semibold">{{ $account->business_account_id ?? '—' }}</div></div>
                    <div class="mb-3"><div class="text-muted small">API Version</div><div class="fw-semibold">{{ $account->api_version }}</div></div>
                    <div class="mb-3"><div class="text-muted small">Access Token</div><div class="fw-semibold text-truncate" style="max-width:250px;">{{ $account->access_token ? '••••••••' . substr($account->access_token, -8) : 'Not set' }}</div></div>
                    <div class="mb-3"><div class="text-muted small">Webhook URL</div><div class="fw-semibold small text-truncate">{{ url('/whatsapp/message/recieved') }}</div></div>
                    <div class="text-muted small mt-3 border-top pt-3"><strong>Limit:</strong> 1 phone number per account.<br>To register a different number, disconnect the current one first.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Business Profile --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white pt-4 pb-3 px-4 border-bottom"><h5 class="mb-0 fw-bold">Business Profile</h5></div>
        <div class="card-body p-4">
            <form action="{{ route('admin.whatsapp.phone-numbers.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row align-items-center">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        @php
                            $profileName = $phoneNumber->business_profile_name ?? $phoneNumber->display_name ?? 'WA';
                            $profilePicUrl = $businessProfile['data'][0]['profile_picture_url'] ?? null;
                        @endphp
                        @if($profilePicUrl)
                            <img src="{{ $profilePicUrl }}" alt="Business Logo" class="rounded-circle img-thumbnail shadow-sm mb-3" style="width:120px;height:120px;object-fit:cover;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($profileName) }}&background=25D366&color=fff&size=120" alt="Business Logo" class="rounded-circle img-thumbnail shadow-sm mb-3">
                        @endif
                        <div>
                            <label class="btn btn-sm btn-outline-secondary">
                                Change Picture
                                <input type="file" name="profile_picture" class="d-none" accept="image/jpeg,image/png,image/jpg">
                            </label>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-4">
                            <div class="col-sm-6"><label class="form-label text-muted small fw-semibold">Industry / Category</label><input type="text" name="vertical" class="form-control" value="{{ $businessProfile['data'][0]['vertical'] ?? $phoneNumber->business_profile_category ?? '' }}" placeholder="e.g. Software & Technology"></div>
                            <div class="col-sm-6"><label class="form-label text-muted small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="{{ $businessProfile['data'][0]['email'] ?? '' }}" placeholder="contact@example.com"></div>
                            <div class="col-12"><label class="form-label text-muted small fw-semibold">Business Description</label><textarea class="form-control" name="description" rows="3" placeholder="Describe your business...">{{ $businessProfile['data'][0]['description'] ?? $phoneNumber->business_profile_description ?? '' }}</textarea></div>
                            <div class="col-12"><label class="form-label text-muted small fw-semibold">About</label><input type="text" name="about" class="form-control" value="{{ $businessProfile['data'][0]['about'] ?? '' }}" placeholder="Short about (max 139 chars)" maxlength="139"></div>
                        </div>
                        <div class="mt-4 text-end">
                            <a href="{{ route('admin.whatsapp.phone-numbers') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn wa-btn">Update Profile</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- Disconnect Confirmation --}}
<div class="modal fade" id="disconnectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                </div>
                <h5 class="fw-bold mb-2">Disconnect Number?</h5>
                <p class="text-muted small mb-0">This will deregister your phone number from the WhatsApp Cloud API. You can connect a new one afterwards.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDisconnectBtn" onclick="confirmDisconnect()">Disconnect</button>
            </div>
        </div>
    </div>
</div>

{{-- Result Overlay (shown after signup completes) --}}
<div id="resultOverlay" class="result-overlay" style="display:none;">
    <div class="result-card">
        <div id="resultIcon" class="result-icon"></div>
        <h4 id="resultTitle" class="fw-bold mb-2"></h4>
        <p id="resultMessage" class="text-muted mb-3"></p>
        <button class="btn btn-primary px-4" onclick="location.reload()">Continue</button>
    </div>
</div>
@endsection

@push('scripts')
{{-- Facebook JavaScript SDK --}}
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>

<script>
(() => {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const FB_APP_ID = @json($facebookAppId);
    const WA_CONFIG_ID = @json($whatsappConfigId);

    // Captured from the postMessage event during Embedded Signup
    let capturedWabaId = null;
    let capturedPhoneNumberId = null;

    // ─── Initialize Facebook SDK ───
    window.fbAsyncInit = function() {
        FB.init({
            appId: FB_APP_ID,
            autoLogAppEvents: true,
            xfbml: true,
            version: 'v20.0'
        });
    };

    // ─── Listen for Embedded Signup postMessage from Meta popup ───
    window.addEventListener('message', (event) => {
        if (event.origin !== 'https://www.facebook.com' && event.origin !== 'https://web.facebook.com') return;

        try {
            const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

            if (data.type === 'WA_EMBEDDED_SIGNUP') {
                // data.event can be: FINISH, CANCEL, ERROR, etc.
                if (data.event === 'FINISH' || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                    capturedWabaId = data.data?.waba_id || null;
                    capturedPhoneNumberId = data.data?.phone_number_id || null;
                    console.log('[Postzy] Meta Embedded Signup FINISH:', { capturedWabaId, capturedPhoneNumberId });
                } else if (data.event === 'CANCEL') {
                    console.log('[Postzy] Meta Embedded Signup CANCELLED by user');
                } else if (data.event === 'ERROR') {
                    console.error('[Postzy] Meta Embedded Signup ERROR:', data.data);
                }
            }
        } catch (e) {
            // Ignore non-JSON messages from other iframes
        }
    });

    // ─── Launch Meta Embedded Signup ───
    window.launchEmbeddedSignup = function() {
        if (typeof FB === 'undefined') {
            alert('Facebook SDK is still loading. Please wait a moment and try again.');
            return;
        }

        // Reset captured data
        capturedWabaId = null;
        capturedPhoneNumberId = null;

        let loginConfig = {};

        if (WA_CONFIG_ID) {
            // For Tech Providers using Facebook Login for Business Configuration
            loginConfig = {
                config_id: WA_CONFIG_ID,
                response_type: 'code',
                override_default_response_type: true
            };
        } else {
            // Legacy flow
            loginConfig = {
                response_type: 'code',
                override_default_response_type: true,
                scope: 'whatsapp_business_management,whatsapp_business_messaging',
                extras: {
                    feature: 'whatsapp_embedded_signup',
                    version: 2,
                    sessionInfoVersion: '3',
                    setup: {}
                }
            };
        }

        // Disable buttons
        document.querySelectorAll('#mainConnectBtn, #headerConnectBtn').forEach(btn => {
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="loading-spinner me-2"></span>Opening Meta...'; }
        });

        FB.login(function(response) {
            if (response.authResponse) {
                const code = response.authResponse.code;
                console.log('[Postzy] Got auth code from FB.login, submitting to backend...');

                // Wait a brief moment for the postMessage to arrive with waba_id + phone_number_id
                setTimeout(() => {
                    submitEmbeddedSignup(code, capturedWabaId, capturedPhoneNumberId);
                }, 800);
            } else {
                console.log('[Postzy] User cancelled or did not authorize');
                // Re-enable buttons
                document.querySelectorAll('#mainConnectBtn, #headerConnectBtn').forEach(btn => {
                    if (btn) { btn.disabled = false; btn.textContent = 'Connect with Meta'; }
                });
            }
        }, loginConfig);
    };

    // ─── Submit the auth code + asset IDs to our backend ───
    async function submitEmbeddedSignup(code, wabaId, phoneNumberId) {
        // Update buttons
        document.querySelectorAll('#mainConnectBtn, #headerConnectBtn').forEach(btn => {
            if (btn) { btn.innerHTML = '<span class="loading-spinner me-2"></span>Connecting...'; }
        });

        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.embedded-signup') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    waba_id: wabaId,
                    phone_number_id: phoneNumberId,
                }),
            });

            const data = await res.json();

            if (data.success) {
                showResult('success', 'Connected!', data.message);
            } else {
                showResult('error', 'Connection Failed', data.message || 'Something went wrong.');
            }
        } catch (err) {
            showResult('error', 'Network Error', 'Could not reach the server. Please try again.');
        }
    }

    // ─── Show result overlay ───
    function showResult(type, title, message) {
        const overlay = document.getElementById('resultOverlay');
        const icon = document.getElementById('resultIcon');
        const titleEl = document.getElementById('resultTitle');
        const messageEl = document.getElementById('resultMessage');

        icon.className = 'result-icon ' + type;
        icon.innerHTML = type === 'success'
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        titleEl.textContent = title;
        messageEl.textContent = message;
        overlay.style.display = 'flex';
    }

    // ─── Sync phone number status ───
    window.syncPhoneNumber = async function() {
        const btn = document.getElementById('syncBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner dark"></span> Syncing...';
        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.sync') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) { location.reload(); }
            else { alert(data.message || 'Sync failed.'); btn.disabled = false; btn.innerHTML = 'Sync Status'; }
        } catch { alert('Network error.'); btn.disabled = false; btn.innerHTML = 'Sync Status'; }
    };

    // ─── Disconnect ───
    window.disconnectNumber = () => new bootstrap.Modal(document.getElementById('disconnectModal')).show();

    window.confirmDisconnect = async function() {
        const btn = document.getElementById('confirmDisconnectBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span>';
        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.disconnect') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) { location.reload(); }
            else { alert(data.message); btn.disabled = false; btn.innerHTML = 'Disconnect'; }
        } catch { alert('Network error.'); btn.disabled = false; btn.innerHTML = 'Disconnect'; }
    };

    // ─── Manual Registration Methods ───
    window.manualRegister = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('step1Btn');
        const phoneId = document.getElementById('manualPhoneNumberId').value;
        btn.disabled = true; btn.innerHTML = '<span class="loading-spinner"></span>';
        
        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.register') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ phone_number_id: phoneId }),
            });
            const data = await res.json();
            if (data.success) { alert(data.message); location.reload(); }
            else { alert(data.message); btn.disabled = false; btn.innerHTML = 'Add Number'; }
        } catch { alert('Network error.'); btn.disabled = false; btn.innerHTML = 'Add Number'; }
    };

    window.manualRequestCode = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('step2Btn');
        const method = document.getElementById('manualCodeMethod').value;
        btn.disabled = true; btn.innerHTML = '<span class="loading-spinner"></span>';
        
        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.request-code') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ code_method: method }),
            });
            const data = await res.json();
            if (data.success) { alert(data.message); btn.disabled = false; btn.innerHTML = 'Code Requested'; }
            else { alert(data.message); btn.disabled = false; btn.innerHTML = 'Request Code'; }
        } catch { alert('Network error.'); btn.disabled = false; btn.innerHTML = 'Request Code'; }
    };

    window.manualVerifyCode = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('step3Btn');
        const code = document.getElementById('manualCode').value;
        btn.disabled = true; btn.innerHTML = '<span class="loading-spinner"></span>';
        
        try {
            const res = await fetch("{{ route('admin.whatsapp.phone-numbers.verify-code') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ code: code }),
            });
            const data = await res.json();
            if (data.success) { alert(data.message); location.reload(); }
            else { alert(data.message); btn.disabled = false; btn.innerHTML = 'Verify & Connect'; }
        } catch { alert('Network error.'); btn.disabled = false; btn.innerHTML = 'Verify & Connect'; }
    };
})();
</script>
@endpush
