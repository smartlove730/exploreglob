<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Country;
use App\Models\EmailLog;
use App\Services\DynamicMailConfigService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
        $deleteJobPath = app_path('Jobs/DeletePostJob.php');

        if (!class_exists(\App\Jobs\DeletePostJob::class, false) && is_file($deleteJobPath)) {
            require_once $deleteJobPath;
        }
    }

    /**
     * Bootstrap any application services.
     */
   
    public function boot(): void
    {
        try {
            if (Schema::hasTable('mail_settings')) {
                app(DynamicMailConfigService::class)->apply();
            }
        } catch (\Throwable) {
            // Database may not be migrated yet during install, tests, or artisan bootstrap.
        }

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            try {
                $message = $event->message;
                $recipients = collect($message->getTo())
                    ->map(fn ($address) => method_exists($address, 'getAddress') ? $address->getAddress() : (string) $address)
                    ->implode(', ');

                EmailLog::create([
                    'recipient' => $recipients ?: 'unknown',
                    'subject' => $message->getSubject(),
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta' => ['message_id' => method_exists($message, 'getHeaders') ? optional($message->getHeaders()->get('Message-ID'))->getBodyAsString() : null],
                ]);
            } catch (\Throwable) {
                //
            }
        });

        Paginator::useBootstrapFive();
        RateLimiter::for('contact-form', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('password-email', function (Request $request) {
            return Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('billing-checkout', function (Request $request) {
            $userKey = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(10)->by($userKey);
        });

        RateLimiter::for('billing-webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

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
