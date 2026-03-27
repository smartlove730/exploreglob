<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Models\DriveImagePost;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use App\Services\GoogleDriveService;
use App\Services\GoogleService;
use App\Services\MetaPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(
        private readonly MetaPostService $metaPostService,
        private readonly GoogleDriveService $googleDriveService,
        private readonly GoogleService $googleService
    )
    {
    }

    public function index()
    {
        $posts = FacebookPost::with(['page.facebookAccount.app', 'images'])
            ->whereHas('page.facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->latest()
            ->paginate(20);

        return view('admin.facebook.posts', compact('posts'));
    }

    public function create(Request $request)
    {
        $apps = FacebookApp::where('is_active', true)->orderBy('name')->get();
        $selectedAppId = (int) $request->integer('app_id');

        if ($selectedAppId === 0 && $apps->isNotEmpty()) {
            $selectedAppId = (int) $apps->first()->id;
        }

        $pages = FacebookPage::query()
            ->where('is_active', true)
            ->whereHas('facebookAccount', function ($query) use ($selectedAppId) {
                $query->where('user_id', Auth::id())
                    ->when($selectedAppId > 0, fn ($inner) => $inner->where('facebook_app_id', $selectedAppId));
            })
            ->orderBy('page_name')
            ->get();

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'pages' => $pages,
            'selectedPageId' => (int) old('page_id', $request->integer('page_id')),
            'driveApiKeys' => DriveApiKey::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedDriveApiKeyId' => (int) old('drive_api_key_id', $request->integer('drive_api_key_id')),
            'driveFolders' => DriveFolder::query()->with('driveApiKey')->where('is_active', true)->orderBy('name')->get(),
            'googleAccount' => GoogleAccount::query()->where('user_id', Auth::id())->first(),
            'googleLocations' => GoogleLocation::query()->where('user_id', Auth::id())->orderByDesc('is_default')->orderBy('name')->get(),
            'defaultGoogleLocationId' => old('google_location_id', optional(GoogleLocation::query()->where('user_id', Auth::id())->where('is_default', true)->first())->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePostRequest($request);
        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $googleLocationId = $this->resolveGoogleLocationId($data);

        $post = FacebookPost::create([
            'page_id' => $page->id,
            'message' => $data['message'],
            'image_url' => $data['image_url'] ?? null,
            'platforms' => $data['platforms'],
            'status' => FacebookPost::STATUS_DRAFT,
        ]);

        $this->syncImages($post, $request, []);
        $publishImageUrl = $this->resolvePublishImageUrl($post);

        if (in_array('instagram', $data['platforms'], true) && !$publishImageUrl) {
            throw ValidationException::withMessages(['images' => 'Instagram requires at least one image URL or upload.']);
        }

        try {
            $publishResult = $this->metaPostService->publish($page, $post->message, $publishImageUrl, $data['platforms'], $googleLocationId);

            $post->update([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => now(),
                'facebook_post_id' => $publishResult['facebook_post_id'],
                'instagram_media_id' => $publishResult['instagram_media_id'],
                'google_post_name' => $publishResult['google_post_name'] ?? null,
                'response_json' => $publishResult['response_json'],
                'image_url' => $publishImageUrl,
            ]);

            return redirect()->route('admin.posts.index')->with('success', 'Post created and published successfully.');
        } catch (\Throwable $exception) {
            Log::error('Post publishing failed on create', ['post_id' => $post->id, 'error' => $exception->getMessage()]);

            return redirect()->route('admin.posts.index')->with('error', 'Post saved as draft. Publishing failed: '.$exception->getMessage());
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $post = FacebookPost::with(['images', 'page.facebookAccount'])->findOrFail($id);
        abort_unless($post->page->facebookAccount->user_id === Auth::id(), 403);

        $data = $this->validatePostRequest($request, true);

        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $post->update([
            'page_id' => $page->id,
            'message' => $data['message'],
            'platforms' => $data['platforms'],
            'image_url' => $data['image_url'] ?? $post->image_url,
        ]);

        $this->syncImages($post, $request, $data['remove_images'] ?? []);
        $publishImageUrl = $this->resolvePublishImageUrl($post);
        $googleLocationId = $this->resolveGoogleLocationId($data);

        if ($post->status === FacebookPost::STATUS_PUBLISHED) {
            try {
                $this->metaPostService->deleteFacebookPost($page, $post->facebook_post_id);
            } catch (\Throwable $exception) {
                Log::warning('Failed deleting old Facebook post before update', ['post_id' => $post->id, 'error' => $exception->getMessage()]);
            }

            try {
                $this->metaPostService->deleteInstagramMedia($page, $post->instagram_media_id);
            } catch (\Throwable $exception) {
                Log::warning('Failed deleting old Instagram media before update', ['post_id' => $post->id, 'error' => $exception->getMessage()]);
            }

            if (in_array('instagram', $data['platforms'], true) && !$publishImageUrl) {
                throw ValidationException::withMessages(['images' => 'Instagram requires at least one image URL or upload.']);
            }

            try {
                $publishResult = $this->metaPostService->publish($page, $post->message, $publishImageUrl, $data['platforms'], $googleLocationId);
                $post->update([
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $publishResult['facebook_post_id'],
                    'instagram_media_id' => $publishResult['instagram_media_id'],
                    'google_post_name' => $publishResult['google_post_name'] ?? null,
                    'response_json' => $publishResult['response_json'],
                    'image_url' => $publishImageUrl,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Re-publishing failed on update', ['post_id' => $post->id, 'error' => $exception->getMessage()]);
                $post->update(['status' => FacebookPost::STATUS_DRAFT]);

                return back()->with('error', 'Post updated locally, but re-publishing failed.');
            }
        } else {
            $post->update([
                'status' => FacebookPost::STATUS_DRAFT,
                'image_url' => $publishImageUrl,
            ]);
        }

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $post = FacebookPost::with(['images', 'page.facebookAccount'])->findOrFail($id);
        abort_unless($post->page->facebookAccount->user_id === Auth::id(), 403);

        if ($post->status === FacebookPost::STATUS_PUBLISHED) {
            try {
                $this->metaPostService->deleteFacebookPost($post->page, $post->facebook_post_id);
            } catch (\Throwable $exception) {
                Log::warning('Failed deleting Facebook post during local delete', ['post_id' => $post->id, 'error' => $exception->getMessage()]);
            }

            try {
                $this->metaPostService->deleteInstagramMedia($post->page, $post->instagram_media_id);
            } catch (\Throwable $exception) {
                Log::warning('Failed deleting Instagram media during local delete', ['post_id' => $post->id, 'error' => $exception->getMessage()]);
            }
        }

        foreach ($post->images as $image) {
            if (Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
    }

    public function fetchDriveImages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folder_url' => 'nullable|string|max:2048',
            'folder_id' => 'nullable|integer|exists:drive_folders,id',
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'drive_api_key_id' => 'required|integer|exists:drive_api_keys,id',
        ]);

        if (empty($data['folder_url']) && empty($data['folder_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a folder link or select a saved folder.',
            ], 422);
        }

        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Selected page is not valid for this app/user.',
            ], 422);
        }

        $driveApiKey = DriveApiKey::query()
            ->whereKey((int) $data['drive_api_key_id'])
            ->where('is_active', true)
            ->first();

        if (!$driveApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Selected Google Drive key is invalid or inactive.',
            ], 422);
        }

        $folderUrl = (string) ($data['folder_url'] ?? '');
        if (!empty($data['folder_id'])) {
            $savedFolder = DriveFolder::query()->whereKey((int) $data['folder_id'])->where('is_active', true)->first();
            if (!$savedFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected saved folder is invalid or inactive.',
                ], 422);
            }

            $folderUrl = $savedFolder->folder_url;
        }

        $folderId = $this->googleDriveService->extractFolderId($folderUrl);

        $driveToken = null;
        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $images = $this->googleDriveService->listPublicFolderImages($folderId, $driveApiKey->api_key, $driveToken);

        $postedByImage = DriveImagePost::query()
            ->where('page_id', $page->id)
            ->whereIn('drive_file_id', collect($images)->pluck('id')->all())
            ->get()
            ->groupBy('drive_file_id');

        $driveApiKeyId = (int) $data['drive_api_key_id'];

        $payload = collect($images)->map(function (array $image) use ($postedByImage, $driveApiKeyId) {
            $records = $postedByImage->get($image['id'], collect());
            $postedPlatforms = $records
                ->flatMap(fn ($record) => $record->platforms ?? [])
                ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram'], true))
                ->unique()
                ->values()
                ->all();

            return array_merge($image, [
                'preview_url' => route('admin.posts.drive.image-proxy', [
                    'source_url' => $image['preview_url'],
                    'drive_api_key_id' => $driveApiKeyId,
                    'file_id' => $image['id'],
                    'resource_key' => $image['resource_key'] ?? null,
                ]),
                'is_posted' => !empty($postedPlatforms),
                'posted_platforms' => $postedPlatforms,
            ]);
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'folder_id' => $folderId,
                'images' => $payload,
            ],
        ]);
    }

    public function postDriveImages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'drive_api_key_id' => 'required|integer|exists:drive_api_keys,id',
            'folder_id' => 'nullable|string|max:255',
            'caption' => 'required|string|max:60000',
            'post_mode' => 'nullable|string|in:separate,combined',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram,google_business',
            'google_location_id' => 'nullable|integer|exists:google_locations,id',
            'images' => 'required|array|min:1',
            'images.*.id' => 'required|string|max:255',
            'images.*.url' => 'required|url|max:2048',
            'images.*.resource_key' => 'nullable|string|max:255',
        ]);

        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Selected page is not valid for this app/user.',
            ], 422);
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $postMode = (string) ($data['post_mode'] ?? 'separate');
        $driveApiKey = DriveApiKey::query()
            ->whereKey((int) $data['drive_api_key_id'])
            ->where('is_active', true)
            ->first();

        if (!$driveApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Selected Google Drive key is invalid or inactive.',
            ], 422);
        }

        $preparedImages = [];

        foreach ($data['images'] as $imageData) {
            try {
                $preparedImages[] = $this->storeDriveImageLocally(
                    (string) $imageData['id'],
                    (string) $imageData['url'],
                    (string) ($imageData['resource_key'] ?? ''),
                    $driveApiKey
                );
            } catch (\Throwable $exception) {
                Log::error('Drive image preparation failed', [
                    'file_id' => $imageData['id'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (empty($preparedImages)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid images could be prepared for posting.',
                'data' => ['results' => []],
            ], 422);
        }

        $results = [];

        if ($postMode === 'combined') {
            try {
                $publishResult = $this->metaPostService->publishCombined(
                    $page,
                    $data['caption'],
                    collect($preparedImages)->pluck('public_url')->all(),
                    $platforms
                );

                $post = FacebookPost::create([
                    'page_id' => $page->id,
                    'message' => $data['caption'],
                    'image_url' => $preparedImages[0]['public_url'],
                    'platforms' => $platforms,
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $publishResult['facebook_post_id'] ?? null,
                    'instagram_media_id' => $publishResult['instagram_media_id'] ?? null,
                    'google_post_name' => $publishResult['google_post_name'] ?? null,
                'response_json' => $publishResult['response_json'] ?? null,
                ]);

                foreach ($preparedImages as $imageMeta) {
                    $post->images()->create(['image_path' => $imageMeta['storage_path']]);

                    DriveImagePost::create([
                        'page_id' => $page->id,
                        'drive_file_id' => $imageMeta['file_id'],
                        'drive_folder_id' => $data['folder_id'] ?? null,
                        'image_url' => $imageMeta['public_url'],
                        'caption' => $data['caption'],
                        'platforms' => $platforms,
                        'facebook_post_id' => $publishResult['facebook_post_id'] ?? null,
                        'instagram_media_id' => $publishResult['instagram_media_id'] ?? null,
                        'response_json' => $publishResult['response_json'] ?? null,
                        'posted_at' => now(),
                    ]);

                    $results[] = [
                        'file_id' => $imageMeta['file_id'],
                        'success' => true,
                    ];
                }
            } catch (\Throwable $exception) {
                Log::error('Combined drive image publish failed', ['error' => $exception->getMessage()]);

                foreach ($preparedImages as $imageMeta) {
                    $results[] = [
                        'file_id' => $imageMeta['file_id'],
                        'success' => false,
                        'message' => $exception->getMessage(),
                    ];
                }
            }

            $successCount = collect($results)->where('success', true)->count();

            return response()->json([
                'success' => $successCount > 0,
                'message' => $successCount === count($results)
                    ? 'All images posted successfully in one post.'
                    : "Posted {$successCount} of ".count($results).' images.',
                'data' => ['results' => $results],
            ], $successCount > 0 ? 200 : 422);
        }

        foreach ($preparedImages as $imageMeta) {
            $imageUrl = $imageMeta['public_url'];
            $this->assertPublicHttpsImageUrl($imageUrl);

            try {
                $googleLocationId = $this->resolveGoogleLocationId($data);
                $publishResult = $this->metaPostService->publish($page, $data['caption'], $imageUrl, $platforms, $googleLocationId);

                $post = FacebookPost::create([
                    'page_id' => $page->id,
                    'message' => $data['caption'],
                    'image_url' => $imageUrl,
                    'platforms' => $platforms,
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $publishResult['facebook_post_id'] ?? null,
                    'instagram_media_id' => $publishResult['instagram_media_id'] ?? null,
                    'google_post_name' => $publishResult['google_post_name'] ?? null,
                'response_json' => $publishResult['response_json'] ?? null,
                ]);

                $post->images()->create(['image_path' => $imageMeta['storage_path']]);

                DriveImagePost::create([
                    'page_id' => $page->id,
                    'drive_file_id' => $imageMeta['file_id'],
                    'drive_folder_id' => $data['folder_id'] ?? null,
                    'image_url' => $imageUrl,
                    'caption' => $data['caption'],
                    'platforms' => $platforms,
                    'facebook_post_id' => $publishResult['facebook_post_id'] ?? null,
                    'instagram_media_id' => $publishResult['instagram_media_id'] ?? null,
                    'response_json' => $publishResult['response_json'] ?? null,
                    'posted_at' => now(),
                ]);

                $results[] = [
                    'file_id' => $imageMeta['file_id'],
                    'success' => true,
                ];
            } catch (\Throwable $exception) {
                Log::error('Drive image publish failed', [
                    'page_id' => $page->id,
                    'file_id' => $imageMeta['file_id'],
                    'error' => $exception->getMessage(),
                ]);

                $results[] = [
                    'file_id' => $imageMeta['file_id'],
                    'success' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'success' => $successCount > 0,
            'message' => $successCount === count($results)
                ? 'All images posted successfully.'
                : "Posted {$successCount} of ".count($results).' images.',
            'data' => [
                'results' => $results,
            ],
        ], $successCount > 0 ? 200 : 422);
    }

    public function proxyDriveImage(Request $request)
    {
        $data = $request->validate([
            'source_url' => 'nullable|url|max:4096',
            'file_id' => 'required|string|max:255',
            'drive_api_key_id' => 'required|integer|exists:drive_api_keys,id',
            'resource_key' => 'nullable|string|max:255',
        ]);

        $driveApiKey = DriveApiKey::query()
            ->whereKey((int) $data['drive_api_key_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $driveToken = null;
        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $binary = $this->downloadImageBinaryFromUrl((string) ($data['source_url'] ?? ''));

        if (!$binary) {
            $binary = $this->googleDriveService->fetchImageBinary(
                (string) $data['file_id'],
                (string) $driveApiKey->api_key,
                (string) ($data['resource_key'] ?? ''),
                $driveToken
            );
        }

        return response($binary['content'], 200, [
            'Content-Type' => (string) $binary['content_type'],
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function validatePostRequest(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram,google_business',
            'google_location_id' => 'nullable|integer|exists:google_locations,id',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
            'remove_images' => $isUpdate ? 'nullable|array' : 'nullable',
            'remove_images.*' => 'integer|exists:post_images,id',
        ]);
    }


    private function resolveGoogleLocationId(array $data): ?int
    {
        $platforms = $data['platforms'] ?? [];

        if (!in_array('google_business', $platforms, true)) {
            return null;
        }

        $locationId = (int) ($data['google_location_id'] ?? 0);

        $location = GoogleLocation::query()
            ->where('user_id', Auth::id())
            ->when($locationId > 0, fn ($query) => $query->whereKey($locationId), fn ($query) => $query->where('is_default', true))
            ->first();

        if (!$location) {
            throw ValidationException::withMessages([
                'google_location_id' => 'Select a Google Business location or set a default location first.',
            ]);
        }

        return (int) $location->id;
    }

    private function resolveAuthorizedPage(int $appId, int $pageId): ?FacebookPage
    {
        return FacebookPage::query()
            ->whereKey($pageId)
            ->where('facebook_app_id', $appId)
            ->where('is_active', true)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();
    }

    private function syncImages(FacebookPost $post, Request $request, array $removeIds = []): void
    {
        if (!empty($removeIds)) {
            $post->images()->whereIn('id', $removeIds)->get()->each(function ($image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            });
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $uploadedImage) {
                $path = $uploadedImage->store('posts', 'public');
                $post->images()->create(['image_path' => $path]);
            }
        }

        $post->load('images');
    }

    private function resolvePublishImageUrl(FacebookPost $post): ?string
    {
        if ($post->image_url) {
            return $post->image_url;
        }

        $firstImage = $post->images->first();

        if (!$firstImage) {
            return null;
        }

        return Storage::disk('public')->url($firstImage->image_path);
    }

    private function assertPublicHttpsImageUrl(string $imageUrl): void
    {
        $host = parse_url($imageUrl, PHP_URL_HOST);
        $scheme = parse_url($imageUrl, PHP_URL_SCHEME);

        if ($scheme !== 'https' || !$host) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must be publicly accessible and use HTTPS.',
            ]);
        }

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must not point to localhost.',
            ]);
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must be publicly reachable.',
            ]);
        }
    }

    private function storeDriveImageLocally(string $fileId, string $sourceUrl, string $resourceKey, DriveApiKey $driveApiKey): array
    {
        $driveToken = null;
        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $binary = $this->downloadImageBinaryFromUrl($sourceUrl)
            ?: $this->googleDriveService->fetchImageBinary($fileId, $driveApiKey->api_key, $resourceKey, $driveToken);
        $contentType = (string) ($binary['content_type'] ?? 'image/jpeg');
        $extension = match ($contentType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $storagePath = 'drive-posts/'.$fileId.'-'.uniqid().'.'.$extension;
        Storage::disk('public')->put($storagePath, $binary['content']);

        return [
            'file_id' => $fileId,
            'storage_path' => $storagePath,
            'public_url' => url(Storage::disk('public')->url($storagePath)),
        ];
    }

    private function downloadImageBinaryFromUrl(string $sourceUrl): ?array
    {
        if ($sourceUrl === '') {
            return null;
        }

        $host = parse_url($sourceUrl, PHP_URL_HOST);
        if (!is_string($host) || (!str_contains($host, 'googleusercontent.com') && !str_contains($host, 'google.com'))) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(45)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders(['Accept' => 'image/*'])
                ->get($sourceUrl);

            if (!$response->successful()) {
                return null;
            }

            return [
                'content' => $response->body(),
                'content_type' => $response->header('Content-Type', 'image/jpeg'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
