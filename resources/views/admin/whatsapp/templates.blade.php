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
    <a href="{{ route('admin.whatsapp.templates.create') }}" class="btn wa-btn fw-medium d-flex align-items-center gap-2 text-decoration-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        New Template
    </a>
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
        <div class="row g-3 justify-content-between align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-light filter-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" id="templateSearchInput" class="form-control border-light bg-light shadow-none" placeholder="Search templates...">
                </div>
            </div>
            <div class="col-auto">
                <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
                    <input type="radio" class="btn-check" name="viewToggle" id="viewGrid" checked autocomplete="off" onchange="toggleTemplateView('grid')">
                    <label class="btn btn-outline-secondary border-light" for="viewGrid" title="Grid View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </label>

                    <input type="radio" class="btn-check" name="viewToggle" id="viewTable" autocomplete="off" onchange="toggleTemplateView('table')">
                    <label class="btn btn-outline-secondary border-light" for="viewTable" title="Table View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid View -->
<div id="templateGridView" class="row g-4 mb-4">
    @forelse($templates as $template)
    <div class="col-md-6 col-xl-4 template-item" data-search="{{ strtolower($template->name . ' ' . $template->category . ' ' . $template->language) }}">
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
                <div class="snippet-preview flex-grow-1 mb-4">{{ Str::limit($template->body, 150) }}</div>
                <div class="d-flex gap-2 mt-auto">
                    <button type="button" class="btn btn-light btn-sm text-primary flex-grow-1 fw-medium" onclick="openSendModal({{ $template->id }}, '{{ addslashes($template->name) }}', this.getAttribute('data-body'))" data-body="{{ $template->body }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> Send
                    </button>
                    <button type="button" class="btn btn-light btn-sm text-danger flex-grow-1 fw-medium" onclick="deleteTemplate({{ $template->id }}, '{{ $template->name }}')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete</button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 template-empty-state">
        <div class="alert alert-light text-center py-5">
            <div class="text-muted mb-3">No templates found.</div>
            <a href="{{ route('admin.whatsapp.templates.create') }}" class="btn wa-btn text-decoration-none">Create your first template</a>
        </div>
    </div>
    @endforelse
    <div class="col-12 search-empty-state" style="display: none;">
        <div class="alert alert-light text-center py-5">
            <div class="text-muted">No templates match your search.</div>
        </div>
    </div>
</div>

<!-- Table View -->
<div id="templateTableView" class="card border-0 shadow-sm mb-4" style="display: none;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Template Name</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th>Preview</th>
                        <th class="text-end pe-4 no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                    <tr class="template-row" data-search="{{ strtolower($template->name . ' ' . $template->category . ' ' . $template->language) }}">
                        <td class="ps-4 fw-bold text-dark">{{ $template->name }}</td>
                        <td><span class="badge bg-secondary text-white rounded-pill px-2">{{ ucfirst(strtolower($template->category)) }}</span></td>
                        <td><span class="badge bg-light text-dark border text-muted rounded-pill px-2">{{ $template->language }}</span></td>
                        <td>
                            @if($template->status === 'approved')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1">Approved</span>
                            @elseif($template->status === 'pending')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1">Pending</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">{{ ucfirst($template->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-muted small text-truncate" style="max-width: 200px;" title="{{ $template->body }}">{{ $template->body }}</div>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-light btn-sm text-primary me-1" onclick="openSendModal({{ $template->id }}, '{{ addslashes($template->name) }}', this.getAttribute('data-body'))" data-body="{{ $template->body }}" title="Send Template">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </button>
                            <button type="button" class="btn btn-light btn-sm text-danger" onclick="deleteTemplate({{ $template->id }}, '{{ $template->name }}')" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr class="template-empty-state">
                        <td colspan="6" class="text-center py-4 text-muted">No templates found.</td>
                    </tr>
                    @endforelse
                    <tr class="search-empty-state" style="display: none;">
                        <td colspan="6" class="text-center py-4 text-muted">No templates match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>




<!-- Send Template Modal -->
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Send Template: <span id="sendTemplateName" class="text-primary"></span></h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="sendTemplateForm">
                    @csrf
                    <input type="hidden" name="template_id" id="sendTemplateId">
                    <div id="sendFormAlert" class="alert d-none"></div>
                    
                    <div class="row g-4">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold mb-3">Select Contacts</h6>
                            
                            <div class="mb-3 d-flex align-items-center gap-2">
                                <input type="checkbox" id="selectAllContacts" class="form-check-input mt-0">
                                <label for="selectAllContacts" class="form-check-label fw-medium cursor-pointer">Select All</label>
                            </div>
                            
                            <div class="contact-list pe-2" style="max-height: 400px; overflow-y: auto;">
                                @forelse($contacts as $contact)
                                    <div class="form-check mb-2 p-2 border rounded hover-bg-light">
                                        <input class="form-check-input contact-checkbox ms-1" type="checkbox" name="contact_ids[]" value="{{ $contact->id }}" id="contact_{{ $contact->id }}">
                                        <label class="form-check-label w-100 ms-2 cursor-pointer" for="contact_{{ $contact->id }}">
                                            <div class="fw-medium text-dark">{{ $contact->name ?? 'Unknown' }}</div>
                                            <div class="small text-muted">{{ $contact->phone_number }}</div>
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted small">No contacts available. Please add some contacts first.</div>
                                @endforelse
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Template Preview</h6>
                            <div id="sendTemplatePreview" class="snippet-preview w-100 mb-4" style="min-height: 150px;"></div>
                            
                            <h6 class="fw-bold mb-2 mt-4">Schedule (Optional)</h6>
                            <div class="text-muted small mb-2">Leave blank to send immediately.</div>
                            <input type="datetime-local" name="schedule_at" id="scheduleAt" class="form-control shadow-none border-light bg-light mb-3">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="scheduleBtn" class="btn btn-outline-primary px-4">Schedule for Later</button>
                <button type="button" id="sendNowBtn" class="btn wa-btn px-4">Send Now</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>


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

    function openSendModal(id, name, bodyRaw) {
        document.getElementById('sendTemplateId').value = id;
        document.getElementById('sendTemplateName').innerText = name;
        document.getElementById('sendTemplatePreview').innerText = bodyRaw;
        
        // Reset form
        document.getElementById('sendFormAlert').classList.add('d-none');
        document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('selectAllContacts').checked = false;
        document.getElementById('scheduleAt').value = '';
        
        new bootstrap.Modal(document.getElementById('sendTemplateModal')).show();
    }

    document.getElementById('selectAllContacts').addEventListener('change', function() {
        const isChecked = this.checked;
        document.querySelectorAll('.contact-checkbox').forEach(cb => {
            cb.checked = isChecked;
        });
    });

    function handleSendSubmit(isSchedule) {
        const form = document.getElementById('sendTemplateForm');
        const alertBox = document.getElementById('sendFormAlert');
        const formData = new FormData(form);
        
        if (isSchedule && !formData.get('schedule_at')) {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.innerText = 'Please select a schedule time.';
            return;
        }
        
        if (!isSchedule) {
            formData.delete('schedule_at');
        }

        const checkedContacts = document.querySelectorAll('.contact-checkbox:checked');
        if (checkedContacts.length === 0) {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.innerText = 'Please select at least one contact.';
            return;
        }

        const btn = isSchedule ? document.getElementById('scheduleBtn') : document.getElementById('sendNowBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Processing...';
        btn.disabled = true;

        alertBox.classList.add('d-none');

        fetch('{{ route('admin.whatsapp.templates.send') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alertBox.classList.remove('d-none', 'alert-danger');
                alertBox.classList.add('alert-success');
                alertBox.innerText = data.message;
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('sendTemplateModal')).hide();
                }, 1500);
            } else {
                throw new Error(data.error || 'Failed to process request.');
            }
        })
        .catch(error => {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.innerText = error.message;
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }

    document.getElementById('sendNowBtn').addEventListener('click', () => handleSendSubmit(false));
    document.getElementById('scheduleBtn').addEventListener('click', () => handleSendSubmit(true));

    function toggleTemplateView(viewType) {
        if (viewType === 'grid') {
            document.getElementById('templateGridView').style.display = 'flex';
            document.getElementById('templateTableView').style.display = 'none';
        } else {
            document.getElementById('templateGridView').style.display = 'none';
            document.getElementById('templateTableView').style.display = 'block';
        }
        localStorage.setItem('whatsappTemplateView', viewType);
    }

    // Initialize view from local storage
    document.addEventListener('DOMContentLoaded', () => {
        const savedView = localStorage.getItem('whatsappTemplateView') || 'grid';
        if (savedView === 'table') {
            document.getElementById('viewTable').checked = true;
            toggleTemplateView('table');
        }

        // Setup real-time search
        const searchInput = document.getElementById('templateSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                
                // Filter Grid View
                const gridItems = document.querySelectorAll('#templateGridView .template-item');
                let gridVisible = 0;
                gridItems.forEach(item => {
                    if (item.getAttribute('data-search').includes(query)) {
                        item.style.display = '';
                        gridVisible++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                const gridEmpty = document.querySelector('#templateGridView .search-empty-state');
                if (gridEmpty) gridEmpty.style.display = (gridVisible === 0 && gridItems.length > 0) ? 'block' : 'none';

                // Filter Table View
                const tableRows = document.querySelectorAll('#templateTableView .template-row');
                let tableVisible = 0;
                tableRows.forEach(row => {
                    if (row.getAttribute('data-search').includes(query)) {
                        row.style.display = '';
                        tableVisible++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                const tableEmpty = document.querySelector('#templateTableView .search-empty-state');
                if (tableEmpty) tableEmpty.style.display = (tableVisible === 0 && tableRows.length > 0) ? 'table-row' : 'none';
            });
        }
    });
</script>
@endpush
