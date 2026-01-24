<?php

namespace App\Jobs;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateBlogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $countryId;
    public $categoryID;

    public $tries = 3; // Retry up to 3 times
    public $backoff = [60, 300, 900]; // Wait 1min, 5min, 15min between retries
    public $timeout = 3600; // 1 hour max execution for job

    /**
     * Create a new job instance.
     */
    public function __construct($countryId, $categoryID)
    {
        $this->countryId = $countryId;
        $this->categoryID = $categoryID;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // 🔹 Allow long execution
        ini_set('max_execution_time', 30000);
        set_time_limit(30000);

        $categories = Category::with('country')->where('country_id', $this->countryId)->where('id', $this->categoryID)->get();
 
        foreach ($categories as $category) {
        
        $PromptGenforcat = "Generate a prompt for blog article genration on the topic of {$category->name}. output should be in json form as following format.
        {
        'prompt':''
        }";

         // Call Gemini API to generate prompt
            $apiKey = config('gemini.api_key') ?? env('GEMINI_API_KEY');
            
            if (!$apiKey) {
                Log::error('Gemini API key not configured');
                throw new \Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
            }

            $model = "gemma-3-27b-it";
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
           
            $result = Http::timeout(180)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, [
                    'contents' => [['parts' => [['text' => $PromptGenforcat]]]]
                ]);

            if (!$result->successful()) {
                Log::error('AI API request for prompt generation failed', [
                    'status' => $result->status(),
                    'response' => $result->body()
                ]);
                throw new \Exception('AI API request for prompt generation failed');
            }

            $responseJson = $result->json();
            $promptData = $this->normalizeAiResponse($responseJson);
            
            $prompt1 = $promptData['prompt'] ?? null;

            if (!$prompt1) {
                Log::error('Failed to generate prompt from AI response', [
                    'response' => $responseJson
                ]);
                throw new \Exception('Failed to generate prompt from AI response');
            }

            // Now use the generated prompt to create the blog content


        $prompt = $prompt1 ."
Give related image generation prompt for the blog article.
Format your response strictly as JSON using this exact schema:

{
  'title': '',
  'subtitle': '',
  'author': '',
  'published_date': '',
  'read_time': '',
  'cover_image': '',
  'seo_title': '',
  'seo_description': '',
  'json_schema': '',
  'seo_keywords': '',
  'image_gen_prompt_for_related_blog': '',
  'sections': [
    {
      'heading': '',
      'content': ''
    }
  ],
  'hashtags': [] 
}
The content should sound insightful, helpful, and written by a human, suitable for a design or technology blog. Use realistic and educational examples, not overly generic text.";
          
            // Get API key from config/environment
            $apiKey = config('gemini.api_key') ?? env('GEMINI_API_KEY');
            
            if (!$apiKey) {
                Log::error('Gemini API key not configured');
                throw new \Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
            }

              $model = config('gemini.model', 'gemma-3-27b');
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            
            $maxRetries = 3;
            $retryCount = 0;
            $aiRaw = null;
            
            do {
                try {
                 
                    
                    $result = Http::timeout(180)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                        ])
                        ->post($apiUrl, [
                            'contents' => [['parts' => [['text' => $prompt]]]]
                        ]);

                    if (!$result->successful()) {
                        Log::warning('AI API request failed', [
                            'status' => $result->status(),
                            'response' => $result->body()
                        ]);
                        
                        if ($retryCount < $maxRetries - 1) {
                            $retryCount++;
                            sleep(5); // Wait before retry
                            continue;
                        } else {
                            throw new \Exception('AI API request failed after ' . $maxRetries . ' attempts');
                        }
                    }

                    $responseJson = $result->json();
                    $aiRaw = $this->normalizeAiResponse($responseJson);

                    // Check if we got valid data (should have more than just empty fields)
                    if (!empty($aiRaw['title']) && !empty($aiRaw['sections'])) {
                        break; // Success, exit retry loop
                    }

                    $retryCount++;
                    
                } catch (\Exception $e) {
                    Log::error('Error calling AI API', [
                        'error' => $e->getMessage(),
                        'attempt' => $retryCount + 1
                    ]);
                    
                    if ($retryCount < $maxRetries - 1) {
                        $retryCount++;
                        sleep(10);
                    } else {
                        throw $e;
                    }
                }
            } while ($retryCount < $maxRetries);
            
            if (!$aiRaw || empty($aiRaw['title'])) {
                throw new \Exception('Failed to generate valid blog content from AI');
            }
 
  
    
          $blog = Blog::create([
    'title'           => $aiRaw['title'] ?? 'Untitled',
    'slug'            => Str::slug($aiRaw['title'] ?? 'untitled'),
    'content'         => json_encode([
        'subtitle'       => $aiRaw['subtitle'] ?? '',
        'author'         => $aiRaw['author'] ?? '',
        'published_date' => $aiRaw['published_date'] ?? '',
        'read_time'      => $aiRaw['read_time'] ?? '',
        'cover_image'    => $aiRaw['cover_image'] ?? '',
        'json_schema'    => $aiRaw['json_schema'] ?? '',
        'sections'       => $aiRaw['sections'] ?? [],
        'hashtags'       => $aiRaw['hashtags'] ?? [],
        'related_blogs'  => $aiRaw['related_blogs'] ?? [],
        'image_gen_prompt_for_related_blog'  => $aiRaw['image_gen_prompt_for_related_blog'] ?? [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),

    // You can still extract a short excerpt dynamically
    'excerpt'         => Str::limit(strip_tags($aiRaw['sections'][0]['content'] ?? ''), 200),

    // Map existing fields properly
    'featured_image'  => $aiRaw['cover_image'] ?? null,
    'category_id'     => $category->id,
    'country_id'      => $category->country_id,
    'seo_title'       => $aiRaw['seo_title'] ?? ($aiRaw['title'] ?? 'Untitled'),
    'seo_description' => $aiRaw['seo_description'] ?? '',
    'seo_keywords'    => $aiRaw['seo_keywords'] ?? '',
    'published_at'    => $aiRaw['published_date'] ?? now(),
    'status'          => 1,
]);

            Log::info('Blog created successfully', [
                'blog_id' => $blog->id,
                'blog_title' => $blog->title,
                'category_id' => $category->id
            ]);

            break; // Exit category loop after successful generation
        }
    }

    /**
     * Normalize AI response from Gemini API
     */
    private function normalizeAiResponse(array $aiResponse): array
{
    // Safely extract the raw text from Gemini's nested response structure
    $rawText = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$rawText) {
        return [
            'title' => '',
            'subtitle' => '',
            'author' => '',
            'published_date' => '',
            'read_time' => '',
            'cover_image' => '',
            'sections' => [],
            'hashtags' => [],
            'related_blogs' => []
        ];
    }

    // Clean up formatting and code fences like ```json ... ```
    $rawText = trim($rawText);
    $rawText = preg_replace('/^```json\s*/', '', $rawText);
    $rawText = preg_replace('/\s*```$/', '', $rawText);

    // Decode JSON
    $data = json_decode($rawText, true);

    // Handle JSON decode errors gracefully
    if (json_last_error() !== JSON_ERROR_NONE || !$data) {
        return [
            'title' => '',
            'subtitle' => '',
            'author' => '',
            'published_date' => '',
            'read_time' => '',
            'cover_image' => '',
            'sections' => [],
            'hashtags' => [],
            'related_blogs' => []
        ];
    }

    // Normalize the structure
    return [
        'title' => $data['title'] ?? '',
        'subtitle' => $data['subtitle'] ?? '',
        'author' => $data['author'] ?? '',
        'published_date' => $data['published_date'] ?? '',
        'read_time' => $data['read_time'] ?? '',
        'cover_image' => $data['cover_image'] ?? '',
        'seo_title' => $data['seo_title'] ?? '',
        'seo_description' => $data['seo_description'] ?? '',
        'seo_keywords' => $data['seo_keywords'] ?? '',
        'json_schema' => $data['json_schema'] ?? '',
        'sections' => $data['sections'] ?? [],
        'hashtags' => $data['hashtags'] ?? [],
        'related_blogs' => $data['related_blogs'] ?? [],
        'prompt' => $data['prompt'] ?? '',
        'image_gen_prompt_for_related_blog' => $data['image_gen_prompt_for_related_blog'] ??''

    ];
}

}
