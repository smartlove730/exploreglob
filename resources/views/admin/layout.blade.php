<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control Panel - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { background-color: #f8fafc; }
        .sidebar-link.active { background: rgba(255,255,255,.15); }
        .admin-sidebar-body {
            height: 100dvh;
            max-height: 100dvh;
            overflow-y: auto;
        }
        .admin-sidebar-nav {
            min-height: 0;
        }
    </style>
</head>
<body>
@php
    $isAdmin = auth()->user()?->isAdmin();
    $facebookSettingsUrl = Route::has('admin.facebook.settings') ? route('admin.facebook.settings') : url('/admin/facebook/settings');
    $facebookPostsUrl = Route::has('admin.posts.index') ? route('admin.posts.index') : url('/admin/posts');
    $facebookManagePostsUrl = Route::has('admin.facebook.manage-posts.index') ? route('admin.facebook.manage-posts.index') : url('/admin/facebook/manage-posts');
    $facebookAppsUrl = Route::has('admin.facebook.apps.index') ? route('admin.facebook.apps.index') : url('/admin/facebook/apps');
    $googleDriveKeysUrl = Route::has('admin.facebook.google-drive-keys.index') ? route('admin.facebook.google-drive-keys.index') : url('/admin/facebook/google-drive-keys');
    $googleDriveFoldersUrl = Route::has('admin.facebook.drive-folders.index') ? route('admin.facebook.drive-folders.index') : url('/admin/facebook/drive-folders');
    $isFailedAutomationRoute = request()->routeIs('admin.automations.failed-posts.*');
    $isAutomationRoute = request()->routeIs('admin.automations.*') && !$isFailedAutomationRoute;
@endphp
@if(auth()->check())
<div class="d-flex">
    <nav class="navbar navbar-dark bg-dark d-lg-none w-100 position-fixed top-0 z-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="offcanvas-lg offcanvas-start text-bg-dark" tabindex="-1" id="adminSidebar" style="width: 260px;">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title">{{ $isAdmin ? 'Admin Panel' : 'User Panel' }}</h5>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body admin-sidebar-body d-flex flex-column p-3">
            <ul class="nav nav-pills flex-column gap-2 flex-grow-1 admin-sidebar-nav">
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                @if($isAdmin)
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}" href="{{ route('admin.blogs.index') }}">Blogs</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">Categories</a></li>
                @endif
                @if($isAdmin)
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.apps.*') ? 'active' : '' }}" href="{{ $facebookAppsUrl }}">Facebook Apps</a></li>
                @endif
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.google-drive-keys.*') ? 'active' : '' }}" href="{{ $googleDriveKeysUrl }}">Connect Google Accounts</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.drive-folders.*') ? 'active' : '' }}" href="{{ $googleDriveFoldersUrl }}">Google Drive Folders</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.settings') ? 'active' : '' }}" href="{{ $facebookSettingsUrl }}">Facebook Settings</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ $facebookPostsUrl }}">Social Posts</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.manage-posts.*') ? 'active' : '' }}" href="{{ $facebookManagePostsUrl }}">Manage Social Posts</a></li>
                <li><a class="nav-link text-white sidebar-link {{ $isAutomationRoute ? 'active' : '' }}" href="{{ route('admin.automations.index') }}">Automations</a></li>
                <li><a class="nav-link text-white sidebar-link {{ $isFailedAutomationRoute ? 'active' : '' }}" href="{{ route('admin.automations.failed-posts.index') }}">Failed Automation Posts</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('app.billing.*') ? 'active' : '' }}" href="{{ route('app.billing.plans') }}">Subscription</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('app.settings.*') ? 'active' : '' }}" href="{{ route('app.settings.index') }}">Settings</a></li>
                @if($isAdmin)
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.saas.*') ? 'active' : '' }}" href="{{ route('admin.saas.overview') }}">SaaS Management</a></li>
                @endif
            </ul>

            <form method="POST" action="{{ route('admin.logout') }}" class="mt-auto pt-3 border-top border-secondary">
                @csrf
                <button class="btn btn-outline-light btn-sm w-100">Logout</button>
            </form>
        </div>
    </div>

    <main class="flex-grow-1 p-3 p-lg-4" style="margin-top: 56px;">
        @include('admin.partials.alerts')
        @yield('content')
    </main>
</div>
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
