<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\App\BillingController;

use App\Http\Controllers\{
    HomeController,
    CategoryController,
    BlogController,
    PageController,
    CountryController,
    NewsletterSubscriptionController,
    ImageConversionController
};

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home/categories/load', [HomeController::class, 'loadCategories'])->name('home.categories.load');
Route::get('/search', [HomeController::class, 'search'])->name('home.search');

Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect()->route('app.dashboard');
})->name('dashboard');

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'verified', 'role:customer,admin'])
    ->group(function () {
        Route::get('/', function () {
            return view('app.dashboard');
        })->name('dashboard');

        Route::get('/billing/plans', [BillingController::class, 'index'])->name('billing.plans');
        Route::post('/billing/subscribe', [BillingController::class, 'startCheckout'])->name('billing.subscribe');
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

// Static Pages
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact/submit', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/policy', [PageController::class, 'policy'])->name('policy');
Route::get('/privacy-policy', [PageController::class, 'policy'])->name('policy.alternate');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/terms-and-conditions', [PageController::class, 'terms'])->name('terms.alternate');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/about-us', [PageController::class, 'about'])->name('about.alternate');
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
use App\Http\Controllers\AutomationController;

Route::middleware(['auth', 'admin'])->post('/synccategoryimages', [CategoryController::class, 'syncCategoryImages'])->name('syncCategoryImages');
Route::middleware(['auth', 'admin'])->get('/run-automations/{automationConfigId?}', [AutomationController::class, 'run'])->name('automations.run');

Route::middleware(['auth', 'admin'])->get('/auth/facebook/callback', [FacebookSettingsController::class, 'callback'])->name('admin.facebook.callback');
Route::middleware(['auth', 'admin'])->get('/auth/google/callback', [GoogleController::class, 'callback'])->name('admin.google.callback');
Route::middleware(['auth', 'admin'])->get('/auth/google/drive/callback', [DriveApiKeyController::class, 'callback'])->name('admin.google-drive.callback');


Route::middleware(['auth', 'admin'])
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

Route::middleware(['auth', 'admin'])
    ->prefix('admin/google')
    ->name('admin.google.')
    ->group(function () {
        Route::get('settings', [GoogleController::class, 'index'])->name('settings');
        Route::get('connect', [GoogleController::class, 'redirect'])->name('connect');
        Route::post('sync-locations', [GoogleController::class, 'syncLocations'])->name('sync-locations');
        Route::post('locations/{location}/default', [GoogleController::class, 'setDefaultLocation'])->name('locations.default');
    });

Route::middleware(['auth', 'admin'])
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
    });

Route::middleware(['auth', 'admin'])
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
    });

// Admin auth
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', function () { return view('admin.dashboard'); })->name('dashboard');
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

require __DIR__.'/auth.php';
