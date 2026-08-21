@extends('admin.layout')

@section('title', 'WhatsApp API Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">WhatsApp API Settings</h1>
        <p class="text-muted mb-0">Configure your Meta WhatsApp Business API connection, webhooks, and auto-replies.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <ul class="nav nav-tabs border-bottom" id="whatsappSettingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-tab-pane" type="button" role="tab" aria-controls="api-tab-pane" aria-selected="true">
                    API Configuration
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="webhooks-tab" data-bs-toggle="tab" data-bs-target="#webhooks-tab-pane" type="button" role="tab" aria-controls="webhooks-tab-pane" aria-selected="false">
                    Webhook Events
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="auto-reply-tab" data-bs-toggle="tab" data-bs-target="#auto-reply-tab-pane" type="button" role="tab" aria-controls="auto-reply-tab-pane" aria-selected="false">
                    Auto-Reply
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications-tab-pane" type="button" role="tab" aria-controls="notifications-tab-pane" aria-selected="false">
                    Notifications
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-4">
        <form action="{{ route('admin.whatsapp.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="tab-content" id="whatsappSettingsTabContent">
                <!-- API Configuration Tab -->
                <div class="tab-pane fade show active" id="api-tab-pane" role="tabpanel" aria-labelledby="api-tab" tabindex="0">
                    <div class="form-check form-switch mb-4 pb-3 border-bottom">
                        <input class="form-check-input" type="checkbox" name="use_embedded_signup" value="1" role="switch" id="useEmbeddedSignup" {{ old('use_embedded_signup', $settings->use_embedded_signup ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="useEmbeddedSignup">Use Embedded Signup (Meta Popup)</label>
                        <div class="form-text text-muted">
                            If enabled, users will connect their number directly through the Meta popup. If disabled (for testing before Meta App Review), manual registration will be available.
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Business Account ID</label>
                            <input type="text" name="business_account_id" class="form-control" value="{{ old('business_account_id', $settings->business_account_id) }}">
                            <div class="form-text text-primary">Must be the <strong>WhatsApp Business Account ID</strong> (WABA ID), not the App ID or Phone Number ID. Found in Meta App Dashboard > WhatsApp > API Setup.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number ID</label>
                            <input type="text" name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $settings->phone_number_id) }}">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Permanent Access Token</label>
                        <div class="input-group">
                            <input type="password" name="access_token" class="form-control" value="{{ old('access_token', $settings->access_token) }}" id="accessTokenInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="const input = document.getElementById('accessTokenInput'); input.type = input.type === 'password' ? 'text' : 'password';">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        <div class="form-text">Use a system user token for permanent access, not a temporary user token.</div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Webhook URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light" value="{{ url('/whatsapp/message/recieved') }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" title="Copy URL">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                            <div class="form-text">Configure this URL in the Meta App Dashboard.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Webhook Verify Token</label>
                            <input type="text" name="webhook_verify_token" class="form-control" value="{{ old('webhook_verify_token', $settings->webhook_verify_token) ?? 'random_string_token_12345' }}">
                        </div>
                    </div>
                    
                    <div class="mb-4 w-25">
                        <label class="form-label fw-semibold">Graph API Version</label>
                        <select class="form-select" name="api_version">
                            <option value="v20.0" {{ old('api_version', $settings->api_version) == 'v20.0' ? 'selected' : '' }}>v20.0 (Latest)</option>
                            <option value="v19.0" {{ old('api_version', $settings->api_version) == 'v19.0' ? 'selected' : '' }}>v19.0</option>
                            <option value="v18.0" {{ old('api_version', $settings->api_version) == 'v18.0' ? 'selected' : '' }}>v18.0</option>
                            <option value="v17.0" {{ old('api_version', $settings->api_version) == 'v17.0' ? 'selected' : '' }}>v17.0</option>
                        </select>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-primary px-4">Save Configuration</button>
                    </div>
                </div>
                
                <!-- Webhook Events Tab -->
                <div class="tab-pane fade" id="webhooks-tab-pane" role="tabpanel" aria-labelledby="webhooks-tab" tabindex="0">
                    <h5 class="mb-3">Subscribed Events</h5>
                    <p class="text-muted small mb-4">Select the events you want to process in the system. Note: You must also subscribe to these in the Meta App Dashboard.</p>
                    
                    @php $events = is_array($settings->notify_events) ? $settings->notify_events : []; @endphp
                    <div class="row mb-5">
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="messages" id="ev_messages" {{ in_array('messages', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_messages">messages</label>
                                <div class="small text-muted">Incoming messages from users</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="message_deliveries" id="ev_deliveries" {{ in_array('message_deliveries', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_deliveries">message_deliveries</label>
                                <div class="small text-muted">Delivery receipts (✓✓)</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="message_reads" id="ev_reads" {{ in_array('message_reads', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_reads">message_reads</label>
                                <div class="small text-muted">Read receipts (blue ✓✓)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="message_reactions" id="ev_reactions" {{ in_array('message_reactions', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_reactions">message_reactions</label>
                                <div class="small text-muted">Emoji reactions to messages</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="message_template_status_update" id="ev_templates" {{ in_array('message_template_status_update', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_templates">message_template_status_update</label>
                                <div class="small text-muted">Approvals or rejections</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="phone_number_quality_update" id="ev_quality" {{ in_array('phone_number_quality_update', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_quality">phone_number_quality_update</label>
                                <div class="small text-muted">Changes in sender quality rating</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="account_update" id="ev_account" {{ in_array('account_update', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_account">account_update</label>
                                <div class="small text-muted">Changes to business account</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="notify_events[]" value="flows" id="ev_flows" {{ in_array('flows', $events) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="ev_flows">flows</label>
                                <div class="small text-muted">WhatsApp Flows completions</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Configuration</button>
                    </div>
                </div>
                
                <!-- Auto-Reply Tab -->
                <div class="tab-pane fade" id="auto-reply-tab-pane" role="tabpanel" aria-labelledby="auto-reply-tab" tabindex="0">
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="auto_reply_enabled" value="1" role="switch" id="enableAutoReply" {{ old('auto_reply_enabled', $settings->auto_reply_enabled) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="enableAutoReply">Enable Auto-Replies</label>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Welcome Message</label>
                            <textarea class="form-control" name="welcome_message" rows="4">{{ old('welcome_message', $settings->welcome_message) }}</textarea>
                            <div class="form-text">Sent immediately when a customer messages you for the first time or after 24 hours of inactivity.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Away Message</label>
                            <textarea class="form-control" name="away_message" rows="4">{{ old('away_message', $settings->away_message) }}</textarea>
                            <div class="form-text">Sent when a customer messages outside of your configured business hours.</div>
                        </div>
                    </div>
                    
                    <div class="mb-4 w-25">
                        <label class="form-label fw-semibold">Auto-Reply Delay (Seconds)</label>
                        <input type="number" name="auto_reply_delay_seconds" class="form-control" value="{{ old('auto_reply_delay_seconds', $settings->auto_reply_delay_seconds) ?? 2 }}" min="0" max="60">
                        <div class="form-text">Add a slight delay to make replies seem more natural.</div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Configuration</button>
                    </div>
                </div>
                
                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications-tab-pane" role="tabpanel" aria-labelledby="notifications-tab" tabindex="0">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="notify_email_enabled" value="1" role="switch" id="enableEmailNotify" {{ old('notify_email_enabled', $settings->notify_email_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="enableEmailNotify">Email Notifications</label>
                            </div>
                            <input type="email" name="notify_email_address" class="form-control mb-2" value="{{ old('notify_email_address', $settings->notify_email_address) }}" placeholder="support@example.com">
                            <div class="form-text">Alerts will be sent to this email address.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="slackWebhookUrl">Slack Webhook URL</label>
                            <input type="text" name="slack_webhook_url" id="slackWebhookUrl" class="form-control mb-2" placeholder="https://hooks.slack.com/services/..." value="{{ old('slack_webhook_url', $settings->slack_webhook_url) }}">
                            <div class="form-text">Paste your Slack incoming webhook URL.</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">Save Configuration</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
