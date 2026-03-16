<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateBlogs;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $countryId = session('country') ?? 183;
        $categoryId = (int) $request->id;

        $category = Category::travelSubcategories($countryId)
            ->where('status', 1)
            ->where('id', $categoryId)
            ->firstOrFail();

        $blogs = Blog::where('status', 1)
            ->where('category_id', $category->id)
            ->with('category')
            ->latest('published_at')
            ->paginate(10);

        return view('blogs.index', compact('blogs', 'category'));
    }

    public function showByCategory($subcategory, $slug)
    {
        $countryId = session('country') ?? 183;
        $category = Category::travelSubcategories($countryId)
            ->where('status', 1)
            ->where('slug', $subcategory)
            ->firstOrFail();

        $cacheKey = "blog_post_{$category->id}_{$slug}";

        $data = Cache::remember($cacheKey, 3600, function () use ($slug, $category) {
            $blog = Blog::where('slug', $slug)
                ->where('category_id', $category->id)
                ->where('status', 1)
                ->with('category')
                ->firstOrFail();

            $related = Blog::where('category_id', $blog->category_id)
                ->where('status', 1)
                ->where('id', '!=', $blog->id)
                ->latest('published_at')
                ->limit(5)
                ->get();

            return [
                'blog' => $blog,
                'related' => $related,
                'seo_title' => $blog->seo_title ?? $blog->title,
                'seo_description' => $blog->seo_description ?? str($blog->content)->limit(160),
                'og_image' => $blog->featured_image,
            ];
        });

        return view('blogs.show', $data);
    }

    public function show($slug)
    {
        $blog = Blog::with('category')->where('slug', $slug)->where('status', 1)->firstOrFail();
        $category = $blog->category;

        if (! $category) {
            abort(404);
        }

        $travelRoot = Category::travelRoot($category->country_id);
        if (! $travelRoot || $category->parent_id !== $travelRoot->id) {
            abort(404);
        }

        return redirect('/travel/' . $category->slug . '/' . $blog->slug, 301);
    }

    public function store(Request $request)
    {
        $countryId = (int) $request->input('country_id', 183);
        $limit = max((int) $request->input('limit', 1), 1);

        $categories = Category::with('country')
            ->where('country_id', $countryId)
            ->where('status', 1)
            ->travelSubcategories($countryId)
            ->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active travel categories found for this country',
            ], 404);
        }

        $dispatched = 0;
        foreach ($categories as $category) {
            for ($i = 0; $i < $limit; $i++) {
                GenerateBlogs::dispatch($countryId, $category->id);
                $dispatched++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully queued {$dispatched} blog generation job(s)",
            'data' => [
                'country_id' => $countryId,
                'categories_count' => $categories->count(),
                'jobs_dispatched' => $dispatched,
            ],
        ]);
    }

    public function genImage()
    {
        $prompt = 'Abstract futuristic cityscape with glowing data streams connecting various technological elements like AI brains, IoT devices, and cloud servers, symbolizing interconnectedness and innovation.';

        $apiKey = config('gemini.api_key') ?? env('GEMINI_API_KEY');

        if (! $apiKey) {
            return response()->json(['error' => 'API key not configured'], 500);
        }

        $result = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict', [
                'instances' => [
                    ['prompt' => $prompt],
                ],
                'parameters' => [
                    'sampleCount' => 4,
                ],
            ]);

        return response()->json($result->json());
    }
}
