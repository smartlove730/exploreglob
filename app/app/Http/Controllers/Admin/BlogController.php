<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
 
use App\Models\Blog;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::latest()->paginate(20);
        return view('admin.blogs.index', compact('blogs'));
    }

    // Return HTML form for modal create
    public function createModal()
    {
        $categories = Category::all();
        $countries = Country::all();
        return view('admin.blogs.partials.form', compact('categories', 'countries'));
    }

    // Return HTML form for modal edit
    public function editModal(Blog $blog)
    {
        $categories = Category::all();
        $countries = Country::all();
        return view('admin.blogs.partials.form', compact('blog', 'categories', 'countries'));
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'featured_image' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'status' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog created');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'featured_image' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'status' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);

        $blog->update($data);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted');
    }

    /**
     * Handle AJAX image uploads from the admin UI.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|max:5120',
        ]);

        $path = $request->file('file')->store('uploads', 'public');
        $url = Storage::url($path);

        return response()->json(['url' => $url]);
    }
}
