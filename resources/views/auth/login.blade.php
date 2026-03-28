<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    @if (session('status'))<p>{{ session('status') }}</p>@endif
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button type="submit">Log in</button>
    </form>

    <p><a href="{{ route('password.request') }}">Forgot password?</a></p>
    <p><a href="{{ route('register') }}">Create account</a></p>
</body>
</html>
