<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private const RECENT_BLOG_LIMIT = 12;
    private const INITIAL_CATEGORY_LIMIT = 12;
    private const CATEGORY_LOAD_LIMIT = 6;
    private const SEARCH_RESULT_LIMIT = 6;

    public function index()
    {
        $country = session('country')?? 183;
        $minBlogs = (int) env('CATEGORY_MIN_BLOGS', 3);

        $blogs = Cache::remember("home:blogs:country:{$country}:travel", now()->addMinutes(10), function () use ($country) {
            return Blog::with('category')
                ->where('status', 1)
                ->when($country, fn($q) => $q->where('country_id', $country))
                ->inTravelSubcategories($country)
                ->latest()
                ->take(self::RECENT_BLOG_LIMIT)
                ->get();
        });

        $categoriesWithExtra = Cache::remember("home:categories:country:{$country}:min:{$minBlogs}:travel", now()->addMinutes(10), function () use ($country, $minBlogs) {
            return Category::travelSubcategories($country)
                ->where('status', 1)
                ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
                ->having('blogs_count', '>=', $minBlogs)
                ->orderBy('name')
                ->take(self::INITIAL_CATEGORY_LIMIT + 1)
                ->get();
        });

        $hasMoreCategories = $categoriesWithExtra->count() > self::INITIAL_CATEGORY_LIMIT;
        $categories = $categoriesWithExtra->take(self::INITIAL_CATEGORY_LIMIT)->values();
 
        return view('home', compact('blogs', 'categories', 'hasMoreCategories'));
    }

    public function loadCategories()
    {
        $country = session('country') ?? 183;
        $minBlogs = (int) env('CATEGORY_MIN_BLOGS', 3);
        $offset = max((int) request('offset', 0), 0);

        $categoriesWithExtra = Category::travelSubcategories($country)
            ->where('status', 1)
            ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
            ->skip($offset)
            ->take(self::CATEGORY_LOAD_LIMIT + 1)
            ->orderBy('name')
            ->having('blogs_count', '>=', $minBlogs)
            ->get();

        $hasMore = $categoriesWithExtra->count() > self::CATEGORY_LOAD_LIMIT;
        $categories = $categoriesWithExtra->take(self::CATEGORY_LOAD_LIMIT)->values();
        $nextOffset = $offset + $categories->count();

        return response()->json([
            'html' => view('partials.category-cards', compact('categories'))->render(),
            'nextOffset' => $nextOffset,
            'hasMore' => $hasMore,
        ]);
    }

    public function search()
    {
        $country = session('country') ?? 183;
        $keyword = trim((string) request('q', ''));

        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'categories' => [],
                'blogs' => [],
            ]);
        }

        $categories = Category::travelSubcategories($country)
            ->where('status', 1)
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get(['name', 'slug']);

        $blogs = Blog::query()
            ->where('status', 1)
            ->where('country_id', $country)
            ->inTravelSubcategories($country)
            ->where('title', 'like', "%{$keyword}%")
            ->latest('published_at')
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->with('category:id,slug')
            ->get()
            ->map(fn (Blog $blog) => [
                'title' => $blog->title,
                'slug' => $blog->slug,
                'category_slug' => $blog->category?->slug,
            ])
            ->values();

        return response()->json([
            'categories' => $categories,
            'blogs' => $blogs,
        ]);
    }
}
