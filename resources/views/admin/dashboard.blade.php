@extends('admin.layout')

@section('title','Dashboard')

@section('content')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <div class="row">
    <div class="col-md-8">
      <h1>Dashboard</h1>
      <p>Welcome to the admin panel.</p>
    </div>
  </div>
  <button id="enablePush">Enable Notifications</button>


@endsection
