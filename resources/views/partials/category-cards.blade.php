@php
    use Illuminate\Support\Facades\Storage;
@endphp

@foreach($categories as $category)
    @php
        $categoryFolder = 'categories/' . trim($category->name);
        $images = Storage::disk('public')->files($categoryFolder);

        $randomImage = count($images) > 0
            ? asset('storage/' . $images[array_rand($images)])
            : asset('images/default-category.webp');
    @endphp
    <div class="col-md-4 col-sm-6">
        <div class="category-card">
            <img
                src="{{ $randomImage }}"
                alt="{{ $category->name }}"
                class="img-fluid mb-3 rounded"
                loading="lazy"
            >
            <h5 class="card-title mb-3">{{ $category->name }}</h5>
            <p class="card-text mb-4">
                {{ $category->description ?? 'Explore amazing blogs in this category' }}
            </p>
            <a href="{{ route('travel.category', $category->slug ?? $category->id) }}" class="btn btn-primary">
                Explore Blogs →
            </a>
        </div>
    </div>
@endforeach
