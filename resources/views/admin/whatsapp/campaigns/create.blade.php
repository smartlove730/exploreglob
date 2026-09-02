@extends('admin.layout')

@section('title', 'Create Campaign')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .wa-preview {
        background-color: #efeae2;
        border-radius: 10px;
        padding: 20px;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
        background-size: cover;
    }
    .wa-bubble {
        background: #d9fdd3;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 10px;
        position: relative;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        word-wrap: break-word;
        white-space: pre-wrap;
    }
    .wa-bubble:after {
        content: '';
        position: absolute;
        width: 0;
        height: 0;
        border-top: 10px solid #d9fdd3;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        top: 0;
        right: -10px;
    }
    .wa-header {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .wa-footer {
        font-size: 0.75rem;
        color: rgba(0,0,0,0.45);
        margin-top: 5px;
    }
    .wa-buttons {
        margin-top: 5px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .wa-btn {
        background: white;
        border: 1px solid #d1d7db;
        border-radius: 5px;
        padding: 8px;
        text-align: center;
        color: #00a884;
        font-weight: 500;
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-dark">Create Campaign</h1>
        <p class="text-muted mb-0">Send or schedule template messages to multiple contacts and groups.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="campaignForm">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Template</label>
                        <select class="form-select select2" id="template_id" name="template_id" required>
                            <option value="">Choose a template...</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->language }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="variablesContainer" style="display: none;" class="mb-4 p-3 bg-light rounded border">
                        <h6 class="fw-bold mb-3">Template Variables</h6>
                        <div id="headerVariablesArea"></div>
                        <div id="bodyVariablesArea"></div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Select Individual Contacts</label>
                            <div>
                                <a href="#" class="text-primary small text-decoration-none me-2" id="selectAllContacts">Select All</a>
                                <a href="#" class="text-secondary small text-decoration-none" id="deselectAllContacts">Clear</a>
                            </div>
                        </div>
                        <select class="form-select select2" id="contact_ids" name="contact_ids[]" multiple>
                            <option value="all" class="fw-bold text-primary">-- Select All Contacts --</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name ?: $contact->phone_number }} ({{ $contact->phone_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Contact Groups</label>
                        <select class="form-select select2" id="group_ids" name="group_ids[]" multiple>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->contacts()->count() }} contacts)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Schedule (Optional)</label>
                        <input type="datetime-local" class="form-control" id="schedule_at" name="schedule_at">
                        <small class="text-muted">Leave blank to send immediately.</small>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-primary" id="btnSendNow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            Send Now
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnSchedule">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0">Message Preview</h6>
            </div>
            <div class="card-body">
                <div class="wa-preview" id="waPreviewArea" style="display: none;">
                    <div class="wa-bubble">
                        <div class="wa-header" id="previewHeader"></div>
                        <div class="wa-body" id="previewBody"></div>
                        <div class="wa-footer" id="previewFooter"></div>
                    </div>
                    <div class="wa-buttons" id="previewButtons"></div>
                </div>
                <div id="noPreviewText" class="text-muted text-center py-5">
                    Select a template to view the preview.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const templates = @json($templates);
    const previousVariables = @json($previousVariables);

    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: "Select options..."
        });

        $('#template_id').on('change', function() {
            const templateId = $(this).val();
            if (!templateId) {
                $('#variablesContainer').hide();
                $('#waPreviewArea').hide();
                $('#noPreviewText').show();
                return;
            }

            const template = templates.find(t => t.id == templateId);
            if (!template) return;

            renderVariables(template);
            updatePreview(template);
            
            $('#variablesContainer').show();
            $('#waPreviewArea').show();
            $('#noPreviewText').hide();
        });

        $(document).on('input', '.var-input', function() {
            const templateId = $('#template_id').val();
            const template = templates.find(t => t.id == templateId);
            if (template) updatePreview(template);
        });

        $(document).on('click', '.use-prev-val', function(e) {
            e.preventDefault();
            const inputId = $(this).data('target');
            const val = $(this).data('val');
            $('#' + inputId).val(val).trigger('input');
        });

        $('#selectAllContacts').on('click', function(e) {
            e.preventDefault();
            $('#contact_ids > option').prop('selected', true);
            $('#contact_ids').trigger('change');
        });

        $('#deselectAllContacts').on('click', function(e) {
            e.preventDefault();
            $('#contact_ids > option').prop('selected', false);
            $('#contact_ids').trigger('change');
        });

        $('#btnSendNow').on('click', function() {
            $('#schedule_at').val(''); // Clear schedule
            submitCampaign();
        });

        $('#btnSchedule').on('click', function() {
            if (!$('#schedule_at').val()) {
                alert('Please select a schedule time before scheduling.');
                $('#schedule_at').focus();
                return;
            }
            submitCampaign();
        });
    });

    function renderVariables(template) {
        let headerVarsArea = $('#headerVariablesArea');
        let bodyVarsArea = $('#bodyVariablesArea');
        headerVarsArea.empty();
        bodyVarsArea.empty();

        let headerMatches = template.header_content ? template.header_content.match(/\{\{(\d+)\}\}/g) : null;
        let bodyMatches = template.body ? template.body.match(/\{\{(\d+)\}\}/g) : null;
        
        let hasVars = false;
        let prevVars = previousVariables[template.id] || {header: [], body: []};

        if (template.header_type === 'TEXT' && headerMatches) {
            let uniqueVars = [...new Set(headerMatches)];
            if (uniqueVars.length > 0) {
                hasVars = true;
                headerVarsArea.append('<h6 class="text-secondary small text-uppercase">Header Variables</h6>');
                uniqueVars.forEach((match, index) => {
                    let prevVal = prevVars.header && prevVars.header[index] ? prevVars.header[index] : '';
                    let anchorHtml = prevVal ? `<a href="#" class="use-prev-val small text-decoration-none" data-target="header_var_${index}" data-val="${prevVal}">Use previous value: ${prevVal}</a>` : '';
                    
                    headerVarsArea.append(`
                        <div class="mb-3">
                            <label class="form-label small">Variable ${match}</label>
                            <input type="text" class="form-control var-input header-var" id="header_var_${index}" name="header_variables[]" placeholder="Enter value for ${match}" required>
                            ${anchorHtml}
                        </div>
                    `);
                });
            }
        }

        if (bodyMatches) {
            let uniqueVars = [...new Set(bodyMatches)];
            if (uniqueVars.length > 0) {
                hasVars = true;
                bodyVarsArea.append('<h6 class="text-secondary small text-uppercase mt-3">Body Variables</h6>');
                uniqueVars.forEach((match, index) => {
                    let prevVal = prevVars.body && prevVars.body[index] ? prevVars.body[index] : '';
                    let anchorHtml = prevVal ? `<a href="#" class="use-prev-val small text-decoration-none" data-target="body_var_${index}" data-val="${prevVal}">Use previous value: ${prevVal}</a>` : '';
                    
                    bodyVarsArea.append(`
                        <div class="mb-3">
                            <label class="form-label small">Variable ${match}</label>
                            <input type="text" class="form-control var-input body-var" id="body_var_${index}" name="body_variables[]" placeholder="Enter value for ${match}" required>
                            ${anchorHtml}
                        </div>
                    `);
                });
            }
        }

        if (!hasVars) {
            $('#variablesContainer').hide();
        }
    }

    function updatePreview(template) {
        let headerText = '';
        if (template.header_type === 'TEXT' && template.header_content) {
            headerText = template.header_content;
            let inputs = $('.header-var');
            inputs.each(function(idx) {
                let val = $(this).val() || "{{" + (idx+1) + "}}";
                headerText = headerText.replace(new RegExp(`\\{\\{${idx+1}\\}\\}`, 'g'), val);
            });
            $('#previewHeader').text(headerText).show();
        } else {
            $('#previewHeader').hide();
        }

        let bodyText = template.body || '';
        let bInputs = $('.body-var');
        bInputs.each(function(idx) {
            let val = $(this).val() || "{{" + (idx+1) + "}}";
            bodyText = bodyText.replace(new RegExp(`\\{\\{${idx+1}\\}\\}`, 'g'), val);
        });
        $('#previewBody').text(bodyText);

        if (template.footer) {
            $('#previewFooter').text(template.footer).show();
        } else {
            $('#previewFooter').hide();
        }

        let buttonsArea = $('#previewButtons');
        buttonsArea.empty();
        if (template.buttons) {
            let buttons = typeof template.buttons === 'string' ? JSON.parse(template.buttons) : template.buttons;
            buttons.forEach(btn => {
                buttonsArea.append(`<div class="wa-btn">${btn.text}</div>`);
            });
        }
    }

    function submitCampaign() {
        let formData = $('#campaignForm').serialize();

        $.ajax({
            url: "{{ route('admin.whatsapp.campaigns.store') }}",
            type: "POST",
            data: formData,
            success: function(res) {
                if(res.success) {
                    alert(res.message);
                    window.location.href = "{{ route('admin.whatsapp.campaigns.index') }}";
                }
            },
            error: function(xhr) {
                let err = xhr.responseJSON?.error || 'An error occurred';
                alert(err);
            }
        });
    }
</script>
@endpush
