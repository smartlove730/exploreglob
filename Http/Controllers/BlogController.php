<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Blog, Category};
use App\Jobs\GenerateBlogs;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Gemini\Laravel\Facades\Gemini;

class BlogController extends Controller
{
    public function index(Request $request)
    {  
        $blogs = Blog::where('status', 1)->where('category_id', $request->id)->latest()->paginate(10);
      
        return view('blogs.index', compact('blogs'));
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)->with('category')->firstOrFail();

        $related = Blog::where('category_id', $blog->category_id)
            ->where('id', '!=', $blog->id)
            ->limit(5)
            ->get();
            $seo_title = $blog->seo_title ?? $blog->title;
            $seo_description = $blog->seo_description ?? str($blog->content)->limit(160);
            $og_image = $blog->featured_image;
  
            return view('blogs.show', compact(
                'blog',
                'related',
                'seo_title',
                'seo_description',
                'og_image'
            ));

       
    }

    public function store(Request $request)
    {
        // Get country_id from request or use default
        $countryId = $request->input('country_id', 183);
        $limit = $request->input('limit', 1);

        $categories = Category::with('country')
            ->where('country_id', $countryId)
            ->where('status', 1)
            ->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active categories found for this country'
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
                'jobs_dispatched' => $dispatched
            ]
        ]);
    }


    public function genImage(){
        $prompt='Abstract futuristic cityscape with glowing data streams connecting various technological elements like AI brains, IoT devices, and cloud servers, symbolizing interconnectedness and innovation.'; 
        
        $apiKey = config('gemini.api_key') ?? env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            return response()->json(['error' => 'API key not configured'], 500);
        }
        
        $result = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict", [
                'instances' => [
                    ['prompt' => $prompt]
                ],
                'parameters' => [
                    'sampleCount' => 4
                ]
            ]);

        $aiRaw = $result->json();

        return response()->json($aiRaw);
    }

  

function normalizeAiResponse(array $aiResponse): array
{
    // Step 1: Extract the raw text JSON from AI response
    $rawText = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$rawText) {
        return ['error' => 'Invalid AI response structure'];
    }

    // Step 2: Remove markdown code fences if present
    $rawText = trim($rawText);
    $rawText = preg_replace('/^```json\s*/', '', $rawText);
    $rawText = preg_replace('/\s*```$/', '', $rawText);

    // Step 3: Decode JSON
    $data = json_decode($rawText, true);
    if (!$data) {
        return ['error' => 'Invalid JSON in AI response', 'raw' => $rawText];
    }

    // Step 4: Normalize nested content arrays
    $normalizedContent = [];

    if (isset($data['content']) && is_array($data['content'])) {
        foreach ($data['content'] as $item) {
            // If the item has its own 'content', merge it recursively
            if (isset($item['content']) && is_array($item['content'])) {
                $normalizedContent = array_merge($normalizedContent, $item['content']);
            } else {
                $normalizedContent[] = $item;
            }
        }
    }

    // Step 5: Return normalized structure
    return [
        'title' => $data['title'] ?? null,
        'content' => $normalizedContent
    ];
}

}
