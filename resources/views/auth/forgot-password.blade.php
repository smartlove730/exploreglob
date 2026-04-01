<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Forgot Password</title>
</head>
<body>
    <h1>Forgot password</h1>

    @if (session('status'))<p>{{ session('status') }}</p>@endif
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
        <button type="submit">Email reset link</button>
    </form>

    <p><a href="{{ route('login') }}">Back to login</a></p>
</body>
</html>
