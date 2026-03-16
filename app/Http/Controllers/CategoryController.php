<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Blog;
class CategoryController extends Controller
{
    public function index()
    {
        $country = session('country');
        $minBlogs = (int) env('CATEGORY_MIN_BLOGS', 3);
        $countryId = $country ?? 183;
        $travelRoot = Category::travelRoot($countryId);

        $categories = Category::travelSubcategories($countryId)
            ->where('status', 1)
            ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
            ->having('blogs_count', '>=', $minBlogs)
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories', 'travelRoot'));
    }

    public function show($slug)
    {
        $countryId = session('country') ?? 183;
        $category = Category::travelSubcategories($countryId)
            ->where('status', 1)
            ->where('slug', $slug)
            ->firstOrFail();

        $blogs = Blog::where('category_id', $category->id)
            ->where('status', 1)
            ->latest('published_at')
            ->paginate(10);

        return view('categories.show', compact('category', 'blogs'));
    }

    public function syncCategoryImages()
    {
        \App\Jobs\SyncCategoryImagesJob::dispatch();

        return response()->json([
            'status' => 'success',
            'message' => 'Image synchronization started in the background.',
        ]);
    }
}
