<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;

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

        $blogs = Blog::with('category')
            ->where('status', 1)
            ->when($country, fn($q) => $q->where('country_id', $country))
            ->latest()
            ->take(self::RECENT_BLOG_LIMIT)
            ->get();

        $categoriesQuery = Category::where('status', 1)
            ->where('country_id', $country)
            ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
            ->having('blogs_count', '>=', $minBlogs);

        $categories = (clone $categoriesQuery)
            ->take(self::INITIAL_CATEGORY_LIMIT)
            ->get();

        $hasMoreCategories = $categoriesQuery->count() > self::INITIAL_CATEGORY_LIMIT;
 
        return view('home', compact('blogs', 'categories', 'hasMoreCategories'));
    }

    public function loadCategories()
    {
        $country = session('country') ?? 183;
        $minBlogs = (int) env('CATEGORY_MIN_BLOGS', 3);
        $offset = max((int) request('offset', 0), 0);

        $categoriesQuery = Category::where('status', 1)
            ->where('country_id', $country)
            ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
            ->having('blogs_count', '>=', $minBlogs);

        $categories = (clone $categoriesQuery)
            ->skip($offset)
            ->take(self::CATEGORY_LOAD_LIMIT)
            ->get();

        $totalCategories = $categoriesQuery->count();
        $nextOffset = $offset + $categories->count();

        return response()->json([
            'html' => view('partials.category-cards', compact('categories'))->render(),
            'nextOffset' => $nextOffset,
            'hasMore' => $nextOffset < $totalCategories,
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

        $categories = Category::query()
            ->where('status', 1)
            ->where('country_id', $country)
            ->where('name', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get(['name', 'slug']);

        $blogs = Blog::query()
            ->where('status', 1)
            ->where('country_id', $country)
            ->where('title', 'like', "%{$keyword}%")
            ->latest('published_at')
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get(['title', 'slug']);

        return response()->json([
            'categories' => $categories,
            'blogs' => $blogs,
        ]);
    }
}
