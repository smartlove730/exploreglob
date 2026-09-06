<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\ContentCalendarController;
use App\Http\Controllers\App\MediaLibraryController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AutomationConfigController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DriveApiKeyController;
use App\Http\Controllers\Admin\DriveFolderController;
use App\Http\Controllers\Admin\FacebookAppController;
use App\Http\Controllers\Admin\FacebookPostController;
use App\Http\Controllers\Admin\FacebookSettingsController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\SaasManagementController;
use App\Http\Controllers\Admin\ScheduledPostController;
use App\Http\Controllers\Admin\SocialPostManagerController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\RedirectController;

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

Route::middleware(['auth', 'verified'])->get('/dashboard', [RedirectController::class, 'dashboard'])->name('dashboard');

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'verified', 'role:customer,admin'])
    ->group(function () {
        Route::get('/', [RedirectController::class, 'appDashboard'])->name('dashboard');

        Route::get('/billing/plans', [BillingController::class, 'index'])->name('billing.plans');
        Route::post('/billing/subscribe', [BillingController::class, 'startCheckout'])->middleware('throttle:billing-checkout')->name('billing.subscribe');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
        Route::get('/settings', [\App\Http\Controllers\App\SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/password', [\App\Http\Controllers\App\SettingsController::class, 'updatePassword'])->name('settings.password');
        Route::get('/facebook/settings', [FacebookSettingsController::class, 'index'])->name('facebook.settings');
        Route::get('/facebook/connect', [FacebookSettingsController::class, 'redirectToFacebook'])->name('facebook.connect');
        Route::post('/facebook/sync-pages', [FacebookSettingsController::class, 'syncPages'])->name('facebook.sync-pages');

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
Route::get('/categories', [RedirectController::class, 'categories'])->name('categories.index');
Route::get('/category/{slug}', [RedirectController::class, 'category'])->name('category.show');

// Blogs
Route::get('/blogs/{id}', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/travel/{subcategory}/{slug}', [BlogController::class, 'showByCategory'])->name('travel.blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/newsletter/subscribe', [NewsletterSubscriptionController::class, 'store'])->name('newsletter.subscribe');

Route::middleware(['auth', 'admin'])
    ->post('/tools/convert-category-images', [ImageConversionController::class, 'convertCategoryImages'])
    ->name('tools.convert-category-images');

// Legacy aliases for existing public pages
Route::redirect('/policy', '/privacy-policy', 301)->name('policy');
Route::redirect('/terms', '/terms-and-conditions', 301)->name('terms');
Route::redirect('/data-deletion', '/data-deletion-instructions', 301)->name('data-deletion');
Route::redirect('/about-us', '/about', 301)->name('about.alternate');
Route::post('/addblog', [BlogController::class, 'store'])->name('store');
Route::post('/genimage', [BlogController::class, 'genImage'])->name('genImage');

// Admin routes (simple Blade-based admin)

Route::middleware(['auth', 'admin'])->post('/synccategoryimages', [CategoryController::class, 'syncCategoryImages'])->name('syncCategoryImages');
Route::middleware(['auth'])->get('/auth/facebook/callback', [FacebookSettingsController::class, 'callback'])->name('oauth.facebook.callback');
Route::middleware(['auth', 'role:customer,admin'])->get('/auth/google/drive/connect', [DriveApiKeyController::class, 'redirectToGoogleOauth'])->name('admin.google-drive.connect');
Route::middleware(['auth', 'role:customer,admin'])->get('/auth/google/drive/callback', [DriveApiKeyController::class, 'callback'])->name('admin.google-drive.callback');


Route::middleware(['auth', 'role:customer,admin'])
    ->prefix('admin/facebook')
    ->name('admin.facebook.')
    ->group(function () {
        Route::get('settings', [FacebookSettingsController::class, 'index'])->name('settings');
        Route::get('connect', [FacebookSettingsController::class, 'redirectToFacebook'])->name('connect');
        Route::post('refresh-token', [FacebookSettingsController::class, 'refreshToken'])->name('refresh-token');
        Route::post('sync-pages', [FacebookSettingsController::class, 'syncPages'])->name('sync-pages');
        Route::get('pages/{page}', [FacebookSettingsController::class, 'pageDetails'])->name('pages.details');
        Route::delete('pages/{page}', [FacebookSettingsController::class, 'removePage'])->name('pages.destroy');
        Route::post('pages/{page}/activate', [FacebookSettingsController::class, 'activatePage'])->name('pages.activate');

        Route::resource('apps', FacebookAppController::class)->except(['show']);
        Route::resource('google-drive-keys', DriveApiKeyController::class)->except(['show']);
        Route::resource('drive-folders', DriveFolderController::class)->except(['show']);
        Route::post('drive-folders/sync', [DriveFolderController::class, 'sync'])->name('drive-folders.sync');
        Route::post('drive-folders/bulk-status', [DriveFolderController::class, 'bulkStatus'])->name('drive-folders.bulk-status');

        Route::post('posts/generate-caption', [FacebookPostController::class, 'generateCaption'])->name('posts.generate-caption');
        Route::get('manage-posts', [SocialPostManagerController::class, 'index'])->name('manage-posts.index');
        Route::post('manage-posts/sync', [SocialPostManagerController::class, 'syncPosts'])->name('manage-posts.sync');
        Route::post('manage-posts/list', [SocialPostManagerController::class, 'listPosts'])->name('manage-posts.list');
        Route::post('manage-posts/delete', [SocialPostManagerController::class, 'deletePosts'])->name('manage-posts.delete');
        Route::post('manage-posts/retry-failed', [SocialPostManagerController::class, 'retryFailed'])->name('manage-posts.retry-failed');
        Route::get('manage-posts/statuses', [SocialPostManagerController::class, 'statuses'])->name('manage-posts.statuses');
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
        Route::post('posts/{id}/execute-now', [PostController::class, 'executeNow'])->name('execute-now');
        Route::post('posts/{id}/retry', [PostController::class, 'retry'])->name('retry');
        Route::put('posts/{id}', [PostController::class, 'update'])->name('update');
        Route::delete('posts/{id}', [PostController::class, 'destroy'])->name('destroy');
        Route::post('posts/bulk-delete', [PostController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('posts/bulk-retry', [PostController::class, 'bulkRetry'])->name('bulk-retry');
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
        Route::get('automations/drive-folders', [AutomationConfigController::class, 'driveFolders'])->name('drive-folders');
        Route::get('automations/create', [AutomationConfigController::class, 'create'])->name('create');
        Route::post('automations', [AutomationConfigController::class, 'store'])->name('store');
        Route::get('automations/{automation}/edit', [AutomationConfigController::class, 'edit'])->name('edit');
        Route::put('automations/{automation}', [AutomationConfigController::class, 'update'])->name('update');
        Route::delete('automations/{automation}', [AutomationConfigController::class, 'destroy'])->name('destroy');
        Route::post('automations/{automation}/pause', [AutomationConfigController::class, 'pause'])->name('pause');
        Route::post('automations/{automation}/resume', [AutomationConfigController::class, 'resume'])->name('resume');
        Route::post('automations/{automation}/stop', [AutomationConfigController::class, 'stop'])->name('stop');
        Route::post('automations/{automation}/queue-now', [AutomationConfigController::class, 'queueNow'])->name('queue-now');
        Route::delete('automations/queue-item/{queueItem}', [AutomationConfigController::class, 'deleteQueueItem'])->name('queue-item.delete');
        Route::post('automations/queue-item/{queueItem}/execute', [AutomationConfigController::class, 'executeQueueItemNow'])->name('queue-item.execute');
    });

Route::middleware(['auth', 'admin'])
    ->prefix('admin/saas')
    ->name('admin.saas.')
    ->group(function () {
        Route::get('/overview', [SaasManagementController::class, 'overview'])->name('overview');
        Route::get('/users', [SaasManagementController::class, 'users'])->name('users');
        Route::get('/plans/create', [SaasManagementController::class, 'createPlan'])->name('plans.create');
        Route::get('/plans', [SaasManagementController::class, 'plans'])->name('plans');
        Route::post('/plans', [SaasManagementController::class, 'storePlan'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [SaasManagementController::class, 'editPlan'])->name('plans.edit');
        Route::put('/plans/{plan}', [SaasManagementController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{plan}', [SaasManagementController::class, 'destroyPlan'])->name('plans.destroy');
        Route::post('/plans/{plan}/toggle', [SaasManagementController::class, 'togglePlan'])->name('plans.toggle');
        Route::get('/subscriptions', [SaasManagementController::class, 'subscriptions'])->name('subscriptions');
        Route::post('/subscriptions/{subscription}/toggle', [SaasManagementController::class, 'toggleSubscription'])->name('subscriptions.toggle');
        Route::post('/users/{user}/verify-email', [SaasManagementController::class, 'verifyEmail'])->name('users.verify-email');
        Route::post('/users/{user}/toggle-whatsapp', [SaasManagementController::class, 'toggleWhatsappAccess'])->name('users.toggle-whatsapp');
    });
// Admin auth
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'role:customer,admin'])->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::middleware('admin')->group(function () {
            Route::get('mail-settings', [MailSettingsController::class, 'index'])->name('mail-settings.index');
            Route::put('mail-settings', [MailSettingsController::class, 'update'])->name('mail-settings.update');
            Route::post('mail-settings/test', [MailSettingsController::class, 'test'])->name('mail-settings.test');
            Route::delete('mail-settings/logs/{log}', [MailSettingsController::class, 'destroyLog'])->name('mail-settings.logs.destroy');

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

// WhatsApp Business API (frontend-only for now)
Route::middleware(['auth', 'role:customer,admin'])
    ->prefix('admin/whatsapp')
    ->name('admin.whatsapp.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\WhatsappDashboardController::class, 'index'])->name('dashboard');
        
        // Phone Numbers - Meta Embedded Signup (1 account = 1 number)
        Route::get('/phone-numbers', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'index'])->name('phone-numbers');
        Route::post('/phone-numbers/embedded-signup', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'embeddedSignup'])->name('phone-numbers.embedded-signup');
        Route::post('/phone-numbers/sync', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'syncStatus'])->name('phone-numbers.sync');
        Route::post('/phone-numbers/disconnect', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'disconnect'])->name('phone-numbers.disconnect');
        Route::put('/phone-numbers/profile', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'updateProfile'])->name('phone-numbers.profile.update');
        
        // Manual Registration Routes
        Route::post('/phone-numbers/register', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'register'])->name('phone-numbers.register');
        Route::post('/phone-numbers/request-code', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'requestCode'])->name('phone-numbers.request-code');
        Route::post('/phone-numbers/verify-code', [\App\Http\Controllers\Admin\WhatsappPhoneNumberController::class, 'verifyCode'])->name('phone-numbers.verify-code');

        Route::get('/templates', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'index'])->name('templates');
        Route::get('/templates/create', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'store'])->name('templates.store');
        Route::post('/templates/send', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'send'])->name('templates.send');
        Route::get('/reports', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'reports'])->name('reports');
        
        Route::get('/campaigns', [\App\Http\Controllers\Admin\WhatsappCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [\App\Http\Controllers\Admin\WhatsappCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [\App\Http\Controllers\Admin\WhatsappCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}/export', [\App\Http\Controllers\Admin\WhatsappCampaignController::class, 'export'])->name('campaigns.export');
        
        Route::delete('/templates/{template}', [\App\Http\Controllers\Admin\WhatsappTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::get('/contacts', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'index'])->name('contacts');
        Route::post('/contacts', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/sample', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'downloadSample'])->name('contacts.sample');
        Route::post('/contacts/groups', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'storeGroup'])->name('contacts.groups.store');
        Route::put('/contacts/groups/{group}', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'updateGroup'])->name('contacts.groups.update');
        Route::delete('/contacts/groups/{group}', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'destroyGroup'])->name('contacts.groups.destroy');
        Route::get('/contacts/groups/{group}/export', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'exportGroup'])->name('contacts.groups.export');
        Route::post('/contacts/bulk-groups', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'bulkAddGroups'])->name('contacts.bulk-groups');
        Route::put('/contacts/{contact}', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('/contacts/{id}/restore', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'restoreContact'])->name('contacts.restore');
        Route::delete('/contacts/{id}/force', [\App\Http\Controllers\Admin\WhatsappContactController::class, 'forceDeleteContact'])->name('contacts.forceDelete');
        Route::view('/conversations', 'admin.whatsapp.conversations')->name('conversations');
        Route::get('/settings', [\App\Http\Controllers\Admin\WhatsappSettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [\App\Http\Controllers\Admin\WhatsappSettingsController::class, 'update'])->name('settings.update');
        
        // API routes for real-time chat frontend
        Route::get('/api/conversations/updates', [\App\Http\Controllers\Admin\WhatsappFrontendController::class, 'checkUpdates'])->name('api.conversations.updates');
        Route::get('/api/conversations', [\App\Http\Controllers\Admin\WhatsappFrontendController::class, 'getConversations'])->name('api.conversations.index');
        Route::get('/api/conversations/{conversation}/messages', [\App\Http\Controllers\Admin\WhatsappFrontendController::class, 'getMessages'])->name('api.conversations.messages');
        Route::post('/api/conversations/{conversation}/messages', [\App\Http\Controllers\Admin\WhatsappFrontendController::class, 'sendMessage'])->name('api.conversations.send');
        Route::post('/api/conversations/{conversation}/react', [\App\Http\Controllers\Admin\WhatsappFrontendController::class, 'sendReaction'])->name('api.conversations.react');
    });

require __DIR__.'/auth.php';

// Webhook endpoints for WhatsApp message received
Route::get('/whatsapp/message/recieved', [\App\Http\Controllers\WhatsappController::class, 'verify']);
Route::post('/whatsapp/message/recieved', [\App\Http\Controllers\WhatsappController::class, 'handleWebhook']);
