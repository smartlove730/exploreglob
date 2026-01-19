<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { padding-top: 70px; }</style>
</head>
<body>
<div class="d-flex">
  <nav class="bg-dark text-white vh-100 p-3" style="width:250px; position:fixed;">
    <a class="d-block mb-3 text-white text-decoration-none fs-4" href="{{ route('admin.dashboard') }}">Admin</a>
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link text-white" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="{{ route('admin.blogs.index') }}">Blogs</a></li>
      <li class="nav-item"><a class="nav-link text-white" href="{{ route('admin.categories.index') }}">Categories</a></li>
    </ul>

    <div class="mt-4">
      @auth
        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-outline-light btn-sm">Logout</button></form>
      @endauth
    </div>
  </nav>

  <main style="margin-left:250px; padding:30px; width:100%;">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @yield('content')
  </main>
</div>

<!-- Modal container for dynamic forms -->
<div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">Loading...</div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// helper: load modal content via AJAX and initialize editors/uploaders
document.addEventListener('click', function(e){
  const btn = e.target.closest('[data-modal-url]');
  if(!btn) return;
  e.preventDefault();
  const url = btn.getAttribute('data-modal-url');
  fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
    .then(r => r.text())
    .then(html => {
      const modalEl = document.querySelector('#adminModal .modal-content');
      modalEl.innerHTML = html;
      const modalElNode = document.getElementById('adminModal');
      const modal = new bootstrap.Modal(modalElNode);
      modal.show();

 

      // wire up file upload inputs inside modal
      const fileInput = modalEl.querySelector('input[type=file][data-upload-url]');
      if (fileInput) {
          fileInput.addEventListener('change', function() {
              const uploadUrl = this.getAttribute('data-upload-url');
              const f = this.files[0];
              if (!f) return;
              const fd = new FormData();
              fd.append('file', f);
              // CSRF token
              const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '{{ csrf_token() }}';

              fetch(uploadUrl, {
                  method: 'POST',
                  headers: { 'X-CSRF-TOKEN': token },
                  body: fd
              }).then(r => r.json()).then(json => {
                  if (json.url) {
                      const urlInput = modalEl.querySelector('input[name=featured_image]');
                      if (urlInput) urlInput.value = json.url;
                  }
              }).catch(err => console.error(err));
          });
      }
    });
});

 
</script>

</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
if ('serviceWorker' in navigator && 'PushManager' in window) {
  navigator.serviceWorker.register('/sw.js').then(registration => {

    document.getElementById('enablePush').addEventListener('click', async () => {

      const permission = await Notification.requestPermission();
      if (permission !== 'granted') return;

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey:
          "{{ config('webpush.vapid.public_key') }}"
      });

      // 🔴 THIS is what Laravel expects
      await fetch('/push/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(subscription)
      });
    });

  });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
