@extends('admin.layout')

@section('title', 'Create Template')

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
    .wa-text { color: #128C7E; }

    /* ── Phone Preview ── */
    .phone-frame {
        width: 320px;
        min-height: 560px;
        border-radius: 32px;
        background: #0b141a;
        padding: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
    }
    .phone-screen {
        background: #efeae2;
        border-radius: 22px;
        overflow: hidden;
        min-height: 536px;
        display: flex;
        flex-direction: column;
    }
    .phone-topbar {
        background: #075e54;
        color: #fff;
        padding: 10px 14px;
        font-size: .82rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .phone-topbar-avatar {
        width: 30px; height: 30px; border-radius: 50%; background: #25d366;
        display: flex; align-items: center; justify-content: center; font-size: .7rem;
    }
    .phone-chat {
        flex: 1;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0;
        background: url("data:image/svg+xml,%3Csvg width='80' height='80' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h80v80H0z' fill='%23efeae2'/%3E%3Cpath d='M20 20h1v1h-1zM40 10h1v1h-1zM60 30h1v1h-1zM10 50h1v1h-1zM50 60h1v1h-1zM30 70h1v1h-1zM70 40h1v1h-1z' fill='%23d4cfc6'/%3E%3C/svg%3E");
    }
    .wa-bubble {
        background: #fff;
        border-radius: 0 8px 8px 8px;
        padding: 0;
        max-width: 100%;
        box-shadow: 0 1px 2px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .wa-bubble-header-media {
        width: 100%;
        min-height: 120px;
        background: #d9dfe3;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8696a0;
        font-size: .8rem;
    }
    .wa-bubble-header-media svg { opacity: .6; }
    .wa-bubble-header-text {
        padding: 8px 10px 0;
        font-weight: 700;
        font-size: .88rem;
        color: #111b21;
    }
    .wa-bubble-body {
        padding: 8px 10px;
        font-size: .84rem;
        color: #111b21;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.35;
    }
    .wa-bubble-footer {
        padding: 0 10px 6px;
        font-size: .72rem;
        color: #8696a0;
    }
    .wa-bubble-time {
        text-align: right;
        padding: 0 8px 6px;
        font-size: .65rem;
        color: #8696a0;
    }
    .wa-bubble-buttons {
        border-top: 1px solid #e9edef;
    }
    .wa-bubble-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px;
        font-size: .82rem;
        color: #009de2;
        font-weight: 500;
        border-bottom: 1px solid #e9edef;
        cursor: default;
    }
    .wa-bubble-btn:last-child { border-bottom: none; }
    .wa-bubble-btn svg { width: 14px; height: 14px; }

    /* ── Form Sections ── */
    .form-section {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .form-section-title {
        font-weight: 700;
        font-size: .95rem;
        margin-bottom: 4px;
        color: #111;
    }
    .form-section-desc {
        font-size: .82rem;
        color: #6c757d;
        margin-bottom: 16px;
    }

    /* ── Header type pills ── */
    .header-type-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .header-type-group .btn-check + .btn {
        border-radius: 20px;
        padding: 6px 16px;
        font-size: .82rem;
        font-weight: 500;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        color: #495057;
        transition: all .15s;
    }
    .header-type-group .btn-check:checked + .btn {
        background: #128C7E;
        border-color: #128C7E;
        color: #fff;
    }

    /* ── Body Editor ── */
    .body-editor-toolbar {
        display: flex;
        gap: 4px;
        padding: 6px 8px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-bottom: 0;
        border-radius: 8px 8px 0 0;
    }
    .body-editor-toolbar button {
        background: none;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 4px 8px;
        cursor: pointer;
        color: #495057;
        font-size: .85rem;
        transition: all .15s;
    }
    .body-editor-toolbar button:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }
    .body-editor-toolbar button.active {
        background: #128C7E;
        color: #fff;
        border-color: #128C7E;
    }
    #bodyEditor {
        border-radius: 0 0 8px 8px !important;
        border-top: 0 !important;
        min-height: 120px;
        resize: vertical;
    }

    /* ── Emoji Picker ── */
    .emoji-picker-dropdown {
        position: absolute;
        z-index: 1050;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,.12);
        padding: 12px;
        width: 320px;
        max-height: 280px;
        overflow-y: auto;
        display: none;
    }
    .emoji-picker-dropdown.show { display: block; }
    .emoji-picker-dropdown .emoji-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: none;
        background: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1.2rem;
        transition: background .1s;
    }
    .emoji-picker-dropdown .emoji-btn:hover {
        background: #e9ecef;
    }
    .emoji-category-label {
        font-size: .72rem;
        font-weight: 700;
        color: #8696a0;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 8px 0 4px;
    }
    .emoji-category-label:first-child { margin-top: 0; }

    /* ── Button Builder ── */
    .button-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        position: relative;
        margin-bottom: 8px;
    }
    .button-item .btn-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: .8rem;
    }
    .button-item .btn-remove:hover { background: #fee2e2; }

    .add-button-menu .dropdown-item.disabled {
        opacity: .5;
    }
    .add-button-menu .dropdown-item .badge {
        font-size: .65rem;
        vertical-align: middle;
    }

    /* ── Char counter ── */
    .char-counter {
        font-size: .75rem;
        color: #8696a0;
        text-align: right;
        margin-top: 4px;
    }
    .char-counter.warn { color: #e67e22; }
    .char-counter.danger { color: #dc3545; }

    /* ── Variable chips ── */
    .var-chip {
        display: inline-block;
        background: #e3f2fd;
        color: #1565c0;
        font-size: .78rem;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .var-chip:hover {
        background: #bbdefb;
    }

    @media (max-width: 991.98px) {
        .phone-frame { width: 100%; position: static; margin: 0 auto 24px; }
    }
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.templates') }}" class="text-decoration-none wa-text">Templates</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0 fw-bold text-dark">Create Message Template</h1>
    </div>
</div>

<form id="templateCreateForm" autocomplete="off">
    @csrf
    <div id="formAlert" class="alert d-none mb-3"></div>

    <div class="row g-4">
        <!-- LEFT: Form -->
        <div class="col-lg-7">

            <!-- Basic Info -->
            <div class="form-section">
                <div class="form-section-title">Basic Information</div>
                <div class="form-section-desc">Name your template and choose its category.</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="templateName" class="form-control shadow-none border-light bg-light" placeholder="e.g. welcome_message" required pattern="^[a-z0-9_]+$">
                        <div class="form-text">Lowercase letters, numbers, and underscores only.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select shadow-none border-light bg-light" required>
                            <option value="MARKETING">Marketing</option>
                            <option value="UTILITY">Utility</option>
                            <option value="AUTHENTICATION">Authentication</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Language <span class="text-danger">*</span></label>
                        <select name="language" class="form-select shadow-none border-light bg-light" required>
                            <option value="en_US">English (US)</option>
                            <option value="en_GB">English (UK)</option>
                            <option value="hi">Hindi</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                            <option value="de">German</option>
                            <option value="pt_BR">Portuguese (BR)</option>
                            <option value="ar">Arabic</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="form-section">
                <div class="form-section-title">Header <span class="text-muted fw-normal" style="font-size:.82rem">(Optional)</span></div>
                <div class="form-section-desc">Add a title or media to the top of your message.</div>

                <div class="header-type-group mb-3">
                    <input type="radio" class="btn-check" name="header_type" id="headerNone" value="none" checked>
                    <label class="btn" for="headerNone">None</label>

                    <input type="radio" class="btn-check" name="header_type" id="headerText" value="text">
                    <label class="btn" for="headerText">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>
                        Text
                    </label>

                    <input type="radio" class="btn-check" name="header_type" id="headerImage" value="image">
                    <label class="btn" for="headerImage">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        Image
                    </label>

                    <input type="radio" class="btn-check" name="header_type" id="headerVideo" value="video">
                    <label class="btn" for="headerVideo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                        Video
                    </label>

                    <input type="radio" class="btn-check" name="header_type" id="headerDocument" value="document">
                    <label class="btn" for="headerDocument">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Document
                    </label>
                </div>

                <!-- Text header input -->
                <div id="headerTextGroup" class="d-none">
                    <label class="form-label fw-semibold text-dark">Header Text</label>
                    <input type="text" name="header_content" id="headerContentInput" class="form-control shadow-none border-light bg-light" placeholder="Enter header text" maxlength="60">
                    <div class="char-counter"><span id="headerCharCount">0</span> / 60</div>
                </div>

                <!-- Media header hint -->
                <div id="headerMediaGroup" class="d-none">
                    <div class="alert alert-light border py-2 px-3 mb-0">
                        <small class="text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            Media will be uploaded when sending the template message. The preview shows a placeholder.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="form-section">
                <div class="form-section-title">Body <span class="text-danger">*</span></div>
                <div class="form-section-desc">The main content of your message. Use <span class="var-chip" onclick="insertVariable()">&#123;&#123;1&#125;&#125;</span> for variables.</div>

                <div class="position-relative">
                    <!-- Mini Toolbar -->
                    <div class="body-editor-toolbar">
                        <button type="button" onclick="wrapSelection('*')" title="Bold"><strong>B</strong></button>
                        <button type="button" onclick="wrapSelection('_')" title="Italic"><em>I</em></button>
                        <button type="button" onclick="wrapSelection('~')" title="Strikethrough"><s>S</s></button>
                        <button type="button" onclick="wrapSelection('```')" title="Monospace" style="font-family:monospace;font-size:.78rem">&lt;/&gt;</button>
                        <div class="vr mx-1 my-1"></div>
                        <button type="button" onclick="insertVariable()" title="Add Variable">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            Variable
                        </button>
                        <div class="vr mx-1 my-1"></div>
                        <button type="button" id="emojiToggleBtn" title="Emoji">
                            😀
                        </button>
                    </div>

                    <!-- Emoji Picker -->
                    <div id="emojiPicker" class="emoji-picker-dropdown">
                        <div class="emoji-category-label">Smileys</div>
                        <div id="emojiSmileys"></div>
                        <div class="emoji-category-label">Gestures</div>
                        <div id="emojiGestures"></div>
                        <div class="emoji-category-label">Hearts</div>
                        <div id="emojiHearts"></div>
                        <div class="emoji-category-label">Objects</div>
                        <div id="emojiObjects"></div>
                        <div class="emoji-category-label">Symbols</div>
                        <div id="emojiSymbols"></div>
                    </div>

                    <textarea name="body" id="bodyEditor" class="form-control shadow-none border-light bg-light" rows="5" placeholder="Hi @{{1}}, welcome to our platform! 🎉" required maxlength="1024"></textarea>
                    <div class="char-counter"><span id="bodyCharCount">0</span> / 1024</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="form-section">
                <div class="form-section-title">Footer <span class="text-muted fw-normal" style="font-size:.82rem">(Optional)</span></div>
                <div class="form-section-desc">A small line of text at the bottom of the message.</div>
                <input type="text" name="footer" id="footerInput" class="form-control shadow-none border-light bg-light" placeholder="e.g. Reply STOP to unsubscribe" maxlength="60">
                <div class="char-counter"><span id="footerCharCount">0</span> / 60</div>
            </div>

            <!-- Buttons -->
            <div class="form-section">
                <div class="form-section-title">Buttons <span class="text-muted fw-normal" style="font-size:.82rem">(Optional)</span></div>
                <div class="form-section-desc">Add interactive buttons. You can add up to 3 buttons.</div>

                <div id="buttonsContainer"></div>

                <div class="dropdown" id="addBtnDropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" id="addButtonTrigger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add Button
                    </button>
                    <ul class="dropdown-menu add-button-menu">
                        <li><a class="dropdown-item" href="#" onclick="addButton('QUICK_REPLY'); return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path></svg>
                            Quick Reply
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="addButton('PHONE_NUMBER'); return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            Call Phone
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="addButton('URL'); return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            Visit Website
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item disabled" href="#" onclick="return false;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            WhatsApp Flow <span class="badge bg-secondary ms-1">Coming Soon</span>
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-3 mb-4">
                <a href="{{ route('admin.whatsapp.templates') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" id="submitTemplateBtn" class="btn wa-btn px-4 fw-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Submit for Approval
                </button>
            </div>
        </div>

        <!-- RIGHT: Live Preview -->
        <div class="col-lg-5">
            <div class="sticky-lg-top" style="top: 24px; z-index: 10;">
            <div class="phone-frame mx-auto">
                <div class="phone-screen">
                    <div class="phone-topbar">
                        <div class="phone-topbar-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div>
                            <div style="font-size:.8rem;line-height:1.1" id="previewName">Template Preview</div>
                            <div style="font-size:.65rem;opacity:.7;font-weight:400">online</div>
                        </div>
                    </div>
                    <div class="phone-chat">
                        <div class="wa-bubble" id="previewBubble">
                            <!-- Header Media -->
                            <div id="previewHeaderMedia" class="wa-bubble-header-media d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                            <!-- Header Text -->
                            <div id="previewHeaderText" class="wa-bubble-header-text d-none"></div>
                            <!-- Body -->
                            <div id="previewBody" class="wa-bubble-body">Your message will appear here…</div>
                            <!-- Footer -->
                            <div id="previewFooter" class="wa-bubble-footer d-none"></div>
                            <!-- Time -->
                            <div class="wa-bubble-time">
                                <span id="previewTime"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <!-- Buttons -->
                            <div id="previewButtons" class="wa-bubble-buttons d-none"></div>
                        </div>
                    </div>
            </div>
        </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Time ──
    function updateTime() {
        const now = new Date();
        document.getElementById('previewTime').textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    updateTime();
    setInterval(updateTime, 60000);

    // ── Header Type Toggle ──
    document.querySelectorAll('input[name="header_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const val = this.value;
            const textGroup = document.getElementById('headerTextGroup');
            const mediaGroup = document.getElementById('headerMediaGroup');
            const previewHeaderMedia = document.getElementById('previewHeaderMedia');
            const previewHeaderText = document.getElementById('previewHeaderText');

            textGroup.classList.add('d-none');
            mediaGroup.classList.add('d-none');
            previewHeaderMedia.classList.add('d-none');
            previewHeaderText.classList.add('d-none');

            if (val === 'text') {
                textGroup.classList.remove('d-none');
                updateHeaderTextPreview();
            } else if (val === 'image' || val === 'video' || val === 'document') {
                mediaGroup.classList.remove('d-none');
                previewHeaderMedia.classList.remove('d-none');

                // Update icon based on type
                const icons = {
                    image: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
                    video: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
                    document: '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>'
                };
                previewHeaderMedia.innerHTML = icons[val] || icons.image;
            }
        });
    });

    // ── Header Text Preview ──
    const headerInput = document.getElementById('headerContentInput');
    headerInput.addEventListener('input', updateHeaderTextPreview);

    function updateHeaderTextPreview() {
        const text = headerInput.value.trim();
        const previewHeaderText = document.getElementById('previewHeaderText');
        const counter = document.getElementById('headerCharCount');
        counter.textContent = headerInput.value.length;

        if (text) {
            previewHeaderText.textContent = text;
            previewHeaderText.classList.remove('d-none');
        } else {
            previewHeaderText.classList.add('d-none');
        }
    }

    // ── Body Preview ──
    const bodyEditor = document.getElementById('bodyEditor');
    bodyEditor.addEventListener('input', updateBodyPreview);

    function updateBodyPreview() {
        const text = bodyEditor.value;
        const counter = document.getElementById('bodyCharCount');
        counter.textContent = text.length;

        // Apply counter colors
        counter.parentElement.className = 'char-counter';
        if (text.length > 900) counter.parentElement.classList.add('danger');
        else if (text.length > 750) counter.parentElement.classList.add('warn');

        const previewBody = document.getElementById('previewBody');
        if (text.trim()) {
            // Convert WhatsApp formatting for preview
            let formatted = escapeHtml(text);
            // Bold: *text*
            formatted = formatted.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
            // Italic: _text_
            formatted = formatted.replace(/_(.*?)_/g, '<em>$1</em>');
            // Strikethrough: ~text~
            formatted = formatted.replace(/~(.*?)~/g, '<s>$1</s>');
            // Monospace: ```text```
            formatted = formatted.replace(/```(.*?)```/g, '<code>$1</code>');
            // Variables: @{{1}}
            formatted = formatted.replace(/\{\{(\d+)\}\}/g, '<span style="background:#e3f2fd;color:#1565c0;padding:0 4px;border-radius:4px;font-weight:600">@{{$1}}</span>');

            previewBody.innerHTML = formatted;
        } else {
            previewBody.textContent = 'Your message will appear here…';
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Footer Preview ──
    const footerInput = document.getElementById('footerInput');
    footerInput.addEventListener('input', function() {
        const text = this.value.trim();
        const previewFooter = document.getElementById('previewFooter');
        const counter = document.getElementById('footerCharCount');
        counter.textContent = this.value.length;

        if (text) {
            previewFooter.textContent = text;
            previewFooter.classList.remove('d-none');
        } else {
            previewFooter.classList.add('d-none');
        }
    });

    // ── Template Name → Preview ──
    document.getElementById('templateName').addEventListener('input', function() {
        const name = this.value.trim();
        document.getElementById('previewName').textContent = name || 'Template Preview';
    });

    // ── Formatting helpers ──
    window.wrapSelection = function(char) {
        const textarea = document.getElementById('bodyEditor');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        if (start === end) {
            // No selection — insert placeholder
            const placeholder = char === '```' ? 'code' : 'text';
            textarea.value = text.substring(0, start) + char + placeholder + char + text.substring(end);
            textarea.selectionStart = start + char.length;
            textarea.selectionEnd = start + char.length + placeholder.length;
        } else {
            const selected = text.substring(start, end);
            textarea.value = text.substring(0, start) + char + selected + char + text.substring(end);
            textarea.selectionStart = start;
            textarea.selectionEnd = end + char.length * 2;
        }

        textarea.focus();
        textarea.dispatchEvent(new Event('input'));
    };

    // ── Variable insertion ──
    window.insertVariable = function() {
        const textarea = document.getElementById('bodyEditor');
        const text = textarea.value;

        // Find the next variable number
        const matches = text.match(/\{\{(\d+)\}\}/g) || [];
        const nextNum = matches.length + 1;

        const pos = textarea.selectionStart;
        const ob = String.fromCharCode(123); // {
        const cb = String.fromCharCode(125); // }
        const varStr = ob + ob + nextNum + cb + cb;
        textarea.value = text.substring(0, pos) + varStr + text.substring(pos);
        textarea.selectionStart = textarea.selectionEnd = pos + varStr.length;
        textarea.focus();
        textarea.dispatchEvent(new Event('input'));
    };

    // ── Emoji Picker ──
    const emojis = {
        smileys: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','😊','😇','🥰','😍','🤩','😘','😗','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤧','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐'],
        gestures: ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏'],
        hearts: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','♥️'],
        objects: ['🎉','🎊','🎈','🎁','🎀','🏆','🥇','📱','💻','⌨️','📧','📩','📦','📋','📝','✏️','📌','📍','🔑','💰','💵','💳','📊','📈','🔔','🔗','⚡','🌟','⭐','✨','🔥','💡','💎'],
        symbols: ['✅','❌','⚠️','🔴','🟢','🔵','🟡','⬛','⬜','▶️','⏸','⏹','⏺','⏭','⏮','🔁','🔂','🔀','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️','↕️','↔️','♻️','✳️']
    };

    const containers = {
        smileys: document.getElementById('emojiSmileys'),
        gestures: document.getElementById('emojiGestures'),
        hearts: document.getElementById('emojiHearts'),
        objects: document.getElementById('emojiObjects'),
        symbols: document.getElementById('emojiSymbols')
    };

    Object.entries(emojis).forEach(([category, emojiList]) => {
        emojiList.forEach(emoji => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'emoji-btn';
            btn.textContent = emoji;
            btn.addEventListener('click', () => {
                const textarea = document.getElementById('bodyEditor');
                const pos = textarea.selectionStart;
                textarea.value = textarea.value.substring(0, pos) + emoji + textarea.value.substring(pos);
                textarea.selectionStart = textarea.selectionEnd = pos + emoji.length;
                textarea.focus();
                textarea.dispatchEvent(new Event('input'));
            });
            containers[category].appendChild(btn);
        });
    });

    // Toggle emoji picker
    const emojiToggle = document.getElementById('emojiToggleBtn');
    const emojiPicker = document.getElementById('emojiPicker');

    emojiToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        emojiPicker.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!emojiPicker.contains(e.target) && e.target !== emojiToggle) {
            emojiPicker.classList.remove('show');
        }
    });

    // ── Button Builder ──
    let buttons = [];
    const maxButtons = 3;

    window.addButton = function(type) {
        if (buttons.length >= maxButtons) {
            alert('Maximum 3 buttons allowed.');
            return;
        }

        const id = Date.now();
        const button = { id, type, text: '', phone_number: '', url: '' };
        buttons.push(button);
        renderButtons();
        updateButtonPreview();
    };

    window.removeButton = function(id) {
        buttons = buttons.filter(b => b.id !== id);
        renderButtons();
        updateButtonPreview();
    };

    function renderButtons() {
        const container = document.getElementById('buttonsContainer');
        container.innerHTML = '';

        // Show/hide add button
        document.getElementById('addBtnDropdown').style.display = buttons.length >= maxButtons ? 'none' : '';

        buttons.forEach((btn, index) => {
            const typeLabels = {
                'QUICK_REPLY': 'Quick Reply',
                'PHONE_NUMBER': 'Call Phone',
                'URL': 'Visit Website'
            };
            const typeIcons = {
                'QUICK_REPLY': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path></svg>',
                'PHONE_NUMBER': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
                'URL': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>'
            };

            let extraFields = '';
            if (btn.type === 'PHONE_NUMBER') {
                extraFields = `
                    <div class="col-12">
                        <label class="form-label small fw-medium">Phone Number</label>
                        <input type="text" class="form-control form-control-sm shadow-none border-light" placeholder="+1234567890" value="${escapeAttr(btn.phone_number)}" onchange="updateButtonData(${btn.id}, 'phone_number', this.value)">
                    </div>
                `;
            } else if (btn.type === 'URL') {
                extraFields = `
                    <div class="col-12">
                        <label class="form-label small fw-medium">Website URL</label>
                        <input type="url" class="form-control form-control-sm shadow-none border-light" placeholder="https://example.com" value="${escapeAttr(btn.url)}" onchange="updateButtonData(${btn.id}, 'url', this.value)">
                    </div>
                `;
            }

            const html = `
                <div class="button-item" data-btn-id="${btn.id}">
                    <button type="button" class="btn-remove" onclick="removeButton(${btn.id})" title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        ${typeIcons[btn.type]}
                        <span class="fw-semibold small">${typeLabels[btn.type]}</span>
                        <span class="badge bg-light text-muted border">#${index + 1}</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-medium">Button Label</label>
                            <input type="text" class="form-control form-control-sm shadow-none border-light" placeholder="Button text" value="${escapeAttr(btn.text)}" oninput="updateButtonData(${btn.id}, 'text', this.value)" maxlength="25">
                        </div>
                        ${extraFields}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    window.updateButtonData = function(id, field, value) {
        const btn = buttons.find(b => b.id === id);
        if (btn) {
            btn[field] = value;
            updateButtonPreview();
        }
    };

    function escapeAttr(str) {
        return (str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function updateButtonPreview() {
        const container = document.getElementById('previewButtons');

        if (buttons.length === 0) {
            container.classList.add('d-none');
            container.innerHTML = '';
            return;
        }

        container.classList.remove('d-none');
        container.innerHTML = '';

        const previewIcons = {
            'QUICK_REPLY': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 17 4 12 9 7"></polyline><path d="M20 18v-2a4 4 0 0 0-4-4H4"></path></svg>',
            'PHONE_NUMBER': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
            'URL': '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>'
        };

        buttons.forEach(btn => {
            const label = btn.text || 'Button';
            const div = document.createElement('div');
            div.className = 'wa-bubble-btn';
            div.innerHTML = (previewIcons[btn.type] || '') + ' ' + escapeHtml(label);
            container.appendChild(div);
        });
    }

    // ── Form Submit ──
    document.getElementById('submitTemplateBtn').addEventListener('click', function() {
        const form = document.getElementById('templateCreateForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Submitting...';
        btn.disabled = true;

        const alertBox = document.getElementById('formAlert');
        alertBox.classList.add('d-none');
        alertBox.classList.remove('alert-success', 'alert-danger');

        const formData = new FormData(form);

        // Add buttons data
        if (buttons.length > 0) {
            const buttonPayload = buttons.map(b => {
                const obj = { type: b.type, text: b.text };
                if (b.type === 'PHONE_NUMBER') obj.phone_number = b.phone_number;
                if (b.type === 'URL') obj.url = b.url;
                return obj;
            });
            formData.append('buttons', JSON.stringify(buttonPayload));
        }

        fetch('{{ route("admin.whatsapp.templates.store") }}', {
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
                alertBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' + data.message;
                window.scrollTo({top: 0, behavior: 'smooth'});
                setTimeout(() => window.location.href = '{{ route("admin.whatsapp.templates") }}', 1500);
            } else {
                throw new Error(data.error || 'Failed to submit template.');
            }
        })
        .catch(error => {
            alertBox.classList.remove('d-none');
            alertBox.classList.add('alert-danger');
            alertBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>' + error.message;
            window.scrollTo({top: 0, behavior: 'smooth'});
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });

});
</script>
@endpush
