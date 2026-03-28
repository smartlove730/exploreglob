<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Email</title>
</head>
<body>
    <h1>Verify your email</h1>

    @if (session('status') === 'verification-link-sent')
        <p>A new verification link has been sent to your email address.</p>
    @endif

    <p>Please verify your email before continuing.</p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
