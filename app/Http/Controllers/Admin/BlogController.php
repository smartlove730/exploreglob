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
        $categories = Category::travelSubcategories()
            ->orderBy('name')
            ->get();
        $countries = Country::all();
        return view('admin.blogs.partials.form', compact('categories', 'countries'));
    }

    // Return HTML form for modal edit
    public function editModal(Blog $blog)
    {
        $categories = Category::travelSubcategories()
            ->orderBy('name')
            ->get();
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
            'category_id' => 'required|integer|exists:categories,id',
            'country_id' => 'required|integer|exists:countries,id',
            'featured_image' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'status' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);

        $category = Category::find($data['category_id']);
        if (!$category) {
            return back()->withErrors(['category_id' => 'Selected category not found.'])->withInput();
        }

        if ((int) $category->country_id !== (int) $data['country_id']) {
            return back()->withErrors(['category_id' => 'Category does not match the selected country.'])->withInput();
        }

        $travelRoot = Category::travelRoot($category->country_id);
        if (!$travelRoot || $category->parent_id !== $travelRoot->id) {
            return back()->withErrors(['category_id' => 'Category must be a Travel subcategory.'])->withInput();
        }

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
            'category_id' => 'required|integer|exists:categories,id',
            'country_id' => 'required|integer|exists:countries,id',
            'featured_image' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'status' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['title']);

        $category = Category::find($data['category_id']);
        if (!$category) {
            return back()->withErrors(['category_id' => 'Selected category not found.'])->withInput();
        }

        if ((int) $category->country_id !== (int) $data['country_id']) {
            return back()->withErrors(['category_id' => 'Category does not match the selected country.'])->withInput();
        }

        $travelRoot = Category::travelRoot($category->country_id);
        if (!$travelRoot || $category->parent_id !== $travelRoot->id) {
            return back()->withErrors(['category_id' => 'Category must be a Travel subcategory.'])->withInput();
        }

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
