<!DOCTYPE html>
<html lang="en">
<head>
    @yield('SeoTags')
<meta name="google-adsense-account" content="ca-pub-3230339294601454">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="{{asset('images/postzy-favicon.png')}}">
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
  <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm" id="mainNavbar">
  <div class="container">
      <a class="navbar-brand fw-bold" href="{{ url('/') }}">
        <img src="{{ asset('images/postzy-logo.png') }}" alt="Postzy logo" loading="eager" decoding="async">
        Postzy
      </a>
 
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
   
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto d-none d-lg-flex">
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/') }}">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/explore') }}">Explore</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/travel') }}">Categories</a>
        </li>
        @foreach($travelNavCategories ?? [] as $navCategory)
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/travel/' . $navCategory->slug) }}">{{ $navCategory->name }}</a>
        </li>
        @endforeach
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/contact') }}">Contact</a>
        </li>
      </ul>

      <div class="header-search-wrapper me-3">
        <input
          type="search"
          id="header-search-input"
          class="form-control form-control-sm"
          placeholder="Search categories and blogs..."
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
    <div class="container position-relative" style="z-index:1">
        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{ url('/') }}" class="footer-brand text-decoration-none d-inline-flex align-items-center gap-2 mb-3">
                    <img src="{{ asset('images/postzy-logo.png') }}" alt="Postzy logo" class="footer-logo">
                    <span class="footer-brand-text">Postzy</span>
                </a>
                <p class="footer-muted mt-2" style="font-size:0.85rem;">Your premium destination for discovering stories, guides, and insights from around the world.</p>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Company</h5>
                <ul class="footer-links list-unstyled mb-0">
                    <li><a href="{{ url('/about') }}">About Us</a></li>
                    <li><a href="{{ url('/contact') }}">Contact</a></li>
                    <li><a href="{{ url('/policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ url('/terms') }}">Terms & Conditions</a></li>
                    <li><a href="{{ url('/data-deletion') }}">Data Deletion</a></li>
                </ul>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Top Categories</h5>
                <ul class="footer-links list-unstyled mb-0">
                    @forelse($topFooterCategories as $footerCategory)
                        <li>
                            <a href="{{ url('/travel/' . $footerCategory->slug) }}">
                                {{ $footerCategory->name }}
                            </a>
                        </li>
                    @empty
                        <li><span class="footer-muted">No categories available</span></li>
                    @endforelse
                </ul>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <h5 class="footer-title">Popular Posts</h5>
                <ul class="footer-links list-unstyled mb-0">
                    @forelse($topFooterBlogs as $footerBlog)
                        <li>
                            <a href="{{ url('/travel/' . ($footerBlog->category?->slug ?? 'travel') . '/' . $footerBlog->slug) }}">
                                {{ \Illuminate\Support\Str::limit($footerBlog->title, 40) }}
                            </a>
                        </li>
                    @empty
                        <li><span class="footer-muted">No blogs available</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <p class="footer-copy mb-0">&copy; {{ date('Y') }} Postzy. All rights reserved.</p>
    </div>
</footer>
    
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Navbar scroll effect
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });
    }

    // Collapse fix
    const navbarCollapse = document.getElementById('navbarNav');
    if (navbarCollapse && window.innerWidth >= 992) navbarCollapse.classList.remove('collapse');

    // Search functionality
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
        searchResults.innerHTML = `${renderSection('Categories', categories, 'category')}${renderSection('Blogs', blogs, 'blog')}`;
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
                const response = await fetch(`{{ url('/search') }}?q=${encodeURIComponent(keyword)}`, {
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
