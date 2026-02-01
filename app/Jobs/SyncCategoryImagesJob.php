<?php

namespace App\Jobs;

use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncCategoryImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set timeout to 15 minutes since we are downloading many images
    public $timeout = 900;

    public function handle()
    {
        $apiKey = 'L9qUCZTQJhmkc3SXYZFX8YAbIoWNoPekItT4DFiPTwNju1I29T0xCvHH';
        
        $categoriesToBeFilled = [
            'Food & Recipes', 'Freelancing', 'Gaming', 'Home & Living', 'Lifestyle',
            'Mobile Apps', 'Movies & TV', 'Music', 'News & Trends', 'Nutrition',
            'Parenting', 'Personal Development', 'Photography', 'Politics',
            'Productivity', 'Real Estate', 'Remote Work', 'Social Media',
            'Spirituality', 'Startups', 'Web Development'
        ];

        // Fetch only the categories we need
        $categories = Category::where('country_id',183)->get();

        foreach ($categories as $category) {
            $folderName = $category->name;
            $directoryPath = "categories/{$folderName}";

            // 1. Hit the Pexels API
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->get('https://api.pexels.com/v1/search', [
                'query' => $category->name,
                'per_page' => 10,
                'orientation' => 'landscape'
            ]);

            if ($response->successful()) {
                $photos = $response->json()['photos'] ?? [];

                foreach ($photos as $photo) {
                    $originalUrl = $photo['src']['original'];
                    $extension = pathinfo(parse_url($originalUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $fileName = $photo['id'] . '.' . $extension;
                    $fullSavePath = "{$directoryPath}/{$fileName}";

                    // Check if file already exists to save bandwidth/time
                    if (!Storage::disk('public')->exists($fullSavePath)) {
                        try {
                            $imageResponse = Http::get($originalUrl);
                            if ($imageResponse->successful()) {
                                Storage::disk('public')->put($fullSavePath, $imageResponse->body());
                            }
                        } catch (\Exception $e) {
                            // Log error and continue with next image
                            \Log::error("Failed to download image {$photo['id']} for category {$folderName}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}