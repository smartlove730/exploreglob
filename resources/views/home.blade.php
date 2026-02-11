@extends('layouts.app')
@section('SeoTags')
    @include('partials.seo', [
        'seo_title' => 'Explore Global Explorer - Your Gateway to Worldwide Stories',
        'seo_description' => 'Dive into a world of captivating blogs and articles from various countries and categories. Discover, read, and share stories that inspire and inform.',
        'seo_keywords' => 'global blogs, international stories, travel blogs, cultural articles, worldwide news, global explorer, country-specific blogs',
        'og_image' => asset('images/home-og-image.jpg'),
    ])  
@endsection
@section('content')
@php
    use Illuminate\Support\Facades\Storage;
 
@endphp
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Discover Amazing Stories</h1>
            <p class="hero-subtitle">Explore engaging content across multiple categories and countries. Your next favorite read is just a click away!</p>
            <a href="#latest-blogs" class="btn btn-primary btn-lg mt-3">
                Explore Now ↓
            </a>
        </div>
    </div>
</section>

<div class="container my-5" id="latest-blogs">
    <!-- Most Recent Blogs Section -->
    @if(isset($blogs) && $blogs->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">Most Recent Blogs</h2>
        <div class="row g-4">
            @foreach($blogs as $index => $blog)
                 
@php
    

    $categoryFolder = 'categories/' . $blog->category->name ;
    $images = Storage::disk('public')->files($categoryFolder);

    $randomImage = count($images) > 0
        ? asset('storage/' . $images[array_rand($images)])
        : asset('images/default-category.webp'); // fallback image
       
@endphp
                <div class="col-md-6 col-lg-4">
                    <div class="animated-card">
                       
                      <img src="{{ is_array(json_decode($blog->featured_image, true)) ? json_decode($blog->featured_image, true)[0] : $randomImage }}"
     class="card-img-top"
     alt="{{ $blog->title }}" loading="lazy" > <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="{{ route('blog.show', $blog->slug) }}">
                                    {{ $blog->title }}
                                </a>
                            </h5>
                            <p class="card-text flex-grow-1">
                                {{ Str::limit($blog->excerpt ?? '', 120) }}
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-3">
                                <small class="text-muted">
                                    📅 {{ \Carbon\Carbon::parse($blog->published_at)->format('M d, Y') }}
                                </small>
                                <a href="{{ route('blog.show', $blog->slug) }}" class="btn btn-primary btn-sm">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Categories Section -->
    @if(isset($categories) && count($categories) > 0)
    <section class="mt-5 pt-5">
        <h2 class="section-title">Browse Categories</h2>
        <div class="row g-4" id="category-grid">
            @include('partials.category-cards', ['categories' => $categories])
        </div>

        <div id="category-loader" class="category-loader d-none" aria-live="polite" aria-label="Loading more categories">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="category-scroll-trigger" class="category-scroll-trigger {{ $hasMoreCategories ? '' : 'd-none' }}"></div>
    </section>
    @endif

    <!-- No Content Message -->
    @if((!isset($blogs) || $blogs->count() == 0) && (!isset($categories) || count($categories) == 0))
        <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <h3 class="mb-3">No content available yet</h3>
            <p>Check back soon for new blog posts!</p>
        </div>
    @endif
</div>

@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('category-scroll-trigger');
    const loader = document.getElementById('category-loader');
    const categoryGrid = document.getElementById('category-grid');

    if (!trigger || !loader || !categoryGrid) {
        return;
    }

    let offset = categoryGrid.children.length;
    let hasMore = {{ $hasMoreCategories ? 'true' : 'false' }};
    let isLoading = false;

    const loadMoreCategories = async () => {
        if (!hasMore || isLoading) {
            return;
        }

        isLoading = true;
        loader.classList.remove('d-none');

        try {
            const response = await fetch(`{{ route('home.categories.load') }}?offset=${offset}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to load categories');
            }

            const data = await response.json();

            if (data.html) {
                const previousCount = categoryGrid.children.length;
                categoryGrid.insertAdjacentHTML('beforeend', data.html);

                const appendedCards = Array.from(categoryGrid.querySelectorAll('.category-card')).slice(previousCount);

                if (typeof gsap !== 'undefined' && appendedCards.length > 0) {
                    gsap.fromTo(appendedCards,
                        {
                            opacity: 0,
                            scale: 0.8,
                            rotation: -5
                        },
                        {
                            opacity: 1,
                            scale: 1,
                            rotation: 0,
                            duration: 0.6,
                            stagger: 0.08,
                            ease: 'back.out(1.7)'
                        }
                    );
                } else {
                    appendedCards.forEach((card) => {
                        card.style.opacity = '1';
                        card.style.transform = 'none';
                    });
                }
            }

            offset = data.nextOffset;
            hasMore = data.hasMore;

            if (!hasMore) {
                trigger.classList.add('d-none');
                observer.disconnect();
            }
        } catch (error) {
            console.error(error);
        } finally {
            loader.classList.add('d-none');
            isLoading = false;
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadMoreCategories();
            }
        });
    }, {
        rootMargin: '0px 0px 200px 0px',
    });

    if (hasMore) {
        observer.observe(trigger);
    }
});
</script>
<style>
    .category-loader {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 1.5rem;
    }

    .category-scroll-trigger {
        width: 100%;
        height: 1px;
    }
</style>
@endpush
