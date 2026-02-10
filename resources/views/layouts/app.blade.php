<!DOCTYPE html>
<html lang="en">
<head>
    @yield('SeoTags')

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="{{asset('e.avif')}}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
<style>

.navbar-toggler {
    border: 1px solid rgba(0,0,0,.2);
}

.navbar-toggler-icon {
    background-image: var(--bs-navbar-toggler-icon-bg);
}

</style>

    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-N4CGBWESRQ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-N4CGBWESRQ');
</script>
    
</head>
<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
  <div class="container">
      <img src="{{asset('e.avif')}}" style="height:20px!important;">
    <a class="navbar-brand fw-bold" href="{{ route('home') }}">  Global Explorer</a>
 
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
   
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto  d-none d-lg-flex">
        <li class="nav-item">
          <a class="nav-link" href="{{ route('home') }}">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('categories.index') }}">Categories</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('contact') }}">Contact</a>
        </li>
      </ul>

     
    </div>
     <select class="form-select form-select-sm" style="width:200px"
        onchange="window.location=this.value">
        <option value="">🌍 Select Country</option>
        @foreach(\App\Models\Country::all() as $country)
        @if($country->code == 'US')
          <option value="{{ url('country/'.$country->code) }}" selected>
            {{ $country->name }}
          </option>
          @endif
        @endforeach
      </select>
  </div>
</nav>
</header>

<main style="padding-top: 80px;">
    @yield('content')
</main>

<footer>
    <div class="container">
        <p class="mb-2">© {{ date('Y') }} Global Explorer. All rights reserved.</p>
        <div class="d-flex flex-wrap gap-3 justify-content-center ">
            <a href="{{ route('about') }}" class="text-decoration-none">About Us</a>
            <a href="{{ route('policy') }}" class="text-decoration-none">Privacy Policy</a>
            <a href="{{ route('terms') }}" class="text-decoration-none">Terms & Conditions</a>
            <a href="{{ route('contact') }}" class="text-decoration-none">Contact</a>
        </div>
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"
        ></script>
        <script>
$(document).ready(function () {

    const $navbar = $('#navbarNav');
    $navbar.removeClass('collapse');
     
 

});
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- GSAP Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

<!-- Custom Animations -->
<script src="{{ asset('/js/animations.js') }}"></script>
@stack('scripts')

</body>
</html>
