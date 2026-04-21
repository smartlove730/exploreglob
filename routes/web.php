<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\ContentCalendarController;
use App\Http\Controllers\App\MediaLibraryController;
use App\Http\Controllers\MarketingController;

use App\Http\Controllers\{
    HomeController,
    CategoryController,
    BlogController,
    CountryController,
    NewsletterSubscriptionController,
    ImageConversionController
};

Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/features', [MarketingController::class, 'features'])->name('marketing.features');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/about', [MarketingController::class, 'about'])->name('marketing.about');
Route::get('/contact', [MarketingController::class, 'contact'])->name('marketing.contact');
Route::post('/contact', [MarketingController::class, 'sendContact'])->middleware('throttle:contact-form')->name('marketing.contact.send');
Route::get('/privacy-policy', [MarketingController::class, 'privacy'])->name('marketing.privacy');
Route::get('/terms-and-conditions', [MarketingController::class, 'terms'])->name('marketing.terms');
Route::get('/data-deletion-instructions', [MarketingController::class, 'dataDeletion'])->name('marketing.data-deletion');

Route::get('/explore', [HomeController::class, 'index'])->name('home');
Route::get('/home/categories/load', [HomeController::class, 'loadCategories'])->name('home.categories.load');
Route::get('/search', [HomeController::class, 'search'])->name('home.search');

Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->name('dashboard');

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'verified', 'role:customer,admin'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

        Route::get('/billing/plans', [BillingController::class, 'index'])->name('billing.plans');
        Route::post('/billing/subscribe', [BillingController::class, 'startCheckout'])->middleware('throttle:billing-checkout')->name('billing.subscribe');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
        Route::get('/settings', [\App\Http\Controllers\App\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/password', [\App\Http\Controllers\App\SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::get('/facebook/settings', [FacebookSettingsController::class, 'index'])->name('facebook.settings');
        Route::get('/facebook/connect', [FacebookSettingsController::class, 'redirectToFacebook'])->name('facebook.connect');
        Route::post('/facebook/sync-pages', [FacebookSettingsController::class, 'syncPages'])->name('facebook.sync-pages');
        Route::get('/google/settings', [GoogleController::class, 'index'])->name('google.settings');
        Route::get('/google/connect', [GoogleController::class, 'redirect'])->name('google.connect');
        Route::post('/google/sync-locations', [GoogleController::class, 'syncLocations'])->name('google.sync-locations');

        Route::get('/calendar', [ContentCalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [ContentCalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar', [ContentCalendarController::class, 'store'])->name('calendar.store');
        Route::put('/calendar/{id}', [ContentCalendarController::class, 'update'])->name('calendar.update');
        Route::delete('/calendar/{id}', [ContentCalendarController::class, 'destroy'])->name('calendar.destroy');
        Route::post('/calendar/import-csv', [ContentCalendarController::class, 'importCsv'])->name('calendar.import');
        Route::get('/calendar/imports/{id}/errors', [ContentCalendarController::class, 'downloadImportErrors'])->name('calendar.import.errors');

        Route::get('/media', [MediaLibraryController::class, 'index'])->name('media.index');
        Route::get('/media/list', [MediaLibraryController::class, 'list'])->name('media.list');
        Route::post('/media', [MediaLibraryController::class, 'store'])->name('media.store');
        Route::delete('/media/{id}', [MediaLibraryController::class, 'destroy'])->name('media.destroy');
    });

// Country Selector
Route::get('/country/{code}', [CountryController::class, 'setCountry'])->name('country.set');

// Categories
Route::get('/travel', [CategoryController::class, 'index'])->name('travel.index');
Route::get('/travel/{subcategory}', [CategoryController::class, 'show'])->name('travel.category');
Route::get('/categories', function () {
    return redirect('/travel', 301);
})->name('categories.index');
Route::get('/category/{slug}', function ($slug) {
    return redirect('/travel/' . $slug, 301);
})->name('category.show');

// Blogs
Route::get('/blogs/{id}', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/travel/{subcategory}/{slug}', [BlogController::class, 'showByCategory'])->name('travel.blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store'])->name('newsletter.subscribe');

Route::get('/tools/convert-category-images', [ImageConversionController::class, 'convertCategoryImages'])->name('tools.convert-category-images');

// Legacy aliases for existing public pages
Route::redirect('/policy', '/privacy-policy', 301)->name('policy');
Route::redirect('/terms', '/terms-and-conditions', 301)->name('terms');
Route::redirect('/data-deletion', '/data-deletion-instructions', 301)->name('data-deletion');
Route::redirect('/about-us', '/about', 301)->name('about.alternate');
Route::post('/addblog', [BlogController::class, 'store'])->name('store');
Route::post('/genimage', [BlogController::class, 'genImage'])->name('genImage');

// Admin routes (simple Blade-based admin)
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\FacebookSettingsController;
use App\Http\Controllers\Admin\FacebookPostController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\FacebookAppController;
use App\Http\Controllers\Admin\DriveApiKeyController;
use App\Http\Controllers\Admin\DriveFolderController;
use App\Http\Controllers\Admin\GoogleController;
use App\Http\Controllers\Admin\AutomationConfigController;
use App\Http\Controllers\Admin\ScheduledPostController;
use App\Http\Controllers\Admin\SaasManagementController;
use App\Http\Controllers\AutomationController;

Route::middleware(['auth', 'admin'])->post('/synccategoryimages', [CategoryController::class, 'syncCategoryImages'])->name('syncCategoryImages');
Route::middleware(['auth', 'role:customer,admin', 'subscription.active'])->get('/run-automations/{userId}/{automationConfigId?}', [AutomationController::class, 'run'])->name('automations.run');

Route::middleware(['auth'])->get('/auth/facebook/callback', [FacebookSettingsController::class, 'callback'])->name('oauth.facebook.callback');
Route::middleware(['auth'])->get('/auth/google/callback', [GoogleController::class, 'callback'])->name('oauth.google.callback');
Route::middleware(['auth', 'role:customer,admin'])->get('/auth/google/drive/connect', [DriveApiKeyController::class, 'redirectToGoogleOauth'])->name('admin.google-drive.connect');
Route::middleware(['auth', 'role:customer,admin'])->get('/auth/google/drive/callback', [DriveApiKeyController::class, 'callback'])->name('admin.google-drive.callback');


Route::middleware(['auth', 'role:customer,admin'])
    ->prefix('admin/facebook')
    ->name('admin.facebook.')
    ->group(function () {
        Route::get('settings', [FacebookSettingsController::class, 'index'])->name('settings');
        Route::get('connect', [FacebookSettingsController::class, 'redirectToFacebook'])->name('connect');
        Route::post('sync-pages', [FacebookSettingsController::class, 'syncPages'])->name('sync-pages');
        Route::post('pages/{page}/activate', [FacebookSettingsController::class, 'activatePage'])->name('pages.activate');

        Route::resource('apps', FacebookAppController::class)->except(['show']);
        Route::resource('google-drive-keys', DriveApiKeyController::class)->except(['show']);
        Route::resource('drive-folders', DriveFolderController::class)->except(['show']);

        Route::post('posts/generate-caption', [FacebookPostController::class, 'generateCaption'])->name('posts.generate-caption');
    });

Route::middleware(['auth', 'role:customer,admin'])
    ->prefix('admin/google')
    ->name('admin.google.')
    ->group(function () {
        Route::get('settings', [GoogleController::class, 'index'])->name('settings');
        Route::get('connect', [GoogleController::class, 'redirect'])->name('connect');
        Route::post('sync-locations', [GoogleController::class, 'syncLocations'])->name('sync-locations');
        Route::post('locations/{location}/default', [GoogleController::class, 'setDefaultLocation'])->name('locations.default');
    });

Route::middleware(['auth', 'role:customer,admin', 'subscription.active'])
    ->prefix('admin')
    ->name('admin.posts.')
    ->group(function () {
        Route::get('posts', [PostController::class, 'index'])->name('index');
        Route::get('posts/create', [PostController::class, 'create'])->name('create');
        Route::post('posts', [PostController::class, 'store'])->name('store');
        Route::post('posts/drive/images', [PostController::class, 'fetchDriveImages'])->name('drive.images');
        Route::get('posts/drive/image-proxy', [PostController::class, 'proxyDriveImage'])->name('drive.image-proxy');
        Route::post('posts/drive/publish', [PostController::class, 'postDriveImages'])->name('drive.publish');
        Route::put('posts/{id}', [PostController::class, 'update'])->name('update');
        Route::delete('posts/{id}', [PostController::class, 'destroy'])->name('destroy');
        Route::post('posts/bulk-delete', [PostController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

Route::middleware(['auth', 'role:customer,admin', 'subscription.active'])
    ->prefix('admin')
    ->name('admin.scheduled-posts.')
    ->group(function () {
        Route::get('scheduled-posts', [ScheduledPostController::class, 'index'])->name('index');
        Route::post('scheduled-posts', [ScheduledPostController::class, 'store'])->name('store');
        Route::put('scheduled-posts/{id}', [ScheduledPostController::class, 'update'])->name('update');
        Route::delete('scheduled-posts/{id}', [ScheduledPostController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:customer,admin', 'subscription.active'])
    ->prefix('admin')
    ->name('admin.automations.')
    ->group(function () {
        Route::get('automations', [AutomationConfigController::class, 'index'])->name('index');
        Route::get('automations/create', [AutomationConfigController::class, 'create'])->name('create');
        Route::post('automations', [AutomationConfigController::class, 'store'])->name('store');
        Route::get('automations/{automation}/edit', [AutomationConfigController::class, 'edit'])->name('edit');
        Route::put('automations/{automation}', [AutomationConfigController::class, 'update'])->name('update');
        Route::delete('automations/{automation}', [AutomationConfigController::class, 'destroy'])->name('destroy');
        Route::post('automations/{automation}/toggle', [AutomationConfigController::class, 'toggle'])->name('toggle');
        Route::delete('automations/executions/{execution}', [AutomationConfigController::class, 'cancelExecution'])->name('executions.destroy');
        Route::post('automations/executions/{execution}/run-now', [AutomationConfigController::class, 'executeNow'])->name('executions.run-now');
    });

Route::middleware(['auth', 'admin'])
    ->prefix('admin/saas')
    ->name('admin.saas.')
    ->group(function () {
        Route::get('/overview', [SaasManagementController::class, 'overview'])->name('overview');
        Route::get('/users', [SaasManagementController::class, 'users'])->name('users');
        Route::get('/plans', [SaasManagementController::class, 'plans'])->name('plans');
        Route::post('/plans/{plan}/toggle', [SaasManagementController::class, 'togglePlan'])->name('plans.toggle');
        Route::get('/subscriptions', [SaasManagementController::class, 'subscriptions'])->name('subscriptions');
        Route::post('/subscriptions/{subscription}/toggle', [SaasManagementController::class, 'toggleSubscription'])->name('subscriptions.toggle');
        Route::post('/users/{user}/verify-email', [SaasManagementController::class, 'verifyEmail'])->name('users.verify-email');
    });

// Admin auth
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'role:customer,admin'])->group(function () {
        Route::get('/', function () { return view('admin.dashboard'); })->name('dashboard');
        Route::middleware('admin')->group(function () {
            // Modal endpoints for dynamic forms
            Route::get('blogs/create-modal', [AdminBlogController::class, 'createModal'])->name('blogs.createModal');
            Route::get('blogs/{blog}/edit-modal', [AdminBlogController::class, 'editModal'])->name('blogs.editModal');
            // Upload endpoint for images (AJAX)
            Route::post('uploads', [AdminBlogController::class, 'uploadImage'])->name('uploads');
            Route::resource('blogs', AdminBlogController::class);

            Route::get('categories/create-modal', [AdminCategoryController::class, 'createModal'])->name('categories.createModal');
            Route::get('categories/{category}/edit-modal', [AdminCategoryController::class, 'editModal'])->name('categories.editModal');
            Route::resource('categories', AdminCategoryController::class);
        });
    });
});

require __DIR__.'/auth.php';
