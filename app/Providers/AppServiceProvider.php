<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $country = session('country') ?? 183;

            $topFooterCategories = Category::query()
                ->where('status', 1)
                ->where('country_id', $country)
                ->withCount(['blogs' => fn($query) => $query->where('status', 1)])
                ->orderByDesc('blogs_count')
                ->orderBy('name')
                ->take(5)
                ->get();

            $topFooterBlogs = Blog::query()
                ->where('status', 1)
                ->where('country_id', $country)
                ->latest()
                ->take(5)
                ->get();

            $view->with([
                'topFooterCategories' => $topFooterCategories,
                'topFooterBlogs' => $topFooterBlogs,
            ]);
        });
    }
        
}
