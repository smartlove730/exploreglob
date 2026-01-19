@extends('layouts.app')

@section('content')

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
    <!-- Latest Blogs Section -->
    @if(isset($blogs) && $blogs->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">Latest Blogs</h2>
        <div class="row g-4">
            @foreach($blogs as $index => $blog)
                <div class="col-md-6 col-lg-4">
                    <div class="animated-card">
                        @php
                            // Use featured image or generate SVG placeholder
                            $imageUrl = $blog->featured_image ?? null;
                            if (empty($imageUrl)) {
                                // Generate inline SVG placeholder - no external request
                                $text = urlencode(substr($blog->title, 0, 20));
                                $imageUrl = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='600'%3E%3Crect fill='%236366f1' width='100%25' height='100%25'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='Arial,sans-serif' font-size='32' font-weight='600'%3E" . $text . "%3C/text%3E%3C/svg%3E";
                            }
                        @endphp
                        <img src="{{ $imageUrl }}" 
                             class="card-img-top" 
                             alt="{{ $blog->title }}"
                             onerror="if(!this.classList.contains('image-placeholder')){const w=this.width||800;const h=this.height||600;const t=this.alt||'Image';const s=`%3Csvg xmlns='http://www.w3.org/2000/svg' width='${w}' height='${h}'%3E%3Crect fill='%236366f1' width='100%25' height='100%25'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='Arial,sans-serif' font-size='${Math.min(w,h)/15}' font-weight='600'%3E${t}%3C/text%3E%3C/svg%3E`;this.src='data:image/svg+xml,'+s;this.classList.add('image-placeholder');}">
                        <div class="card-body d-flex flex-column">
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
        <div class="row g-4">
            @php
                $categoryIcons = ['🎨', '💻', '📱', '🚀', '🎮', '📚', '🎬', '🎵', '🏃', '🍔', '✈️', '🏠'];
            @endphp
            @foreach($categories as $index => $category)
                <div class="col-md-4 col-sm-6">
                    <div class="category-card">
                        <span class="category-icon">{{ $categoryIcons[$index % count($categoryIcons)] }}</span>
                        <h5 class="card-title mb-3">{{ $category['name'] }}</h5>
                        <p class="card-text mb-4">
                            {{ $category['description'] ?? 'Explore amazing blogs in this category' }}
                        </p>
                        <a href="{{ route('category.show', $category['slug'] ?? $category['id']) }}" class="btn btn-primary">
                            Explore Blogs →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
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
