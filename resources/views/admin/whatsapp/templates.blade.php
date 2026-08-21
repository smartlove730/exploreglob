@extends('admin.layout')

@section('title', 'Message Templates')

@push('styles')
<style>
    .wa-btn {
        background-color: #25D366;
        border-color: #25D366;
        color: white;
        transition: all 0.2s ease;
    }
    .wa-btn:hover, .wa-btn:focus {
        background-color: #128C7E;
        border-color: #128C7E;
        color: white;
    }
    .wa-text {
        color: #128C7E;
    }
    .wa-accent {
        accent-color: #25D366;
    }
    
    .template-card {
        border-left: 4px solid transparent;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .template-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
    }
    .template-card.status-approved { border-left-color: #25D366; }
    .template-card.status-pending { border-left-color: #ffc107; }
    .template-card.status-rejected { border-left-color: #dc3545; }
    
    .snippet-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px;
        font-size: 0.85rem;
        color: #495057;
        font-family: 'Inter', monospace;
        white-space: pre-wrap;
        line-height: 1.4;
        border: 1px solid #e9ecef;
    }
    
    .filter-icon {
        color: #adb5bd;
    }
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-dark">Message Templates</h1>
        <p class="text-muted mb-0">Create, manage, and submit WhatsApp message templates for approval.</p>
    </div>
    <button type="button" class="btn wa-btn fw-medium d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        New Template
    </button>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Approved</div>
                    <div class="h3 mb-0 fw-bold">{{ $templates->where('status', 'approved')->count() }}</div>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Pending Review</div>
                    <div class="h3 mb-0 fw-bold">{{ $templates->where('status', 'pending')->count() }}</div>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-flex align-items-center justify-content-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1">Rejected</div>
                    <div class="h3 mb-0 fw-bold">{{ $templates->where('status', 'rejected')->count() }}</div>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle d-flex align-items-center justify-content-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters / Toolbar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-light filter-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" class="form-control border-light bg-light shadow-none" placeholder="Search templates...">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Cards Grid -->
<div class="row g-4 mb-4">
    @forelse($templates as $template)
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 template-card status-{{ strtolower($template->status) }}">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0 fw-bold text-dark text-truncate" title="{{ $template->name }}">{{ $template->name }}</h5>
                    @if($template->status === 'approved')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">Approved</span>
                    @elseif($template->status === 'pending')
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1">Pending</span>
                    @else
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">{{ ucfirst($template->status) }}</span>
                    @endif
                </div>
                <div class="mb-3 d-flex gap-2">
                    <span class="badge bg-secondary text-white rounded-pill px-2">{{ ucfirst(strtolower($template->category)) }}</span>
                    <span class="badge bg-light text-dark border text-muted rounded-pill px-2">{{ $template->language }}</span>
                </div>
                <div class="snippet-preview flex-grow-1 mb-4">{{ $template->body }}</div>
                <div class="d-flex gap-2 mt-auto">
                    <button type="button" class="btn btn-light btn-sm text-danger flex-grow-1 fw-medium" onclick="deleteTemplate({{ $template->id }}, '{{ $template->name }}')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete Template</button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-light text-center py-5">
            <div class="text-muted mb-3">No templates found.</div>
            <button class="btn wa-btn" data-bs-toggle="modal" data-bs-target="#createTemplateModal">Create your first template</button>
        </div>
    </div>
    @endforelse
</div>

<!-- Create Template Modal -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Create Message Template</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="templateForm">
                    @csrf
                    <div id="formAlert" class="alert d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Template Name</label>
                            <input type="text" name="name" class="form-control shadow-none border-light bg-light" placeholder="e.g. welcome_message" required>
                            <div class="form-text">Use lowercase and underscores only.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Category</label>
                            <select name="category" class="form-select shadow-none border-light bg-light" required>
                                <option value="MARKETING">Marketing</option>
                                <option value="UTILITY">Utility</option>
                                <option value="AUTHENTICATION">Authentication</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Language</label>
                            <select name="language" class="form-select shadow-none border-light bg-light" required>
                                <option value="en_US">English (US)</option>
                                <option value="en_GB">English (UK)</option>
                                <option value="es">Spanish</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Header Type <span class="text-muted fw-normal">(Optional)</span></label>
                            <select name="header_type" class="form-select shadow-none border-light bg-light" onchange="document.getElementById('header_content_group').style.display = this.value === 'text' ? 'block' : 'none'">
                                <option value="none">None</option>
                                <option value="text">Text</option>
                            </select>
                        </div>
                        
                        <div class="col-12" id="header_content_group" style="display: none;">
                            <label class="form-label fw-semibold text-dark">Header Text</label>
                            <input type="text" name="header_content" class="form-control shadow-none border-light bg-light" placeholder="Header text">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Message Body</label>
                            <textarea name="body" class="form-control shadow-none border-light bg-light" rows="4" placeholder="Hi {{1}}, welcome to our platform!" required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Footer <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" name="footer" class="form-control shadow-none border-light bg-light" placeholder="Footer text">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitTemplateBtn" class="btn wa-btn px-4">Submit Template</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('submitTemplateBtn').addEventListener('click', function() {
        const form = document.getElementById('templateForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Submitting...';
        btn.disabled = true;

        const alertBox = document.getElementById('formAlert');
        alertBox.classList.add('d-none');
        alertBox.classList.remove('alert-success', 'alert-danger');

        const formData = new FormData(form);

        fetch('{{ route('admin.whatsapp.templates.store') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertBox.classList.remove('d-none');
                alertBox.classList.add('alert-success');
                alertBox.innerText = data.message;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.error || 'Failed to submit template.');
            }
        })
        .catch(error => {
            alertBox.classList.remove('d-none');
            alertBox.classList.add('alert-danger');
            alertBox.innerText = error.message;
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });

    function deleteTemplate(id, name) {
        if (confirm(`Are you sure you want to delete the template "${name}"? This action cannot be undone and it will be permanently deleted from both the database and Meta API.`)) {
            fetch(`{{ route('admin.whatsapp.templates') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete template.'));
                }
            })
            .catch(error => {
                alert('An error occurred while deleting the template.');
                console.error(error);
            });
        }
    }
</script>
@endpush
