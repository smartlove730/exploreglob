<!DOCTYPE html>
<html lang="en">
<head>
    @yield('SeoTags')
<meta name="google-adsense-account" content="ca-pub-3230339294601454">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="{{asset('e.avif')}}">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

    <!-- Custom CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
<style>

.navbar-toggler {
    border: 1px solid rgba(0,0,0,.2);
}

.navbar-toggler-icon {
    background-image: var(--bs-navbar-toggler-icon-bg);
}

.header-search-wrapper {
    position: relative;
    max-width: 420px;
    width: 100%;
}

.header-search-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    z-index: 1051;
    max-height: 320px;
    overflow-y: auto;
}

.header-search-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6c757d;
    padding: 0.5rem 0.75rem 0.35rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.header-search-item {
    display: block;
    padding: 0.45rem 0.75rem;
    color: #212529;
    text-decoration: none;
    border-top: 1px solid #f1f3f5;
}

.header-search-item:hover {
    background-color: #f8f9fa;
}

.header-search-empty {
    padding: 0.25rem 0.75rem 0.75rem;
    color: #6c757d;
    font-size: 0.85rem;
}

@media (max-width: 991.98px) {
    .header-search-wrapper {
        margin-top: 0.75rem;
        max-width: none;
    }
}

</style>

 <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-REDTM9GQ3P"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-REDTM9GQ3P');
</script>
    
</head>
<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
  <div class="container">
      <img src="{{ asset('e.avif') }}" alt="Global Explorer logo" style="height:20px!important;" loading="eager" decoding="async">
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
          <a class="nav-link" href="{{ route('travel.index') }}">Travel</a>
        </li>
        @foreach($travelNavCategories ?? [] as $navCategory)
        <li class="nav-item">
          <a class="nav-link" href="{{ route('travel.category', $navCategory->slug) }}">{{ $navCategory->name }}</a>
        </li>
        @endforeach
        <li class="nav-item">
          <a class="nav-link" href="{{ route('contact') }}">Contact</a>
        </li>
      </ul>

      <div class="header-search-wrapper me-3">
        <input
          type="search"
          id="header-search-input"
          class="form-control form-control-sm"
          placeholder="Search travel categories and blogs..."
          autocomplete="off"
        >
        <div id="header-search-results" class="header-search-dropdown d-none"></div>
      </div>
    </div>
     <select class="form-select form-select-sm" style="width:200px" onchange="if(this.value){window.location=this.value}">
        <option value="">🌍 Select Country</option>
        @foreach($countries as $country)
        @if($country->code == 'US')
          <option value="{{ url('country/'.$country->code) }}" @selected(($country->id ?? null) == (session('country') ?? 183))>
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

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{ route('home') }}" class="footer-brand text-decoration-none d-inline-flex align-items-center gap-2 mb-3">
                    <img src="{{ asset('e.avif') }}" alt="Global Explorer logo" class="footer-logo"> 
                </a>
                
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Company</h5>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="{{ route('about') }}" class="text-decoration-none text-white">About Us</a></li>
                    <li><a href="{{ route('contact') }}" class="text-decoration-none text-white">Contact</a></li>
                    <li><a href="{{ route('policy') }}" class="text-decoration-none text-white">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-decoration-none text-white">Terms & Conditions</a></li>
                </ul>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Top Travel Categories</h5>
                <ul class="footer-links list-unstyled mb-0">
                    @forelse($topFooterCategories as $footerCategory)
                        <li>
                            <a href="{{ route('travel.category', $footerCategory->slug) }}" class="text-decoration-none text-white">
                                {{ $footerCategory->name }}
                            </a>
                        </li>
                    @empty
                        <li><span class="footer-muted">No categories available</span></li>
                    @endforelse
                </ul>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Top Travel Blogs</h5>
                <ul class="footer-links list-unstyled mb-0">
                    @forelse($topFooterBlogs as $footerBlog)
                        <li>
                            <a href="{{ url('/travel/' . ($footerBlog->category?->slug ?? 'travel') . '/' . $footerBlog->slug) }}" class="text-decoration-none text-white">
                                {{ \Illuminate\Support\Str::limit($footerBlog->title, 45) }}
                            </a>
                        </li>
                    @empty
                        <li><span class="footer-muted">No blogs available</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <p class="footer-copy mb-0">© {{ date('Y') }} Global Explorer. All rights reserved.</p>
    </div>
</footer>
    
<script>
document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('navbarNav');
    if (navbar) navbar.classList.remove('collapse');

    const searchInput = document.getElementById('header-search-input');
    const searchResults = document.getElementById('header-search-results');
    let controller = null;
    let debounce = null;

    if (!searchInput || !searchResults) return;

    const hideSearchResults = () => {
        searchResults.classList.add('d-none');
        searchResults.innerHTML = '';
    };

    const escapeHtml = (value = '') => {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    const renderSection = (label, items, type) => {
        let html = `<div class="header-search-label">${escapeHtml(label)}</div>`;
        if (!items.length) return `${html}<div class="header-search-empty">No results found</div>`;

        items.forEach((item) => {
            const url = type === 'category'
                ? `{{ url('/travel') }}/${item.slug}`
                : `{{ url('/travel') }}/${item.category_slug || 'travel'}/${item.slug}`;
            const title = type === 'category' ? item.name : item.title;
            html += `<a class="header-search-item" href="${url}">${escapeHtml(title)}</a>`;
        });

        return html;
    };

    const renderResults = (data) => {
        const categories = Array.isArray(data.categories) ? data.categories : [];
        const blogs = Array.isArray(data.blogs) ? data.blogs : [];
        searchResults.innerHTML = `${renderSection('Travel Categories', categories, 'category')}${renderSection('Blogs', blogs, 'blog')}`;
        searchResults.classList.remove('d-none');
    };

    searchInput.addEventListener('input', () => {
        const keyword = searchInput.value.trim();
        window.clearTimeout(debounce);

        if (controller) controller.abort();

        if (keyword.length < 2) {
            hideSearchResults();
            return;
        }

        debounce = window.setTimeout(async () => {
            controller = new AbortController();

            try {
                const response = await fetch(`{{ route('home.search') }}?q=${encodeURIComponent(keyword)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: controller.signal,
                });

                if (!response.ok) throw new Error('Request failed');

                renderResults(await response.json());
            } catch (error) {
                if (error.name !== 'AbortError') hideSearchResults();
            }
        }, 200);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.header-search-wrapper')) hideSearchResults();
    });
});
</script>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3230339294601454" crossorigin="anonymous"></script>
<!-- Bootstrap 5 JS -->
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- GSAP Library -->
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

<!-- Custom Animations -->
@vite(['resources/js/animations.js'])
@stack('scripts')

</body>
</html>
