@if(session('success'))
    <div class="admin-alert admin-alert-success">
        <svg class="admin-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div class="admin-alert-content">{{ session('success') }}</div>
        <button type="button" class="admin-alert-dismiss">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="admin-alert admin-alert-danger">
        <svg class="admin-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div class="admin-alert-content">{{ session('error') }}</div>
        <button type="button" class="admin-alert-dismiss">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="admin-alert admin-alert-danger">
        <svg class="admin-alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div class="admin-alert-content">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="admin-alert-dismiss">&times;</button>
    </div>
@endif
