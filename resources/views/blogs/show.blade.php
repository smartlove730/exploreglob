@extends('layouts.app')

@php
 
    use Illuminate\Support\Facades\Storage;
    // Decode the JSON content from DB
    $content = json_decode($blog->content ?? '{}', true);

    $subtitle = $content['subtitle'] ?? '';
    $author = $content['author'] ?? 'Unknown Author';
    $publishedDate = \Carbon\Carbon::parse($content['published_date'] ?? $blog->published_at)->format('F j, Y');
    
    $readTime = $content['read_time'] ?? '5';
    $coverImage = $blog->featured_image ?? $content['cover_image'] ;
   
    $sections = $content['sections'] ?? [];
    $hashtags = $content['hashtags'] ?? [];
    $relatedBlogs = $content['related_blogs'] ?? [];
    
    // Use SVG placeholder if no cover image
    if (empty($coverImage)) {
        $text = urlencode(substr($blog->title, 0, 30));
        $coverImage = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1200' height='600'%3E%3Crect fill='%236366f1' width='100%25' height='100%25'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='Arial,sans-serif' font-size='48' font-weight='700'%3E" . $text . "%3C/text%3E%3C/svg%3E";
    }
    
@endphp

 @php
    

    $categoryFolder = 'categories/' . $blog->category->name ;
    $images = Storage::disk('public')->files($categoryFolder);

    $randomImage = count($images) > 0
        ? asset('storage/' . $images[array_rand($images)])
        : asset('images/default-category.webp'); // fallback image

         $randomImage1= count($images) > 0
        ? asset('storage/' . $images[array_rand($images)])
        : asset('images/default-category.webp'); // fallback image
@endphp


@section('SeoTags')
@include('partials.seo', [
    'seo_title'       => $blog->seo_title,
    'seo_description' => $blog->seo_description,
    'seo_keywords'    => $blog->seo_keywords,
    'og_image'        => is_array(json_decode($blog->featured_image, true)) 
                            ? json_decode($blog->featured_image, true)[0] 
                            : $randomImage,
]) 
@endsection
@section('content')
    
    
<!-- Blog Header Section -->
<section class="blog-header-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto text-center">
                <h1 class="blog-title-main">{{ $blog->title }}</h1>
                @if($subtitle)
                    <p class="lead mb-4" style="opacity: 0.95;">{{ $subtitle }}</p>
                @endif
                <div class="d-flex justify-content-center align-items-center gap-4 flex-wrap mb-4">
                    <span class="d-flex align-items-center gap-2">
                        <strong>👤 {{ $author }}</strong>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <strong>📅 {{ $publishedDate }}</strong>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <strong>⏱️ {{ $readTime }} min read</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cover Image -->

@if($randomImage)
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <img src="{{ is_array(json_decode($blog->featured_image, true)) ? json_decode($blog->featured_image, true)[0] : $randomImage }}" 
                 alt="{{ $blog->title }}" 
                 class="blog-cover-image" loading="lazy"
                 >
        </div>
    </div>
</div>
@endif

<!-- Blog Content Section -->
<section class="blog-content-section">
    @if(!empty($sections))
        @foreach($sections as $index => $section)
            <div class="blog-section">
                @if(!empty($section['heading']))
                    <h3>{{ $section['heading'] }}</h3>
                @endif
                @if(!empty($section['content']))
                    @php
                        $sectionContent = $section['content'];
                        $sectionContent = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $sectionContent);
                        $sectionContent = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $sectionContent);
                        $sectionContent = nl2br($sectionContent);
                    @endphp
                    <div>{!! $sectionContent !!}</div>
                @endif
            </div>
        @endforeach
    @else
        <div class="blog-section">
            <p class="text-muted">Content coming soon...</p>
        </div>
    @endif

    <!-- Hashtags -->
    @if(!empty($hashtags))
        <div class="hashtags-container">
            @foreach($hashtags as $tag)
                <span class="hashtag">#{{ $tag }}</span>
            @endforeach
        </div>
    @endif

    <!-- Social Share -->
    <div class="social-share">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <strong class="me-2">Share this article:</strong>
            <a href="https://twitter.com/intent/tweet?text={{ urlencode($blog->title) }}&url={{ urlencode(request()->fullUrl()) }}" 
               target="_blank" 
               class="social-btn twitter">
                🐦 Twitter
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}" 
               target="_blank" 
               class="social-btn facebook">
                📘 Facebook
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->fullUrl()) }}" 
               target="_blank" 
               class="social-btn linkedin">
                💼 LinkedIn
            </a>
        </div>
    </div>
</section>

<!-- Related Blogs -->
@if(!empty($related))
<section class="related-blogs-section">
    <div class="container">
        <h2 class="section-title text-center mb-5">Related Articles</h2>
        <div class="row g-4">
            @foreach($related as $index => $related)
                <div class="col-md-4">
                    <div class="related-blog-card">
                    @php
    $images = json_decode($blog->featured_image, true);
    // Check if it decoded correctly and is a non-empty array
    $src = (is_array($images) && !empty($images)) 
            ? $images[array_rand($images)] 
            : $randomImage;
@endphp

 
                        <img src="{{ $src }}" 
                             class="card-img-top" 
                             alt="{{ $related['title'] ?? 'Related' }}"
                             style="height: 220px; object-fit: cover;" loading="lazy"
                             onerror="if(!this.classList.contains('image-placeholder')){const w=this.width||400;const h=this.height||300;const t=this.alt||'Image';const s=`%3Csvg xmlns='http://www.w3.org/2000/svg' width='${w}' height='${h}'%3E%3Crect fill='%236366f1' width='100%25' height='100%25'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='%23ffffff' font-family='Arial,sans-serif' font-size='${Math.min(w,h)/15}' font-weight='600'%3E${t}%3C/text%3E%3C/svg%3E`;this.src='data:image/svg+xml,'+s;this.classList.add('image-placeholder');}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $related['title'] }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($related['excerpt'] ?? '', 100) }}</p>
                            <a href="{{ url('/blog/' . ($related['slug'] ?? '#')) }}" class="btn btn-primary btn-sm">
                                Read More →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Newsletter Section -->
<section class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="p-5 rounded-4" style="background: var(--gradient-1); color: white; text-align: center;">
                <h2 class="mb-3">📬 Join Our Newsletter</h2>
                <p class="mb-4">Get weekly articles, tips, and updates straight to your inbox</p>
                <form class="row g-3 justify-content-center" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
                    <div class="col-md-5">
                        <input type="text" class="form-control form-control-lg" placeholder="Your name" required>
                    </div>
                    <div class="col-md-5">
                        <input type="email" class="form-control form-control-lg" placeholder="Your email" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-light btn-lg w-100" type="submit">
                            Subscribe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
