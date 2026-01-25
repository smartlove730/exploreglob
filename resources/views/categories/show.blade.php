@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
 
@endphp
<!-- Hero Section -->
<section class="hero-section" style="min-height: 35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">{{ $category->name }}</h1>
            @if($category->description)
                <p class="hero-subtitle">{{ $category->description }}</p>
            @endif
        </div>
    </div>
</section>

<div class="container my-5">
    <!-- Blogs Grid -->
          

    @if($blogs->count() > 0)
        <div class="row g-4">
            @foreach($blogs as $index => $blog)
            @php
    

    $categoryFolder = 'categories/' . $category->name ;
    $images = Storage::disk('public')->files($categoryFolder);

    $randomImage = count($images) > 0
        ? asset('storage/' . $images[array_rand($images)])
        : asset('images/default-category.webp'); // fallback image
@endphp
                <div class="col-md-6 col-lg-4">
                    <div class="animated-card"> 
                                   <img 
            src="{{ is_array(json_decode($blog->featured_image, true)) ? json_decode($blog->featured_image, true)[0] : $randomImage }}" 
            alt="{{ $category->name }}" 
            class="card-img-top"  loading="lazy"
        >
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="{{ route('blog.show', $blog->slug) }}">
                                    {{ $blog->title }}
                                </a>
                            </h5>
                            <p class="card-text flex-grow-1">
                                {{ Str::limit($blog->excerpt ?? '', 150) }}
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

        <!-- Pagination -->
        @if($blogs->hasPages())
        <div class="d-flex justify-content-center mt-5">
            {{ $blogs->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📂</div>
            <h3>No blogs found in this category yet</h3>
            <p>Check back soon for new content!</p>
        </div>
    @endif
</div>
@endsection

