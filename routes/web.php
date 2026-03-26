<?php

use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\Admin\FacebookAppController;

Route::post('/synccategoryimages', [CategoryController::class, 'syncCategoryImages'])->name('syncCategoryImages');

Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->get('/auth/facebook/callback', [FacebookSettingsController::class, 'callback'])->name('admin.facebook.callback');


Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])
    ->prefix('admin/facebook')
    ->name('admin.facebook.')
    ->group(function () {
        Route::get('settings', [FacebookSettingsController::class, 'index'])->name('settings');
        Route::get('connect', [FacebookSettingsController::class, 'redirectToFacebook'])->name('connect');
        Route::post('sync-pages', [FacebookSettingsController::class, 'syncPages'])->name('sync-pages');
        Route::post('pages/{page}/activate', [FacebookSettingsController::class, 'activatePage'])->name('pages.activate');

        Route::resource('apps', FacebookAppController::class)->except(['show']);

        Route::get('posts', [FacebookPostController::class, 'index'])->name('posts');
        Route::get('posts/create', [FacebookPostController::class, 'create'])->name('posts.create');
        Route::post('posts', [FacebookPostController::class, 'store'])->name('posts.store');
        Route::post('posts/{post}/retry', [FacebookPostController::class, 'retry'])->name('posts.retry');
    });

// Admin auth
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])->group(function () {
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
