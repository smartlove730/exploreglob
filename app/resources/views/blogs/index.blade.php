@extends('layouts.app')

@section('content')

<!-- Hero Section -->
<section class="hero-section" style="min-height: 40vh;">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">All Blogs</h1>
            <p class="hero-subtitle">Discover our complete collection of articles and stories</p>
        </div>
    </div>
</section>

<div class="container my-5">
  <div class="row g-4">
    @forelse($blogs as $index => $blog)
      <div class="col-md-6 col-lg-4">
        <div class="animated-card">
          @php
            $imageUrl = $blog->featured_image ?? null;
            if (empty($imageUrl)) {
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
              {{ Str::limit($blog->excerpt ?? strip_tags($blog->content), 150) }}
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
    @empty
      <div class="col-12">
        <div class="empty-state">
          <div class="empty-state-icon">📝</div>
          <h3>No blogs found</h3>
          <p>Check back later for new content!</p>
        </div>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  @if($blogs->hasPages())
  <div class="d-flex justify-content-center mt-5">
    {{ $blogs->links() }}
  </div>
  @endif
</div>

@endsection

