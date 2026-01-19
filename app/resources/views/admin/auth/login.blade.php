@extends('admin.layout')

@section('title','Admin Login')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6">
    <h2>Admin Login</h2>

    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <button class="btn btn-primary">Login</button>
      </div>
    </form>
  </div>
</div>
@endsection
