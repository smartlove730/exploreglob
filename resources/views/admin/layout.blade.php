<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { background-color: #f8fafc; }
        .sidebar-link.active { background: rgba(255,255,255,.15); }
    </style>
</head>
<body>
@php
    $facebookSettingsUrl = Route::has('admin.facebook.settings') ? route('admin.facebook.settings') : url('/admin/facebook/settings');
    $facebookPostsUrl = Route::has('admin.posts.index') ? route('admin.posts.index') : url('/admin/posts');
    $facebookAppsUrl = Route::has('admin.facebook.apps.index') ? route('admin.facebook.apps.index') : url('/admin/facebook/apps');
@endphp
@if(auth()->check())
<div class="d-flex">
    <nav class="navbar navbar-dark bg-dark d-lg-none w-100 position-fixed top-0 z-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="offcanvas-lg offcanvas-start text-bg-dark" tabindex="-1" id="adminSidebar" style="width: 260px;">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title">Admin Panel</h5>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3 vh-100">
            <ul class="nav nav-pills flex-column gap-2">
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}" href="{{ route('admin.blogs.index') }}">Blogs</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">Categories</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.apps.*') ? 'active' : '' }}" href="{{ $facebookAppsUrl }}">Facebook Apps</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.facebook.settings') ? 'active' : '' }}" href="{{ $facebookSettingsUrl }}">Facebook Settings</a></li>
                <li><a class="nav-link text-white sidebar-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ $facebookPostsUrl }}">Social Posts</a></li>
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
