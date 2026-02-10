@php
    use Illuminate\Support\Facades\Storage;
    use App\Helpers\ImageOptimizer;
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
            @php
                $optimizedCategoryImage = ImageOptimizer::optimize($randomImage, 480, 70);
            @endphp
            <img
                src="{{ $optimizedCategoryImage }}"
                alt="{{ $category->name }}"
                class="img-fluid mb-3 rounded"
                loading="lazy"
                decoding="async"
                width="480"
                height="320"
            >
            <h5 class="card-title mb-3">{{ $category->name }}</h5>
            <p class="card-text mb-4">
                {{ $category->description ?? 'Explore amazing blogs in this category' }}
            </p>
            <a href="{{ route('category.show', $category->slug ?? $category->id) }}" class="btn btn-primary">
                Explore Blogs →
            </a>
        </div>
    </div>
@endforeach
