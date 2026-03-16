@extends('layouts.app')
@section('SeoTags')
    @include('partials.seo', [
    'seo_title' => 'Global Explorer | Travel Categories & Guides',
    'seo_description' => 'Explore travel guides, destinations, itineraries, and tips. Discover inspiring travel stories and practical advice on Global Explorer.',
    'seo_keywords' => 'travel blogs, travel guides, destinations, itineraries, travel tips, budget travel, adventure travel, luxury travel',
    'og_image' => asset('images/category-og-image.jpg'), // Consider a specific category-themed image
])  
@endsection
@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
 
@endphp
<!-- Hero Section -->
<section class="hero-section" style="min-height: 35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Explore Travel Categories</h1>
            <p class="hero-subtitle">Find guides, destinations, itineraries, and tips for your next adventure</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row g-4">
          

        @forelse($categories as $index => $category)
    @php
        $defaultFallback = 'https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg?auto=compress&cs=tinysrgb&w=1200';

        $randomImage = null;

        if (!empty($category->image) && Storage::disk('public')->exists($category->image)) {
            $randomImage = asset('storage/' . ltrim($category->image, '/'));
        }

        if (!$randomImage) {
            $categoryFolder = 'categories/' . trim($category->name);
            $images = Storage::disk('public')->files($categoryFolder);
            $images = array_values(array_filter($images, function ($path) {
                return Str::endsWith(Str::lower($path), ['.jpg', '.jpeg', '.png', '.webp', '.gif']);
            }));

            if (count($images) > 0) {
                $randomImage = asset('storage/' . $images[array_rand($images)]);
            }
        }

        if (!$randomImage) {
            $randomImage = $defaultFallback;
        }
@endphp
            <div class="col-md-4 col-sm-6">
                <div class="category-card">
                 <img 
            src="{{ $randomImage }}" 
            alt="{{ $category->name }}" 
            class="img-fluid mb-3 rounded" loading="lazy"
        >
                    <h5 class="card-title mb-3">{{ $category->name }}</h5>
                    <p class="card-text mb-4">
                        {{ $category->description ?? 'Explore amazing blogs in this category' }}
                    </p>
                    <a href="{{ url('/travel/' . $category->slug) }}" class="btn btn-primary">
                        Explore Blogs →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <h3>No categories available</h3>
                    <p>Categories will appear here once they are added.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
 
@endsection
