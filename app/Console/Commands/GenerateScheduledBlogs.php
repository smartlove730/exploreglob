<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBlogs;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateScheduledBlogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blogs:generate-scheduled 
                            {--country= : Country ID to generate blogs for}
                            {--category= : Category ID to generate blogs for}
                            {--limit=1 : Number of blogs per category}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate blogs automatically based on schedule. Can be configured with country, category, and limit options.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $countryId = $this->option('country');
        $categoryId = $this->option('category');
        $limit = (int) $this->option('limit') ?? 1;

        $this->info('Starting scheduled blog generation...');

        try {
            if ($countryId && $categoryId) {
                // Generate for specific country and category
                $this->generateForSpecific($countryId, $categoryId, $limit);
            } elseif ($countryId) {
                // Generate for all categories in a country
                $this->generateForCountry($countryId, $limit);
            } elseif ($categoryId) {
                // Generate for category across all countries
                $this->generateForCategory($categoryId, $limit);
            } else {
                // Generate for all active countries and categories
                $this->generateForAll($limit);
            }

            $this->info('Blog generation jobs dispatched successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Scheduled blog generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Generate blogs for a specific country and category
     */
    private function generateForSpecific($countryId, $categoryId, $limit)
    {
        $category = Category::where('id', $categoryId)
            ->where('country_id', $countryId)
            ->first();

        if (!$category) {
            throw new \Exception("Category {$categoryId} not found for country {$countryId}");
        }

        for ($i = 0; $i < $limit; $i++) {
            GenerateBlogs::dispatch($countryId, $categoryId);
            $this->line("  → Queued blog for: {$category->name} (Country ID: {$countryId})");
        }
    }

    /**
     * Generate blogs for all categories in a country
     */
    private function generateForCountry($countryId, $limit)
    {
        $categories = Category::where('country_id', $countryId)
            ->where('status', 1)
            ->get();

        if ($categories->isEmpty()) {
            $this->warn("No active categories found for country ID: {$countryId}");
            return;
        }

        foreach ($categories as $category) {
            for ($i = 0; $i < $limit; $i++) {
                GenerateBlogs::dispatch($countryId, $category->id);
                $this->line("  → Queued blog for: {$category->name} (Country ID: {$countryId})");
            }
        }

        
    }

    /**
     * Generate blogs for a category across all countries
     */
    private function generateForCategory($categoryId, $limit)
    {
        $category = Category::findOrFail($categoryId);
        $countries = Country::where('status', 1)->get();

        if ($countries->isEmpty()) {
            $this->warn("No active countries found");
            return;
        }

        $dispatched = 0;
        foreach ($countries as $country) {
            $categoryForCountry = Category::where('id', $categoryId)
                ->where('country_id', $country->id)
                ->first();

            if ($categoryForCountry) {
                for ($i = 0; $i < $limit; $i++) {
                    GenerateBlogs::dispatch($country->id, $categoryId);
                    $dispatched++;
                }
            }
        }

        $this->info("Queued {$dispatched} blog(s) for category: {$category->name}");
    }

    /**
     * Generate blogs for all countries and categories
     */
    private function generateForAll($limit)
    {
        $countries = Country::where('status', 1)->get();

        if ($countries->isEmpty()) {
            $this->warn("No active countries found");
            return;
        }

        $totalDispatched = 0;
        foreach ($countries as $country) {
            $categories = Category::where('country_id', $country->id)
                ->where('status', 1)
                ->get();

            foreach ($categories as $category) {
                for ($i = 0; $i < $limit; $i++) {
                    GenerateBlogs::dispatch($country->id, $category->id);
                    $totalDispatched++;
                }
            }
        }

        $this->info("Queued {$totalDispatched} blog(s) total");
    }
}
