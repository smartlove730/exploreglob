<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     * 
     * GET /api/v1/categories
     */
    public function index(Request $request)
    {
        $countryId = $request->input('country_id');
        
        $categories = Category::with('country')
            ->when($countryId, fn($q) => $q->where('country_id', $countryId))
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get single category
     * 
     * GET /api/v1/categories/{id}
     */
    public function show($id)
    {
        $category = Category::with(['country', 'blogs' => function($query) {
            $query->where('status', 1)->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }
}
