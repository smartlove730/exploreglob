<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateBlogs;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BlogGenerationController extends Controller
{
    /**
     * Generate a single blog for a specific category and country
     * 
     * POST /api/v1/generate-blog
     * Body: { "country_id": 1, "category_id": 1 }
     */
    public function generateBlog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $countryId = $request->country_id;
            $categoryId = $request->category_id;

            // Verify category belongs to country
            $category = Category::where('id', $categoryId)
                ->where('country_id', $countryId)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category does not belong to the specified country'
                ], 404);
            }

            // Dispatch job to queue
            GenerateBlogs::dispatch($countryId, $categoryId);

            Log::info('Blog generation job dispatched', [
                'country_id' => $countryId,
                'category_id' => $categoryId,
                'category_name' => $category->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Blog generation job queued successfully',
                'data' => [
                    'country_id' => $countryId,
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'status' => 'queued'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching blog generation job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue blog generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate blogs for all categories in a country
     * 
     * POST /api/v1/generate-blogs-for-country
     * Body: { "country_id": 1, "limit": 5 } // optional limit per category
     */
    public function generateBlogsForCountry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|integer|exists:countries,id',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $countryId = $request->country_id;
            $limit = $request->input('limit', 1);

            $categories = Category::where('country_id', $countryId)
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

            Log::info('Multiple blog generation jobs dispatched for country', [
                'country_id' => $countryId,
                'categories_count' => $categories->count(),
                'total_jobs_dispatched' => $dispatched
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$dispatched} blog generation job(s)",
                'data' => [
                    'country_id' => $countryId,
                    'categories_count' => $categories->count(),
                    'jobs_per_category' => $limit,
                    'total_jobs_dispatched' => $dispatched
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching multiple blog generation jobs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue blog generation jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate blogs for a specific category across all countries
     * 
     * POST /api/v1/generate-blogs-for-category
     * Body: { "category_id": 1, "limit": 3 }
     */
    public function generateBlogsForCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:categories,id',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $categoryId = $request->category_id;
            $limit = $request->input('limit', 1);

            $category = Category::findOrFail($categoryId);
            $countries = Country::where('status', 1)->get();

            if ($countries->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active countries found'
                ], 404);
            }

            $dispatched = 0;
            foreach ($countries as $country) {
                // Check if category exists for this country
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

            Log::info('Blog generation jobs dispatched for category across countries', [
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'total_jobs_dispatched' => $dispatched
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$dispatched} blog generation job(s)",
                'data' => [
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'jobs_per_country' => $limit,
                    'total_jobs_dispatched' => $dispatched
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching blog generation jobs for category', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue blog generation jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get generation status (simple health check)
     * 
     * GET /api/v1/generation-status
     */
    public function getStatus()
    {
        return response()->json([
            'success' => true,
            'message' => 'API is operational',
            'data' => [
                'status' => 'active',
                'timestamp' => now()->toDateTimeString(),
                'queue_connection' => config('queue.default')
            ]
        ]);
    }
}
