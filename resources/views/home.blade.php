@extends('layouts.app')

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
    <!-- Latest Blogs Section -->
    @if(isset($blogs) && $blogs->count() > 0)
    <section class="mb-5">
        <h2 class="section-title">Latest Blogs</h2>
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
                        
                      <img src="{{  $randomImage }}"
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
        <div class="row g-4">
           
            @foreach($categories as $index => $category)
            
@php
    

    $categoryFolder = 'categories/' . $category['name'] ;
    $images = Storage::disk('public')->files($categoryFolder);

    $randomImage = count($images) > 0
        ? asset('storage/' . $images[array_rand($images)])
        : asset('images/default-category.webp'); // fallback image
@endphp
                <div class="col-md-4 col-sm-6">
                    <div class="category-card">
                       <img 
            src="{{ $randomImage }}" 
            alt="{{ $category['name'] }}" 
            class="img-fluid mb-3 rounded" loading="lazy"
        >
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
