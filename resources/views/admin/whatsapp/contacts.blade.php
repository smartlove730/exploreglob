@extends('admin.layout')

@section('title', 'Contacts & Audiences - WhatsApp')

@push('styles')
<style>
    .wa-text-green { color: #25D366 !important; }
    .wa-bg-green { background-color: #25D366 !important; color: white !important; }
    .wa-btn { background-color: #25D366; color: white; border: none; }
    .wa-btn:hover { background-color: #128C7E; color: white; }
    .wa-btn-outline { color: #25D366; border-color: #25D366; }
    .wa-btn-outline:hover { background-color: #25D366; color: white; }
    
    .upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 3rem 1.5rem;
        text-align: center;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .upload-area:hover {
        border-color: #25D366;
        background-color: rgba(37, 211, 102, 0.05);
    }
    .upload-area-icon {
        width: 48px;
        height: 48px;
        color: #94a3b8;
        margin-bottom: 1rem;
    }
    .upload-area:hover .upload-area-icon {
        color: #25D366;
    }

    .group-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .group-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Contacts & Audiences</h1>
        <p class="text-muted mb-0">Manage your WhatsApp contacts, group them into audiences, and track their opt-in status.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#createContactModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Add Contact
        </button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            Import Contacts
        </button>
        <button class="btn wa-btn" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create Group
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small fw-semibold">Total Contacts</div>
                    <div class="p-2 rounded-2 bg-light">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                </div>
                <div class="h3 mb-0 fw-bold">{{ number_format($contactStats->total ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small fw-semibold">Active Groups</div>
                    <div class="p-2 rounded-2 bg-light">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    </div>
                </div>
                <div class="h3 mb-0 fw-bold">{{ number_format($activeGroupsCount) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small fw-semibold">Opted-in</div>
                    <div class="p-2 rounded-2" style="background-color: rgba(37, 211, 102, 0.1);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                </div>
                <div class="h3 mb-0 fw-bold wa-text-green">{{ number_format($contactStats->opted_in ?? 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small fw-semibold">Opted-out</div>
                    <div class="p-2 rounded-2" style="background-color: rgba(239, 68, 68, 0.1);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    </div>
                </div>
                <div class="h3 mb-0 fw-bold text-danger">{{ number_format($contactStats->opted_out ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-3">
        <ul class="nav nav-tabs" id="contactsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-medium" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts-tab-pane" type="button" role="tab" aria-controls="contacts-tab-pane" aria-selected="true">All Contacts</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups-tab-pane" type="button" role="tab" aria-controls="groups-tab-pane" aria-selected="false">Groups</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-medium text-danger" id="trash-tab" data-bs-toggle="tab" data-bs-target="#trash-tab-pane" type="button" role="tab" aria-controls="trash-tab-pane" aria-selected="false">Trash</button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-0">
        <div class="tab-content" id="contactsTabContent">
            
            <!-- All Contacts Tab -->
            <div class="tab-pane fade show active" id="contacts-tab-pane" role="tabpanel" aria-labelledby="contacts-tab" tabindex="0">
                <form action="{{ route('admin.whatsapp.contacts') }}" method="GET" class="p-3 border-bottom d-flex gap-2 align-items-center bg-light">
                    <div class="input-group input-group-sm w-auto" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-end-0"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search contacts..." value="{{ request('search') }}">
                    </div>
                    <select name="group_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">Filter by Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(request('group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <select name="opted_in" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">Opt-in Status</option>
                        <option value="1" @selected(request('opted_in') === '1')>Opted In</option>
                        <option value="0" @selected(request('opted_in') === '0')>Opted Out</option>
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-primary">Filter</button></noscript>
                    
                    <div class="ms-auto">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            Bulk Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><button type="button" class="dropdown-item" onclick="openBulkGroupModal()">Add to Group</button></li>
                        </ul>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-3" style="width: 40px;"><input class="form-check-input" type="checkbox" id="selectAllContacts"></th>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Groups</th>
                                <th>Opted In</th>
                                <th>Last Message</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contacts as $contact)
                            <tr>
                                <td class="ps-3"><input class="form-check-input contact-checkbox" type="checkbox" value="{{ $contact->id }}"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $initials = collect(explode(' ', $contact->name ?? 'U N'))->map(fn($n) => substr($n, 0, 1))->take(2)->implode('');
                                            $colors = ['primary', 'info', 'secondary', 'success', 'warning', 'danger', 'dark'];
                                            $color = $colors[$contact->id % count($colors)];
                                        @endphp
                                        <div class="rounded-circle bg-{{ $color }} text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 14px;">{{ strtoupper($initials) }}</div>
                                        <div class="fw-medium">{{ $contact->name ?? 'Unknown' }}</div>
                                    </div>
                                </td>
                                <td>{{ $contact->phone_number }}</td>
                                <td>
                                    @forelse($contact->groups as $group)
                                        <span class="badge bg-light text-dark border">{{ $group->name }}</span>
                                    @empty
                                        <span class="text-muted small">None</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($contact->opted_in)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">Yes</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2">No</span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $contact->last_message_at ? $contact->last_message_at->diffForHumans() : 'Never' }}
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <button class="btn btn-sm btn-light rounded-circle p-1 me-1" onclick="openEditModal({{ $contact->id }}, '{{ addslashes($contact->name ?? '') }}', '{{ addslashes($contact->phone_number) }}', {{ $contact->opted_in ? 'true' : 'false' }}, {{ json_encode($contact->groups->pluck('id')) }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <form action="{{ route('admin.whatsapp.contacts.destroy', $contact->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to move this contact to trash?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light rounded-circle p-1 text-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mb-2 text-muted opacity-50"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <p class="mb-0">No contacts found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted small">
                        Showing {{ $contacts->firstItem() ?? 0 }} to {{ $contacts->lastItem() ?? 0 }} of {{ $contacts->total() }} entries
                    </div>
                    <div>
                        {{ $contacts->links() }}
                    </div>
                </div>
            </div>
            
            <!-- Groups Tab -->
            <div class="tab-pane fade p-4" id="groups-tab-pane" role="tabpanel" aria-labelledby="groups-tab" tabindex="0">
                <div class="row g-4">
                    @forelse($groups as $group)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 group-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $icons = [
                                                '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
                                                '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
                                                '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
                                                '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
                                                '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>'
                                            ];
                                            $colors = ['primary', 'info', 'success', 'warning', 'danger'];
                                            $icon = $icons[$group->id % count($icons)];
                                            $color = $colors[$group->id % count($colors)];
                                        @endphp
                                        <div class="p-2 bg-{{ $color }} bg-opacity-10 text-{{ $color }} rounded">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                                        </div>
                                        <h5 class="card-title mb-0 fw-bold">{{ $group->name }}</h5>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button type="button" class="dropdown-item" onclick="openEditGroupModal({{ $group->id }}, '{{ addslashes($group->name) }}', '{{ addslashes($group->description ?? '') }}')">Edit Group</button></li>
                                            <li><a class="dropdown-item" href="{{ route('admin.whatsapp.contacts.groups.export', $group) }}">Export Members</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button type="button" class="dropdown-item text-danger" onclick="openDeleteGroupModal({{ $group->id }}, '{{ addslashes($group->name) }}')">Delete Group</button></li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="card-text text-muted small mb-4">{{ $group->description ?? 'No description provided.' }}</p>
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <div class="small fw-semibold text-dark">{{ number_format($group->contacts_count) }} Members</div>
                                    <div class="small text-muted">Created: {{ $group->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-muted opacity-50"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                            <h5 class="text-muted mb-1">No groups found</h5>
                            <p class="text-muted small">Create a group to organize your contacts.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            
            <!-- Trash Tab -->
            <div class="tab-pane fade" id="trash-tab-pane" role="tabpanel" aria-labelledby="trash-tab" tabindex="0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th>Phone Number</th>
                                <th>Deleted At</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trashedContacts as $contact)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        @php
                                            $initials = collect(explode(' ', $contact->name ?? 'U'))->map(function($segment) {
                                                return strtoupper(substr($segment, 0, 1));
                                            })->take(2)->join('');
                                        @endphp
                                        <div class="avatar-circle bg-danger bg-opacity-10 text-danger fw-bold small rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $contact->name ?? 'Unknown' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-secondary">{{ $contact->phone_number }}</span></td>
                                <td class="text-secondary small">
                                    {{ $contact->deleted_at ? $contact->deleted_at->diffForHumans() : 'Unknown' }}
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <form action="{{ route('admin.whatsapp.contacts.restore', $contact->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light rounded-circle p-1 me-1 text-success" title="Restore">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.whatsapp.contacts.forceDelete', $contact->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this contact? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light rounded-circle p-1 text-danger" title="Permanently Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mb-3 text-muted opacity-50"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    <h5 class="text-muted mb-1">Trash is empty</h5>
                                    <p class="text-muted small">No deleted contacts found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted small">
                        Showing {{ $trashedContacts->firstItem() ?? 0 }} to {{ $trashedContacts->lastItem() ?? 0 }} of {{ $trashedContacts->total() }} entries
                    </div>
                    <div>
                        {{ $trashedContacts->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="importModalLabel">Import Contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="text-muted small mb-0">Upload a CSV or Excel file containing your contacts. Ensure you have a 'Phone Number' column.</p>
                    <a href="{{ route('admin.whatsapp.contacts.sample') }}" class="btn btn-sm btn-outline-secondary text-nowrap ms-3 d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Sample CSV
                    </a>
                </div>
                
                <div class="upload-area mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="upload-area-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <h6 class="fw-bold mb-1">Click to upload or drag and drop</h6>
                    <p class="text-muted small mb-0">CSV, XLSX, or XLS (Max 5MB)</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Add to Group (Optional)</label>
                    <select class="form-select">
                        <option value="">Select a group...</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="optInCheck" checked>
                    <label class="form-check-label small" for="optInCheck">
                        Mark all imported contacts as Opted-in
                    </label>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn wa-btn">Import Data</button>
            </div>
        </div>
    </div>
</div>
<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="createGroupModalLabel">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.whatsapp.contacts.groups.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Group Name</label>
                        <input type="text" name="name" class="form-control shadow-none border-light bg-light" required placeholder="e.g. VIP Customers">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Description <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="description" class="form-control shadow-none border-light bg-light" rows="3" placeholder="What is this group for?"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn wa-btn px-4">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Group Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editGroupModalLabel">Edit Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editGroupForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Group Name</label>
                        <input type="text" name="name" id="editGroupName" class="form-control shadow-none border-light bg-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Description <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="description" id="editGroupDescription" class="form-control shadow-none border-light bg-light" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn wa-btn px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Group Modal -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1" aria-labelledby="deleteGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteGroupModalLabel">Delete Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteGroupForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <p>Are you sure you want to delete the group <strong id="deleteGroupName"></strong>?</p>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="delete_contacts" id="deleteGroupContacts" value="1">
                        <label class="form-check-label text-danger fw-medium" for="deleteGroupContacts">
                            Also delete all contacts within this group (Moves them to trash)
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Delete Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Create Contact Modal -->
<div class="modal fade" id="createContactModal" tabindex="-1" aria-labelledby="createContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="createContactModalLabel">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.whatsapp.contacts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Name</label>
                        <input type="text" name="name" class="form-control shadow-none border-light bg-light" placeholder="e.g. John Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control shadow-none border-light bg-light" placeholder="e.g. +1234567890" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Assign to Groups</label>
                        <select name="group_ids[]" class="form-select shadow-none border-light bg-light" multiple size="3">
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Hold Ctrl/Cmd to select multiple groups.</div>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input mt-1" type="checkbox" name="opted_in" id="createContactOptedIn" value="1" checked>
                        <label class="form-check-label fw-medium text-dark ms-1" for="createContactOptedIn">
                            Opted In
                        </label>
                        <div class="form-text small">Users must opt-in to receive template messages.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn wa-btn px-4">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Add to Group Modal -->
<div class="modal fade" id="bulkAddGroupModal" tabindex="-1" aria-labelledby="bulkAddGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="bulkAddGroupModalLabel">Add Contacts to Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.whatsapp.contacts.bulk-groups') }}" method="POST" id="bulkAddGroupForm">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Select a group to add the <strong id="bulkSelectedCount">0</strong> selected contacts to.</p>
                    
                    <div id="bulkContactIdsContainer"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Group</label>
                        <select name="group_id" class="form-select shadow-none border-light bg-light" required>
                            <option value="">Select a group...</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn wa-btn px-4">Add to Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div class="modal fade" id="editContactModal" tabindex="-1" aria-labelledby="editContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="editContactModalLabel">Edit Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editContactForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Name</label>
                        <input type="text" name="name" id="editContactName" class="form-control shadow-none border-light bg-light">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Phone Number</label>
                        <input type="text" name="phone_number" id="editContactPhone" class="form-control shadow-none border-light bg-light" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Assign to Groups</label>
                        <select name="group_ids[]" id="editContactGroups" class="form-select shadow-none border-light bg-light" multiple size="3">
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Hold Ctrl/Cmd to select multiple groups.</div>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input mt-1" type="checkbox" name="opted_in" id="editContactOptedIn" value="1">
                        <label class="form-check-label fw-medium text-dark ms-1" for="editContactOptedIn">
                            Opted In
                        </label>
                        <div class="form-text small">Users must opt-in to receive template messages.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn wa-btn px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditModal(id, name, phone, optedIn, groupIds = []) {
        document.getElementById('editContactName').value = name;
        document.getElementById('editContactPhone').value = phone;
        document.getElementById('editContactOptedIn').checked = optedIn;
        
        const groupSelect = document.getElementById('editContactGroups');
        Array.from(groupSelect.options).forEach(option => {
            option.selected = groupIds.includes(parseInt(option.value));
        });

        const form = document.getElementById('editContactForm');
        form.action = `{{ url('admin/whatsapp/contacts') }}/${id}`;
        
        new bootstrap.Modal(document.getElementById('editContactModal')).show();
    }

    // Handle "Select All" checkbox
    const selectAllCheckbox = document.getElementById('selectAllContacts');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.contact-checkbox').forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
        });
    }

    function openBulkGroupModal() {
        const selectedIds = Array.from(document.querySelectorAll('.contact-checkbox:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) {
            alert('Please select at least one contact.');
            return;
        }

        document.getElementById('bulkSelectedCount').innerText = selectedIds.length;
        
        const container = document.getElementById('bulkContactIdsContainer');
        container.innerHTML = '';
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'contact_ids[]';
            input.value = id;
            container.appendChild(input);
        });

        new bootstrap.Modal(document.getElementById('bulkAddGroupModal')).show();
    }

    function openEditGroupModal(id, name, description) {
        document.getElementById('editGroupName').value = name;
        document.getElementById('editGroupDescription').value = description;
        
        const form = document.getElementById('editGroupForm');
        form.action = `{{ url('admin/whatsapp/contacts/groups') }}/${id}`;
        
        new bootstrap.Modal(document.getElementById('editGroupModal')).show();
    }

    function openDeleteGroupModal(id, name) {
        document.getElementById('deleteGroupName').innerText = name;
        document.getElementById('deleteGroupContacts').checked = false;
        
        const form = document.getElementById('deleteGroupForm');
        form.action = `{{ url('admin/whatsapp/contacts/groups') }}/${id}`;
        
        new bootstrap.Modal(document.getElementById('deleteGroupModal')).show();
    }
</script>
@endpush
