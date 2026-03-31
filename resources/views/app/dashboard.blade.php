<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>App Dashboard</title>
</head>
<body>
    <h1>Customer App Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}.</p>
    <p>Role: {{ auth()->user()->role ?? (auth()->user()->is_admin ? 'admin' : 'customer') }}</p>

    @if (auth()->user()->isAdmin())
        <p><a href="{{ route('admin.dashboard') }}">Go to Admin Dashboard</a></p>
    @endif
    <p><a href="{{ route('app.calendar.index') }}">Open Content Calendar</a></p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
