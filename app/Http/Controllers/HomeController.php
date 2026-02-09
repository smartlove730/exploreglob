<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $country = session('country')?? 183;
        $minBlogs = (int) env('CATEGORY_MIN_BLOGS', 3);

        $blogs = Blog::with('category')
            ->where('status', 1)
            ->when($country, fn($q) => $q->where('country_id', $country))
            ->latest()
            ->take(10)
            ->get();

        $categories = Category::where('status', 1)
            ->where('country_id', $country)
            ->withCount(['blogs' => fn($q) => $q->where('status', 1)])
            ->having('blogs_count', '>=', $minBlogs)
            ->get();
 
        return view('home', compact('blogs', 'categories'));
    }
}
