<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control Panel - @yield('title', 'Dashboard')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/css/admin-panel.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-body">
@php
    $isAdmin = auth()->user()?->isAdmin();
    $facebookSettingsUrl = Route::has('admin.facebook.settings') ? route('admin.facebook.settings') : url('/admin/facebook/settings');
    $facebookPostsUrl = Route::has('admin.posts.index') ? route('admin.posts.index') : url('/admin/posts');
    $facebookManagePostsUrl = Route::has('admin.facebook.manage-posts.index') ? route('admin.facebook.manage-posts.index') : url('/admin/facebook/manage-posts');
    $facebookAppsUrl = Route::has('admin.facebook.apps.index') ? route('admin.facebook.apps.index') : url('/admin/facebook/apps');
    $googleDriveKeysUrl = Route::has('admin.facebook.google-drive-keys.index') ? route('admin.facebook.google-drive-keys.index') : url('/admin/facebook/google-drive-keys');
    $googleDriveFoldersUrl = Route::has('admin.facebook.drive-folders.index') ? route('admin.facebook.drive-folders.index') : url('/admin/facebook/drive-folders');
    $isAutomationRoute = request()->routeIs('admin.automations.*');
@endphp

@if(auth()->check())

<!-- Mobile Top Navbar -->
<div class="admin-mobile-nav">
    <a href="{{ route('admin.dashboard') }}" class="admin-mobile-brand">
        <div class="admin-sidebar-brand-icon" style="width:32px;height:32px;border-radius:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <span class="admin-mobile-brand-text">Postzy</span>
    </a>
    <button class="admin-mobile-toggle" onclick="toggleAdminSidebar()" aria-label="Toggle sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
</div>

<!-- Sidebar Overlay -->
<div class="admin-sidebar-overlay" id="adminSidebarOverlay" onclick="toggleAdminSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Brand -->
    <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-brand">
        <div class="admin-sidebar-brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <span class="admin-sidebar-brand-text">Postzy</span>
        <span class="admin-sidebar-brand-badge">{{ $isAdmin ? 'Admin' : 'User' }}</span>
    </a>

    <!-- User Info -->
    <div class="admin-sidebar-user">
        <div class="admin-sidebar-user-info">
            <div class="admin-sidebar-avatar">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
            <div>
                <div class="admin-sidebar-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                <div class="admin-sidebar-user-role">{{ $isAdmin ? 'Administrator' : 'Member' }}</div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="admin-sidebar-nav">
        @if($isAdmin)
        <!-- Overview -->
        <div class="admin-nav-section">Overview</div>
        <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>

        <!-- Content -->
        <div class="admin-nav-section">Content</div>
        <a class="admin-nav-link {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}" href="{{ route('admin.blogs.index') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Blogs
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Categories
        </a>
        @endif

        @if($isAdmin)
        <!-- Social & Publishing -->
        <div class="admin-nav-section">Social & Publishing</div>
        <a class="admin-nav-link {{ request()->routeIs('admin.facebook.apps.*') ? 'active' : '' }}" href="{{ $facebookAppsUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-2a4 4 0 0 0-4 4v8"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
            Facebook Apps
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.facebook.google-drive-keys.*') ? 'active' : '' }}" href="{{ $googleDriveKeysUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            Google Accounts
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.facebook.drive-folders.*') ? 'active' : '' }}" href="{{ $googleDriveFoldersUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            Drive Folders
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.facebook.settings') ? 'active' : '' }}" href="{{ $facebookSettingsUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Facebook Settings
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ $facebookPostsUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Social Posts
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.facebook.manage-posts.*') ? 'active' : '' }}" href="{{ $facebookManagePostsUrl }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Manage Posts
        </a>

        <!-- Automation -->
        <div class="admin-nav-section">Automation</div>
        <a class="admin-nav-link {{ $isAutomationRoute ? 'active' : '' }}" href="{{ route('admin.automations.index') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6"/><path d="M1 12h6m6 0h6"/><path d="M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24"/><path d="M19.78 4.22l-4.24 4.24m-7.08 7.08l-4.24 4.24"/></svg>
            Automations
        </a>
        @endif

        <!-- WhatsApp Business -->
        @if(auth()->check())
        <div class="admin-nav-section">WhatsApp Business</div>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp') || request()->is('admin/whatsapp/dashboard') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/dashboard') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Overview
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/phone-numbers*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/phone-numbers') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            Phone Numbers
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/templates*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/templates') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layout"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            Templates
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/campaigns*') ? 'active' : '' }}" href="{{ route('admin.whatsapp.campaigns.index') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            Campaigns
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/reports*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/reports') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Template Reports
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/contacts*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/contacts') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Contacts
        </a>
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/conversations*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/conversations') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Conversations
        </a>
        @if($isAdmin)
        <a class="admin-nav-link {{ request()->is('admin/whatsapp/settings*') ? 'active' : '' }}" href="{{ url('/admin/whatsapp/settings') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            API Settings
        </a>
        @endif
        @endif

        @if($isAdmin)
        <!-- Account -->
        <div class="admin-nav-section">Account</div>
        <a class="admin-nav-link {{ request()->routeIs('app.billing.*') ? 'active' : '' }}" href="{{ route('app.billing.plans') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Subscription
        </a>
        <a class="admin-nav-link {{ request()->routeIs('app.settings.*') ? 'active' : '' }}" href="{{ route('app.settings.index') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Settings
        </a>
        @endif

        @if($isAdmin)
        <!-- System -->
        <div class="admin-nav-section">System</div>
        <a class="admin-nav-link {{ request()->routeIs('admin.mail-settings.*') ? 'active' : '' }}" href="{{ route('admin.mail-settings.index') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Mail Settings
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.saas.*') ? 'active' : '' }}" href="{{ route('admin.saas.overview') }}">
            <svg class="admin-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            SaaS Management
        </a>
        @endif
    </nav>

    <!-- Logout -->
    <div class="admin-sidebar-footer">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="admin-logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign out
            </button>
        </form>
    </div>
</aside>

<!-- Main Content -->
<main class="admin-main">
    @include('admin.partials.alerts')
    @yield('content')
</main>

@else
<main class="container py-5">
    @include('admin.partials.alerts')
    @yield('content')
</main>
@endif

<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">Loading...</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
// Sidebar Toggle
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminSidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', () => {
    // Preserve sidebar scroll position
    const sidebarNav = document.querySelector('.admin-sidebar-nav');
    if (sidebarNav) {
        const scrollPos = localStorage.getItem('adminSidebarScroll');
        if (scrollPos) {
            sidebarNav.scrollTop = parseInt(scrollPos, 10);
        }
        sidebarNav.addEventListener('scroll', () => {
            localStorage.setItem('adminSidebarScroll', sidebarNav.scrollTop);
        }, { passive: true });
    }

    document.querySelectorAll('.admin-alert-dismiss').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('.admin-alert');
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-12px)';
            setTimeout(() => alert.remove(), 300);
        });
    });

    // Close sidebar on nav link click (mobile)
    if (window.innerWidth < 992) {
        document.querySelectorAll('.admin-nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.getElementById('adminSidebarOverlay');
                if (sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.jQuery?.fn?.DataTable) return;

    window.jQuery('table.data-table').each(function () {
        const table = window.jQuery(this);
        if (window.jQuery.fn.DataTable.isDataTable(this)) return;
        const firstBodyRow = table.find('tbody tr:first');
        if (firstBodyRow.length && firstBodyRow.children('td,th').length === 1 && firstBodyRow.children('[colspan]').length) {
            return;
        }

        const noExport = String(table.data('no-export') || '')
            .split(',')
            .map(value => Number(value.trim()))
            .filter(Number.isInteger);
        const order = table.data('order') || [[0, 'desc']];

        table.DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order,
            responsive: true,
            dom: "<'row g-2 align-items-center mb-2'<'col-md-6'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row g-2 align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
            buttons: [
                { extend: 'copy', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'csv', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'excel', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
                { extend: 'print', className: 'btn btn-sm btn-outline-secondary', exportOptions: { columns: ':visible:not(.no-export)' } },
            ],
            columnDefs: noExport.length ? [{ targets: noExport, orderable: false, searchable: false, className: 'no-export' }] : [],
            language: {
                search: '',
                searchPlaceholder: 'Search table...',
            },
        });
    });
});
</script>
<script>
document.addEventListener('click', async (e) => {
    const trigger = e.target.closest('[data-modal-url]');
    if (!trigger) return;

    e.preventDefault();

    try {
        const response = await fetch(trigger.dataset.modalUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Unable to load modal.');
        }

        const html = await response.text();
        const modalNode = document.getElementById('adminModal');
        modalNode.querySelector('.modal-content').innerHTML = html;

        const modal = new bootstrap.Modal(modalNode);
        modal.show();

        const fileInput = modalNode.querySelector('input[type=file][data-upload-url]');
        if (!fileInput) return;

        fileInput.addEventListener('change', async function () {
            const file = this.files?.[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            const uploadResponse = await fetch(this.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                body: formData,
            });

            const result = await uploadResponse.json();
            if (result.url) {
                const imageUrl = modalNode.querySelector('input[name="featured_image"]');
                if (imageUrl) imageUrl.value = result.url;
            }
        });
    } catch (error) {
        alert(error.message || 'Unexpected error while opening modal.');
    }
});
</script>
@stack('scripts')
</body>
</html>
