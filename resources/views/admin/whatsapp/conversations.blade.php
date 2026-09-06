@extends('admin.layout')

@section('title', 'WhatsApp Inbox')

@section('content')
<style>
    .wa-container {
        height: calc(100vh - 130px);
        min-height: 600px;
        overflow: hidden;
    }
    .wa-sidebar {
        display: flex;
        flex-direction: column;
        height: 100%;
        border-right: 1px solid #e2e8f0;
        background: #fff;
    }
    .wa-search {
        padding: 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e2e8f0;
    }
    .wa-search input {
        border-radius: 20px;
        padding-left: 35px;
        background-color: #f0f2f5;
        border: none;
    }
    .wa-search-icon {
        position: absolute;
        left: 25px;
        top: 23px;
        color: #64748b;
        width: 16px;
        height: 16px;
    }
    .wa-tabs {
        display: flex;
        padding: 0 15px;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }
    .wa-tab {
        padding: 12px 15px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border-bottom: 2px solid transparent;
    }
    .wa-tab.active {
        color: #25D366;
        border-bottom-color: #25D366;
    }
    .wa-contact-list {
        flex: 1;
        overflow-y: auto;
    }
    .wa-contact {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    .wa-contact:hover {
        background: #f8f9fa;
    }
    .wa-contact.active {
        background: #f0f2f5;
    }
    .wa-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 1.1rem;
        flex-shrink: 0;
        position: relative;
    }
    .wa-status-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .wa-status-dot.online { background-color: #25D366; }
    .wa-status-dot.offline { background-color: #cbd5e1; }
    .wa-contact-info {
        margin-left: 15px;
        flex: 1;
        min-width: 0;
    }
    .wa-contact-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
    }
    .wa-contact-name {
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .wa-contact-time {
        font-size: 0.75rem;
        color: #64748b;
    }
    .wa-contact-preview-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .wa-contact-preview {
        font-size: 0.85rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 10px;
    }
    .wa-badge {
        background: #25D366;
        color: white;
        font-size: 0.7rem;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
    }
    
    .wa-chat-area {
        display: flex;
        flex-direction: column;
        height: 100%;
        background-color: #e5ddd5;
        position: relative;
    }
    .wa-chat-bg {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        opacity: 0.06;
        background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 20.5V18H0v-2h20v-2H0v-2h20v-2H0V8h20V6H0V4h20V2H0V0h22v20h2V0h2v20h2V0h2v20h2V0h2v20h2V0h2v20h2v2H20v-1.5zM0 20h2v20H0V20zm4 0h2v20H4V20zm4 0h2v20H8V20zm4 0h2v20h-2V20zm4 0h2v20h-2V20zm4 4h20v2H20v-2zm0 4h20v2H20v-2zm0 4h20v2H20v-2zm0 4h20v2H20v-2z' fill='%23000000' fill-opacity='1' fill-rule='evenodd'/%3E%3C/svg%3E");
        z-index: 0;
    }
    .wa-chat-header {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        background: #f0f2f5;
        border-bottom: 1px solid #e2e8f0;
        z-index: 1;
    }
    .wa-chat-header-info {
        margin-left: 15px;
        flex: 1;
    }
    .wa-chat-header-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 2px;
    }
    .wa-chat-header-status {
        font-size: 0.8rem;
        color: #64748b;
    }
    .wa-chat-actions button {
        background: none;
        border: none;
        color: #64748b;
        padding: 8px;
        cursor: pointer;
        border-radius: 50%;
        transition: background 0.2s;
    }
    .wa-chat-actions button:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .wa-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 1;
    }
    .wa-date-divider {
        text-align: center;
        margin: 15px 0;
    }
    .wa-date-divider span {
        background: #fff;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.75rem;
        color: #64748b;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .wa-msg {
        max-width: 75%;
        padding: 8px 12px;
        border-radius: 8px;
        position: relative;
        font-size: 0.9rem;
        line-height: 1.4;
        box-shadow: 0 1px 1px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }
    .wa-msg-in {
        align-self: flex-start;
        background: #fff;
        border-top-left-radius: 0;
    }
    .wa-msg-out {
        align-self: flex-end;
        background: #DCF8C6;
        border-top-right-radius: 0;
    }
    .wa-msg-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        margin-top: 4px;
    }
    .wa-msg-time {
        font-size: 0.65rem;
        color: #64748b;
    }
    .wa-msg-status svg {
        width: 14px;
        height: 14px;
    }
    .wa-msg-status.read svg { color: #53bdeb; }
    .wa-msg-status.delivered svg { color: #64748b; }
    
    /* Media message styles */
    .wa-msg-media img {
        max-width: 280px;
        max-height: 300px;
        border-radius: 6px;
        cursor: pointer;
        display: block;
    }
    .wa-msg-media video {
        max-width: 280px;
        max-height: 250px;
        border-radius: 6px;
        display: block;
    }
    .wa-msg-media audio {
        max-width: 260px;
        height: 36px;
    }
    .wa-msg-media .wa-doc-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: rgba(0,0,0,0.04);
        border-radius: 6px;
        text-decoration: none;
        color: #1e293b;
        min-width: 200px;
    }
    .wa-msg-media .wa-doc-link:hover {
        background: rgba(0,0,0,0.08);
    }
    .wa-msg-media .wa-doc-icon {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        background: #ef4444;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .wa-msg-media .wa-doc-icon.pdf { background: #ef4444; }
    .wa-msg-media .wa-doc-icon.doc { background: #3b82f6; }
    .wa-msg-media .wa-doc-icon.xls { background: #10b981; }
    .wa-msg-media .wa-doc-icon.ppt { background: #f59e0b; }
    .wa-msg-media .wa-doc-name {
        font-size: 0.85rem;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
    }
    .wa-msg-media .wa-doc-size {
        font-size: 0.7rem;
        color: #64748b;
    }
    .wa-msg-caption {
        margin-top: 6px;
        font-size: 0.9rem;
    }
    .wa-msg-sticker img {
        max-width: 150px;
        max-height: 150px;
    }

    /* Reaction badge on messages */
    .wa-msg-wrapper {
        position: relative;
        display: flex;
        flex-direction: column;
    }
    .wa-msg-wrapper.out { align-items: flex-end; }
    .wa-msg-wrapper.in { align-items: flex-start; }
    .wa-reaction-badge {
        position: absolute;
        bottom: -10px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1px 6px;
        font-size: 0.85rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        z-index: 2;
        line-height: 1.4;
    }
    .wa-msg-wrapper.in .wa-reaction-badge { left: 8px; }
    .wa-msg-wrapper.out .wa-reaction-badge { right: 8px; }

    /* Reaction picker (on hover/click) */
    .wa-reaction-picker {
        display: none;
        position: absolute;
        top: -40px;
        background: #fff;
        border-radius: 20px;
        padding: 4px 6px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        z-index: 10;
        white-space: nowrap;
    }
    .wa-reaction-picker::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 0;
        right: 0;
        height: 15px;
    }
    .wa-msg-wrapper.in .wa-reaction-picker { left: 0; }
    .wa-msg-wrapper.out .wa-reaction-picker { right: 0; }
    .wa-reaction-picker.show { display: flex; gap: 2px; }
    .wa-reaction-picker button {
        background: none;
        border: none;
        font-size: 1.2rem;
        padding: 3px 5px;
        cursor: pointer;
        border-radius: 50%;
        transition: background 0.15s, transform 0.15s;
        line-height: 1;
    }
    .wa-reaction-picker button:hover {
        background: #f0f2f5;
        transform: scale(1.25);
    }

    /* Emoji picker for chat input */
    .wa-emoji-picker {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 0;
        width: 320px;
        max-height: 280px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        margin-bottom: 8px;
        z-index: 100;
        overflow: hidden;
    }
    .wa-emoji-picker.show { display: block; }
    .wa-emoji-tabs {
        display: flex;
        border-bottom: 1px solid #e2e8f0;
        padding: 0 4px;
        background: #f8f9fa;
    }
    .wa-emoji-tab {
        padding: 8px 10px;
        font-size: 1.1rem;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        opacity: 0.6;
        transition: opacity 0.2s;
    }
    .wa-emoji-tab:hover, .wa-emoji-tab.active { opacity: 1; border-bottom-color: #25D366; }
    .wa-emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 2px;
        padding: 8px;
        max-height: 220px;
        overflow-y: auto;
    }
    .wa-emoji-grid button {
        background: none;
        border: none;
        font-size: 1.3rem;
        padding: 4px;
        cursor: pointer;
        border-radius: 6px;
        transition: background 0.15s;
        line-height: 1;
    }
    .wa-emoji-grid button:hover {
        background: #f0f2f5;
    }
    
    /* Image lightbox */
    .wa-lightbox {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .wa-lightbox.show { display: flex; }
    .wa-lightbox img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 8px;
        box-shadow: 0 4px 30px rgba(0,0,0,0.3);
    }

    .wa-input-area {
        display: flex;
        align-items: flex-end;
        padding: 12px 20px;
        background: #f0f2f5;
        gap: 10px;
        z-index: 1;
    }
    .wa-input-btn {
        background: none;
        border: none;
        color: #64748b;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
        flex-shrink: 0;
    }
    .wa-input-btn:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .wa-input-wrapper {
        flex: 1;
        background: #fff;
        border-radius: 20px;
        padding: 0 15px;
        display: flex;
        align-items: center;
    }
    .wa-input-wrapper textarea {
        width: 100%;
        border: none;
        padding: 12px 0;
        resize: none;
        max-height: 100px;
        font-size: 0.95rem;
        background: transparent;
        outline: none;
    }
    .wa-send-btn {
        background: #25D366;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
        flex-shrink: 0;
        box-shadow: 0 2px 5px rgba(37, 211, 102, 0.3);
    }
    .wa-send-btn:hover { background: #1ebc59; }
    .wa-send-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">WhatsApp Inbox</h1>
        <p class="text-muted mb-0">Manage customer conversations and support tickets in real-time.</p>
    </div>
</div>

<div class="card border-0 shadow-sm wa-container">
    <div class="row g-0 h-100">
        <!-- Sidebar -->
        <div class="col-md-4 wa-sidebar">
            <div class="wa-search position-relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="wa-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" class="form-control" placeholder="Search or start new chat">
            </div>
            
            <div class="wa-tabs">
                <div class="wa-tab active">All</div>
                <div class="wa-tab">Unread</div>
            </div>
            
            <div class="wa-contact-list" id="conversations-list">
                <!-- Dynamically populated via JS -->
                <div class="p-4 text-center text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="mt-2">Loading conversations...</div>
                </div>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="col-md-8 wa-chat-area" id="chat-area" style="display: none;">
            <div class="wa-chat-bg"></div>
            
            <div class="wa-chat-header">
                <div class="wa-avatar" id="active-chat-avatar" style="background: #3b82f6; width: 40px; height: 40px; font-size: 1rem;">?</div>
                <div class="wa-chat-header-info">
                    <div class="wa-chat-header-name" id="active-chat-name">Select a conversation</div>
                    <div class="wa-chat-header-status" id="active-chat-phone"></div>
                </div>
            </div>
            
            <div class="wa-messages" id="messages-list">
                <!-- Messages populated via JS -->
            </div>
            
            <div class="wa-input-area" style="flex-direction: column; align-items: stretch; position: relative;">
                <div id="wa-24h-warning" style="display: none; text-align: center; padding: 6px; font-size: 0.8rem; border-radius: 4px; margin-bottom: 8px;">
                    This window will close after 24 hours.
                </div>
                <!-- Emoji Picker Dropdown -->
                <div class="wa-emoji-picker" id="chat-emoji-picker">
                    <div class="wa-emoji-tabs">
                        <div class="wa-emoji-tab active" data-category="smileys" title="Smileys">😀</div>
                        <div class="wa-emoji-tab" data-category="gestures" title="Gestures">👋</div>
                        <div class="wa-emoji-tab" data-category="hearts" title="Hearts">❤️</div>
                        <div class="wa-emoji-tab" data-category="objects" title="Objects">🎉</div>
                        <div class="wa-emoji-tab" data-category="symbols" title="Symbols">✅</div>
                        <div class="wa-emoji-tab" data-category="animals" title="Animals">🐶</div>
                        <div class="wa-emoji-tab" data-category="food" title="Food">🍕</div>
                    </div>
                    <div class="wa-emoji-grid" id="chat-emoji-grid">
                        <!-- Populated via JS -->
                    </div>
                </div>
                <div class="d-flex align-items-end" style="gap: 10px; width: 100%;">
                    <button class="wa-input-btn" id="emoji-btn" title="Emoji">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                    </button>
                    <button class="wa-input-btn" id="attach-btn" title="Attach file">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                    </button>
                    <div class="wa-input-wrapper" style="flex: 1;">
                        <textarea id="message-input" placeholder="Type a message" rows="1"></textarea>
                    </div>
                    <button class="wa-send-btn" id="send-btn" title="Send message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <div class="col-md-8 wa-chat-area d-flex align-items-center justify-content-center" id="empty-state">
            <div class="text-center text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                <h4 class="mt-3">WhatsApp for Business</h4>
                <p>Select a conversation from the sidebar to start messaging.</p>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div class="wa-lightbox" id="wa-lightbox" onclick="this.classList.remove('show')">
    <img id="wa-lightbox-img" src="" alt="Preview">
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let activeConversationId = null;
        let pollingInterval = null;
        
        // Colors for avatars
        const colors = ['#3b82f6', '#ef4444', '#10b981', '#8b5cf6', '#f59e0b', '#06b6d4', '#ec4899'];
        
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }
        
        function getColorForName(name) {
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            return colors[Math.abs(hash) % colors.length];
        }
        
        function formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Load all conversations
        function loadConversations() {
            fetch("{{ route('admin.whatsapp.api.conversations.index') }}")
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('conversations-list');
                    
                    if (data.length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-muted">No conversations yet.</div>';
                        return;
                    }
                    
                    list.innerHTML = '';
                    data.forEach(conv => {
                        const contact = conv.contact;
                        const lastMsg = conv.messages[0];
                        const initials = getInitials(contact.name);
                        const color = getColorForName(contact.name);
                        const isActive = conv.id === activeConversationId ? 'active' : '';
                        
                        let msgPreview = lastMsg ? (lastMsg.type === 'text' ? lastMsg.content : '📷 Media') : 'Started a conversation';
                        if(msgPreview && msgPreview.length > 30) msgPreview = msgPreview.substring(0, 30) + '...';
                        
                        let statusIcon = '';
                        if (lastMsg && lastMsg.direction === 'outbound') {
                            const readColor = lastMsg.status === 'read' ? 'text-primary' : 'text-muted';
                            const checkMarks = lastMsg.status === 'sent' || lastMsg.status === 'pending' ? '✓' : '✓✓';
                            statusIcon = `<span class="${readColor}">${checkMarks}</span> `;
                        }

                        const time = lastMsg ? formatTime(lastMsg.created_at) : '';
                        
                        let unreadBadge = '';
                        let fontWeight = '';
                        let timeColor = '';
                        let previewColor = '';
                        
                        if (conv.unread_count > 0) {
                            unreadBadge = `<div class="wa-badge">${conv.unread_count}</div>`;
                            fontWeight = 'font-weight: 700;';
                            timeColor = 'color: #25D366; font-weight: 600;';
                            previewColor = 'color: #1e293b; font-weight: 600;';
                        }
                        
                        const div = document.createElement('div');
                        div.className = `wa-contact ${isActive}`;
                        div.dataset.id = conv.id;
                        div.innerHTML = `
                            <div class="wa-avatar" style="background: ${color};">${initials}</div>
                            <div class="wa-contact-info">
                                <div class="wa-contact-header">
                                    <div class="wa-contact-name" style="${fontWeight}">${contact.name}</div>
                                    <div class="wa-contact-time" style="${timeColor}">${time}</div>
                                </div>
                                <div class="wa-contact-preview-row">
                                    <div class="wa-contact-preview" style="${previewColor}">${statusIcon}${msgPreview}</div>
                                    ${unreadBadge}
                                </div>
                            </div>
                        `;
                        
                        div.addEventListener('click', () => openConversation(conv.id, contact.name, contact.phone_number, color, initials));
                        list.appendChild(div);
                    });
                })
                .catch(err => console.error("Error loading conversations", err));
        }
        
        function openConversation(id, name, phone, color, initials) {
            activeConversationId = id;
            
            // Update UI
            document.querySelectorAll('.wa-contact').forEach(el => el.classList.remove('active'));
            const activeEl = document.querySelector(`.wa-contact[data-id="${id}"]`);
            if (activeEl) activeEl.classList.add('active');
            
            document.getElementById('empty-state').style.display = 'none';
            document.getElementById('chat-area').style.display = 'flex';
            
            document.getElementById('active-chat-name').innerText = name;
            document.getElementById('active-chat-phone').innerText = phone;
            document.getElementById('active-chat-avatar').innerText = initials;
            document.getElementById('active-chat-avatar').style.background = color;
            
            loadMessages(id);
        }
        
        function loadMessages(id) {
            fetch(`{{ url('admin/whatsapp/api/conversations') }}/${id}/messages`)
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('messages-list');
                    
                    list.innerHTML = '';
                    
                    let html = '';
                    let currentDate = null;
                    
                    data.messages.forEach(msg => {
                        const date = new Date(msg.created_at).toLocaleDateString();
                        if (date !== currentDate) {
                            html += `<div class="wa-date-divider"><span>${date === new Date().toLocaleDateString() ? 'Today' : date}</span></div>`;
                            currentDate = date;
                        }
                        
                        const time = formatTime(msg.created_at);
                        const isOut = msg.direction === 'outbound';
                        
                        let statusIcon = '';
                        if (isOut) {
                            const readColor = msg.status === 'read' ? 'style="color: #53bdeb;"' : '';
                            let svgIcon = '';
                            if (msg.status === 'sent' || msg.status === 'pending') {
                                svgIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ${readColor}><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                            } else {
                                // delivered or read
                                svgIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ${readColor}><polyline points="18 6 7 17 2 12"></polyline><polyline points="22 6 11 17"></polyline></svg>`;
                            }
                            
                            statusIcon = `
                            <span class="wa-msg-status ${msg.status}">
                                ${svgIcon}
                            </span>`;
                        }
                        
                        // Build message content based on type
                        let msgContentHtml = '';
                        const msgType = msg.type || 'text';
                        const mediaTypes = ['image', 'video', 'audio', 'document', 'sticker'];
                        
                        if (mediaTypes.includes(msgType) && msg.media_url) {
                            if (msgType === 'image') {
                                msgContentHtml = `<div class="wa-msg-media"><img src="${msg.media_url}" alt="Image" onclick="document.getElementById('wa-lightbox-img').src=this.src; document.getElementById('wa-lightbox').classList.add('show');" loading="lazy"></div>`;
                            } else if (msgType === 'video') {
                                msgContentHtml = `<div class="wa-msg-media"><video controls preload="metadata"><source src="${msg.media_url}" type="${msg.media_mime_type || 'video/mp4'}">Video</video></div>`;
                            } else if (msgType === 'audio') {
                                msgContentHtml = `<div class="wa-msg-media"><audio controls preload="metadata"><source src="${msg.media_url}" type="${msg.media_mime_type || 'audio/ogg'}">Audio</audio></div>`;
                            } else if (msgType === 'document') {
                                const docName = msg.media_filename || 'Document';
                                const ext = docName.split('.').pop().toLowerCase();
                                let iconClass = 'pdf';
                                if (['doc','docx'].includes(ext)) iconClass = 'doc';
                                else if (['xls','xlsx'].includes(ext)) iconClass = 'xls';
                                else if (['ppt','pptx'].includes(ext)) iconClass = 'ppt';
                                msgContentHtml = `<div class="wa-msg-media"><a href="${msg.media_url}" target="_blank" download class="wa-doc-link"><div class="wa-doc-icon ${iconClass}">${ext.toUpperCase()}</div><div><div class="wa-doc-name">${docName}</div><div class="wa-doc-size">Download</div></div></a></div>`;
                            } else if (msgType === 'sticker') {
                                msgContentHtml = `<div class="wa-msg-sticker"><img src="${msg.media_url}" alt="Sticker" loading="lazy"></div>`;
                            }
                            // Add caption if present
                            if (msg.media_caption || msg.content) {
                                const caption = msg.media_caption || msg.content;
                                if (caption) {
                                    msgContentHtml += `<div class="wa-msg-caption">${escapeHtml(caption)}</div>`;
                                }
                            }
                        } else if (mediaTypes.includes(msgType) && !msg.media_url) {
                            // Media without downloaded URL - show a placeholder
                            const typeLabels = {image: '📷 Image', video: '🎬 Video', audio: '🎵 Audio', document: '📄 Document', sticker: '🏷️ Sticker'};
                            msgContentHtml = `<div style="padding: 8px; color: #64748b; font-style: italic;">${typeLabels[msgType] || '📎 Media'}</div>`;
                            if (msg.content && msg.content !== '{}') {
                                msgContentHtml += `<div>${escapeHtml(msg.content)}</div>`;
                            }
                        } else {
                            // Regular text message
                            msgContentHtml = `<div>${escapeHtml(msg.content || '')}</div>`;
                        }
                        
                        // Reaction badge
                        let reactionHtml = '';
                        if (msg.reaction_emoji) {
                            reactionHtml = `<div class="wa-reaction-badge">${msg.reaction_emoji}</div>`;
                        }
                        
                        // Reaction picker (quick react)
                        const quickReactions = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
                        let reactionPickerHtml = `<div class="wa-reaction-picker" data-msg-id="${msg.id}">`;
                        quickReactions.forEach(emoji => {
                            reactionPickerHtml += `<button onclick="sendReaction(${msg.id}, '${emoji}')">${emoji}</button>`;
                        });
                        reactionPickerHtml += '</div>';
                        
                        const wrapperDir = isOut ? 'out' : 'in';
                        const marginBottom = msg.reaction_emoji ? 'margin-bottom: 14px;' : '';
                        
                        html += `
                            <div class="wa-msg-wrapper ${wrapperDir}" style="${marginBottom}" onmouseenter="this.querySelector('.wa-reaction-picker').classList.add('show')" onmouseleave="this.querySelector('.wa-reaction-picker').classList.remove('show')">
                                ${reactionPickerHtml}
                                <div class="wa-msg ${isOut ? 'wa-msg-out' : 'wa-msg-in'}" data-msg-id="${msg.id}">
                                    ${msgContentHtml}
                                    <div class="wa-msg-meta">
                                        <span class="wa-msg-time">${time}</span>
                                        ${statusIcon}
                                    </div>
                                </div>
                                ${reactionHtml}
                            </div>
                        `;
                    });
                    
                    list.innerHTML = html;
                    list.scrollTop = list.scrollHeight;
                    
                    const messageInput = document.getElementById('message-input');
                    const sendBtn = document.getElementById('send-btn');
                    const attachBtn = document.getElementById('attach-btn');
                    const warningLabel = document.getElementById('wa-24h-warning');
                    
                    if (data.is_within_24_hours) {
                        messageInput.disabled = false;
                        sendBtn.disabled = false;
                        attachBtn.disabled = false;
                        messageInput.placeholder = 'Type a message';
                        warningLabel.innerText = 'This window will close after 24 hours.';
                        warningLabel.style.display = 'block';
                        warningLabel.style.backgroundColor = '#d4edda';
                        warningLabel.style.color = '#155724';
                    } else {
                        messageInput.disabled = true;
                        sendBtn.disabled = true;
                        attachBtn.disabled = true;
                        messageInput.placeholder = 'Messaging disabled (24h window closed)';
                        warningLabel.innerText = 'The 24-hour window has closed. You cannot send free-form messages to this user.';
                        warningLabel.style.display = 'block';
                        warningLabel.style.backgroundColor = '#fff3cd';
                        warningLabel.style.color = '#856404';
                    }
                });
        }
        
        // Handle sending
        const sendBtn = document.getElementById('send-btn');
        const messageInput = document.getElementById('message-input');
        
        function sendMessage() {
            const text = messageInput.value.trim();
            if (!text || !activeConversationId) return;
            
            messageInput.value = '';
            messageInput.focus();
            
            // Add optimistic UI message
            const list = document.getElementById('messages-list');
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            list.innerHTML += `
                <div class="wa-msg wa-msg-out" style="opacity: 0.7;">
                    <div>${text}</div>
                    <div class="wa-msg-meta">
                        <span class="wa-msg-time">${time}</span>
                        <span class="wa-msg-status">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </span>
                    </div>
                </div>
            `;
            list.scrollTop = list.scrollHeight;
            
            // Post to server
            fetch(`{{ url('admin/whatsapp/api/conversations') }}/${activeConversationId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok || data.success === false) {
                    throw new Error(data.error || "Failed to send message");
                }
                return data;
            })
            .then(data => {
                // If Reverb is working, we don't strictly need to reload here because the broadcast will do it.
                // But it's safer to reload in case it fails.
                loadMessages(activeConversationId);
                loadConversations();
            })
            .catch(err => {
                console.error("Failed to send", err);
                alert(err.message || "Failed to send message");
                loadMessages(activeConversationId); // Reload to remove optimistic UI
            });
        }
        
        sendBtn.addEventListener('click', sendMessage);
        
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Helper: escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Send reaction to a message
        window.sendReaction = function(messageId, emoji) {
            if (!activeConversationId) return;
            
            fetch(`{{ url('admin/whatsapp/api/conversations') }}/${activeConversationId}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message_id: messageId, emoji: emoji })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadMessages(activeConversationId);
                } else {
                    console.error('Failed to send reaction:', data.error);
                }
            })
            .catch(err => console.error('Failed to send reaction', err));
        };
        
        // ── Emoji Picker for Chat Input ──
        const chatEmojiData = {
            smileys: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','😊','😇','🥰','😍','🤩','😘','😗','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤧','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐'],
            gestures: ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','💪','🦾','🤳','👆'],
            hearts: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','♥️','😍','🥰','😘','💑','💏'],
            objects: ['🎉','🎊','🎈','🎁','🎀','🏆','🥇','📱','💻','⌨️','📧','📩','📦','📋','📝','✏️','📌','📍','🔑','💰','💵','💳','📊','📈','🔔','🔗','⚡','🌟','⭐','✨','🔥','💡','💎','🎯','🎵','🎶','🎤','🎧','📷','🎬'],
            symbols: ['✅','❌','⚠️','🔴','🟢','🔵','🟡','⬛','⬜','▶️','⏸','⏹','⏺','➡️','⬅️','⬆️','⬇️','↗️','↘️','♻️','✳️','❇️','🔰','⭕','✖️','➕','➖','➗','💲','💱','©️','®️','™️'],
            animals: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🐢','🐍','🦎','🦀','🐙','🦑','🐠','🐟','🐡','🐬','🦈','🐳','🐊'],
            food: ['🍕','🍔','🍟','🌭','🍿','🧂','🥓','🥚','🍳','🧇','🥞','🥐','🍞','🥖','🧀','🥗','🥙','🥪','🌮','🌯','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🍤','🍩','🍰','🎂','🧁','🍫','🍬','🍭','🍮','🍯','🍪','☕','🍵','🥤','🍺','🍷','🥂','🍹']
        };
        
        const emojiGrid = document.getElementById('chat-emoji-grid');
        const emojiPicker = document.getElementById('chat-emoji-picker');
        const emojiBtn = document.getElementById('emoji-btn');
        
        function renderEmojiCategory(category) {
            emojiGrid.innerHTML = '';
            const emojis = chatEmojiData[category] || [];
            emojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = emoji;
                btn.addEventListener('click', () => {
                    const textarea = document.getElementById('message-input');
                    const pos = textarea.selectionStart;
                    textarea.value = textarea.value.substring(0, pos) + emoji + textarea.value.substring(pos);
                    textarea.selectionStart = textarea.selectionEnd = pos + emoji.length;
                    textarea.focus();
                });
                emojiGrid.appendChild(btn);
            });
        }
        
        // Initialize with smileys
        renderEmojiCategory('smileys');
        
        // Tab switching
        document.querySelectorAll('.wa-emoji-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.wa-emoji-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                renderEmojiCategory(tab.dataset.category);
            });
        });
        
        // Toggle emoji picker
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            emojiPicker.classList.toggle('show');
        });
        
        document.addEventListener('click', (e) => {
            if (!emojiPicker.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
                emojiPicker.classList.remove('show');
            }
        });
        
        // Initial load
        loadConversations();
        
        // Initialize Real-time WebSockets via Laravel Echo (Reverb)
        setTimeout(() => {
            if (window.Echo) {
                const userId = {{ auth()->id() ?? 1 }};
                window.Echo.private(`whatsapp.user.${userId}`)
                    .listen('WhatsappMessageReceived', (e) => {
                        console.log('New message received via WebSocket:', e);
                        
                        // If the active conversation is the one receiving the message, refresh messages
                        if (activeConversationId == e.whatsapp_conversation_id) {
                            loadMessages(activeConversationId);
                        }
                        
                        // Always reload the sidebar to update unread counts and latest messages
                        loadConversations();
                    });
            } else {
                console.warn("Laravel Echo is not initialized. WebSockets will not work.");
            }
        }, 1000); // Small delay to ensure Echo is bound to window
    });
</script>
@endpush
