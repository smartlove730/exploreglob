<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BlogGenerationController;
use App\Http\Controllers\Api\CategoryController as ApiCategoryController;

Route::prefix('v1')->group(function () {
    // API endpoints for cron jobs (require API key authentication)
    Route::middleware(['api.key'])->group(function () {
        // Generate blog for specific category and country
        Route::post('/generate-blog', [BlogGenerationController::class, 'generateBlog']);
        
        // Generate blogs for all categories in a country
        Route::post('/generate-blogs-for-country', [BlogGenerationController::class, 'generateBlogsForCountry']);
        
        // Generate blogs for specific category across all countries
        Route::post('/generate-blogs-for-category', [BlogGenerationController::class, 'generateBlogsForCategory']);
        
        // Get generation status
        Route::get('/generation-status', [BlogGenerationController::class, 'getStatus']);
    });
    
    // Public API endpoints (no auth required)
    Route::get('/categories', [ApiCategoryController::class, 'index']);
    Route::get('/categories/{id}', [ApiCategoryController::class, 'show']);
});
