<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Blog;

class CategoryController extends Controller
{
    public function index()
    {
            $country = session('country');
           
        $categories = Category::where('status', 1)
        ->when($country, fn($q) => $q->where('country_id', $country))->get();

        return view('categories.index', compact('categories'));
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $blogs = Blog::where('category_id', $category->id)
            ->where('status', 1)
            ->paginate(10);

        return view('categories.show', compact('category', 'blogs'));
    }
}
