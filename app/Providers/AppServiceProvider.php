<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
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

            $topFooterCategories = Cache::remember("layout:footer:categories:country:{$country}",
                now()->addMinutes(15),
                fn () => Category::query()
                    ->where('status', 1)
                    ->where('country_id', $country)
                    ->withCount(['blogs' => fn($query) => $query->where('status', 1)])
                    ->orderByDesc('blogs_count')
                    ->orderBy('name')
                    ->take(5)
                    ->get()
            );

            $topFooterBlogs = Cache::remember("layout:footer:blogs:country:{$country}",
                now()->addMinutes(15),
                fn () => Blog::query()
                    ->where('status', 1)
                    ->where('country_id', $country)
                    ->latest()
                    ->take(5)
                    ->get()
            );

            $countries = Cache::remember('layout:countries', now()->addHours(6), fn () =>
                Country::query()
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code'])
            );

            $view->with([
                'countries' => $countries,
                'topFooterCategories' => $topFooterCategories,
                'topFooterBlogs' => $topFooterBlogs,
            ]);
        });
    }
        
}
