@extends('layouts.app')

@section('content')

<!-- Hero Section -->
<section class="hero-section" style="min-height: 35vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Browse Categories</h1>
            <p class="hero-subtitle">Explore content by category and discover what interests you</p>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row g-4">
        @php
            $categoryIcons = ['🎨', '💻', '📱', '🚀', '🎮', '📚', '🎬', '🎵', '🏃', '🍔', '✈️', '🏠', '🎯', '🔬', '💡'];
        @endphp
        @forelse($categories as $index => $category)
            <div class="col-md-4 col-sm-6">
                <div class="category-card">
                    <span class="category-icon">{{ $categoryIcons[$index % count($categoryIcons)] }}</span>
                    <h5 class="card-title mb-3">{{ $category->name }}</h5>
                    <p class="card-text mb-4">
                        {{ $category->description ?? 'Explore amazing blogs in this category' }}
                    </p>
                    <a href="{{ route('category.show', $category->slug) }}" class="btn btn-primary">
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
