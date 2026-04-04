<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Explore Glob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(120deg, #eef2ff, #f8fafc); }
        .auth-card { max-width: 520px; border: 0; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card auth-card w-100">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-1">Create your account</h1>
            <p class="text-muted mb-4">Start publishing and manage plans in one place.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div>
                    <label class="form-label">Confirm password</label>
                    <input type="password" class="form-control" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create account</button>
            </form>

            <p class="small mt-3 mb-0">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
        </div>
    </div>
</body>
</html>
