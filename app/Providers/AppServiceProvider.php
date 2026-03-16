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

            $travelRootCategory = Cache::remember("layout:travel:root:country:{$country}",
                now()->addMinutes(30),
                fn () => Category::travelRoot($country)
            );

            $travelNavCategories = Cache::remember("layout:travel:nav:country:{$country}",
                now()->addMinutes(15),
                function () use ($country) {
                    $navNames = Category::TRAVEL_NAV_CATEGORY_NAMES;

                    $categories = Category::travelSubcategories($country)
                        ->where('status', 1)
                        ->whereIn('name', $navNames)
                        ->get(['id', 'name', 'slug']);

                    return $categories
                        ->sortBy(fn ($category) => array_search($category->name, $navNames, true))
                        ->values();
                }
            );

            $topFooterCategories = Cache::remember("layout:footer:categories:country:{$country}:travel",
                now()->addMinutes(15),
                fn () => Category::query()
                    ->travelSubcategories($country)
                    ->where('status', 1)
                    ->withCount(['blogs' => fn($query) => $query->where('status', 1)])
                    ->orderByDesc('blogs_count')
                    ->orderBy('name')
                    ->take(5)
                    ->get()
            );

            $topFooterBlogs = Cache::remember("layout:footer:blogs:country:{$country}:travel",
                now()->addMinutes(15),
                fn () => Blog::query()
                    ->where('status', 1)
                    ->where('country_id', $country)
                    ->inTravelSubcategories($country)
                    ->with('category:id,slug')
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
                'travelRootCategory' => $travelRootCategory,
                'travelNavCategories' => $travelNavCategories,
            ]);
        });
    }
        
}
