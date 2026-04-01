<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishPostJob;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Models\DriveImagePost;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\GoogleAccount;
use App\Models\GoogleLocation;
use App\Services\DriveService;
use App\Services\GoogleDriveService;
use App\Services\GoogleService;
use App\Services\MetaPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(
        private readonly MetaPostService $metaPostService,
        private readonly GoogleDriveService $googleDriveService,
        private readonly GoogleService $googleService,
        private readonly DriveService $driveService,
    )
    {
    }

    public function index()
    {
        $posts = FacebookPost::query()->ownedBy(Auth::user())
            ->with(['page.facebookAccount.app', 'images'])
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

        $pages = FacebookPage::query()->ownedBy(Auth::user())
            ->where('is_active', true)
            ->when($selectedAppId > 0, fn ($query) => $query->where('facebook_app_id', $selectedAppId))
            ->orderBy('page_name')
            ->get();

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'pages' => $pages,
            'selectedPageId' => (int) old('page_id', $request->integer('page_id')),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedDriveApiKeyId' => (int) old('drive_api_key_id', $request->integer('drive_api_key_id')),
            'driveFolders' => DriveFolder::query()->ownedBy(Auth::user())->with('driveApiKey')->where('is_active', true)->orderBy('name')->get(),
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

        $mediaType = $data['media_type'] ?? FacebookPost::MEDIA_TYPE_IMAGE;
        if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO && in_array('google_business', $data['platforms'], true)) {
            throw ValidationException::withMessages([
                'platforms' => 'Google Business posting currently supports image posts only.',
            ]);
        }

        $post = FacebookPost::create([
            'user_id' => $page->user_id,
            'page_id' => $page->id,
            'message' => $data['message'],
            'media_type' => $mediaType,
            'image_url' => $data['image_url'] ?? null,
            'video_path' => null,
            'video_url' => null,
            'platforms' => $data['platforms'],
            'google_location_id' => $googleLocationId,
            'status' => FacebookPost::STATUS_PENDING,
            'last_error' => null,
        ]);

        if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO) {
            $videoMeta = $this->storeVideoAndResolveUrl($request);

            $post->update([
                'video_path' => $videoMeta['video_path'],
                'video_url' => $videoMeta['video_url'],
                'image_url' => null,
            ]);

            PublishPostJob::dispatch($post->id);

            return redirect()->route('admin.posts.index')->with('success', 'Video queued for background publishing.');
        }

        $this->syncImages($post, $request, []);
        $publishImageUrl = $this->resolvePublishImageUrl($post);
        $publishImageUrl = $this->ensureInstagramEligibleImage($publishImageUrl, $data['platforms']);

        if (in_array('instagram', $data['platforms'], true) && !$publishImageUrl) {
            throw ValidationException::withMessages(['images' => 'Instagram requires at least one image URL or upload.']);
        }

        $post->update([
            'image_url' => $publishImageUrl,
            'google_location_id' => $googleLocationId,
            'status' => FacebookPost::STATUS_PENDING,
            'last_error' => null,
        ]);

        PublishPostJob::dispatch($post->id);

        return redirect()->route('admin.posts.index')->with('success', 'Post queued for background publishing.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $post = FacebookPost::query()->ownedBy(Auth::user())->with(['images', 'page.facebookAccount'])->findOrFail($id);

        $data = $this->validatePostRequest($request, true);

        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);
        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $post->update([
            'page_id' => $page->id,
            'message' => $data['message'],
            'platforms' => $data['platforms'],
            'media_type' => $data['media_type'] ?? $post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE,
            'image_url' => $data['image_url'] ?? $post->image_url,
            'video_url' => $data['video_url'] ?? $post->video_url,
        ]);

        if (($post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO
            && in_array('google_business', $data['platforms'], true)) {
            throw ValidationException::withMessages([
                'platforms' => 'Google Business posting currently supports image posts only.',
            ]);
        }

        if (($post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO && $request->hasFile('video')) {
            $videoMeta = $this->storeVideoAndResolveUrl($request);

            $post->update([
                'video_path' => $videoMeta['video_path'],
                'video_url' => $videoMeta['video_url'],
                'image_url' => null,
            ]);
        }

        $this->syncImages($post, $request, $data['remove_images'] ?? []);
        $publishImageUrl = $this->resolvePublishImageUrl($post);
        $publishImageUrl = $this->ensureInstagramEligibleImage($publishImageUrl, $data['platforms']);
        $googleLocationId = $this->resolveGoogleLocationId($data);

        $isImagePost = ($post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_IMAGE;
        $isVideoPost = ($post->media_type ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO;

        if ($isVideoPost && !$post->video_url) {
            throw ValidationException::withMessages(['video' => 'Please upload an MP4 video file or provide a valid video URL.']);
        }

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

            if ($isImagePost && in_array('instagram', $data['platforms'], true) && !$publishImageUrl) {
                throw ValidationException::withMessages(['images' => 'Instagram requires at least one image URL or upload.']);
            }
        }

        $post->update([
            'status' => FacebookPost::STATUS_PENDING,
            'image_url' => $isImagePost ? $publishImageUrl : null,
            'google_location_id' => $googleLocationId,
            'facebook_post_id' => null,
            'instagram_media_id' => null,
            'google_post_name' => null,
            'posted_at' => null,
            'response_json' => null,
            'last_error' => null,
        ]);

        PublishPostJob::dispatch($post->id);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated and queued for publishing.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $post = FacebookPost::query()->ownedBy(Auth::user())->with(['images', 'page.facebookAccount'])->findOrFail($id);

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

        $driveApiKey = DriveApiKey::query()->ownedBy(Auth::user())
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
            $savedFolder = DriveFolder::query()->ownedBy(Auth::user())->whereKey((int) $data['folder_id'])->where('is_active', true)->first();
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

        $postedByImage = DriveImagePost::query()->ownedBy(Auth::user())
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
        $driveApiKey = DriveApiKey::query()->ownedBy(Auth::user())
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
            $queueImageUrls = collect($preparedImages)
                ->map(fn (array $imageMeta) => $this->ensureInstagramEligibleImage($imageMeta['public_url'], $platforms))
                ->all();
            foreach ($queueImageUrls as $queueImageUrl) {
                if ($queueImageUrl) {
                    $this->assertPublicHttpsImageUrl($queueImageUrl);
                }
            }

            $googleLocationId = $this->resolveGoogleLocationId($data);
            $post = FacebookPost::create([
                'user_id' => $page->user_id,
                'page_id' => $page->id,
                'message' => $data['caption'],
                'image_url' => $queueImageUrls[0] ?? null,
                'platforms' => $platforms,
                'google_location_id' => $googleLocationId,
                'status' => FacebookPost::STATUS_PENDING,
                'last_error' => null,
            ]);

            foreach ($preparedImages as $imageMeta) {
                $post->images()->create(['user_id' => $page->user_id, 'image_path' => $imageMeta['storage_path']]);

                DriveImagePost::create([
                    'user_id' => $page->user_id,
                    'page_id' => $page->id,
                    'drive_file_id' => $imageMeta['file_id'],
                    'drive_folder_id' => $data['folder_id'] ?? null,
                    'image_url' => $imageMeta['public_url'],
                    'caption' => $data['caption'],
                    'platforms' => $platforms,
                    'response_json' => ['status' => 'queued', 'facebook_post_record_id' => $post->id],
                ]);

                $results[] = [
                    'file_id' => $imageMeta['file_id'],
                    'success' => true,
                    'message' => 'Queued for publishing.',
                ];
            }

            PublishPostJob::dispatch($post->id);

            $successCount = count($results);

            return response()->json([
                'success' => $successCount > 0,
                'message' => $successCount === count($results)
                    ? 'Combined post queued for background publishing.'
                    : "Queued {$successCount} of ".count($results).' images.',
                'data' => ['results' => $results],
            ], $successCount > 0 ? 200 : 422);
        }

        foreach ($preparedImages as $imageMeta) {
            $imageUrl = $imageMeta['public_url'];
            $imageUrl = $this->ensureInstagramEligibleImage($imageUrl, $platforms);
            $this->assertPublicHttpsImageUrl($imageUrl);

            try {
                $googleLocationId = $this->resolveGoogleLocationId($data);
                $post = FacebookPost::create([
                    'user_id' => $page->user_id,
                    'page_id' => $page->id,
                    'message' => $data['caption'],
                    'image_url' => $imageUrl,
                    'platforms' => $platforms,
                    'google_location_id' => $googleLocationId,
                    'status' => FacebookPost::STATUS_PENDING,
                    'last_error' => null,
                ]);

                $post->images()->create(['user_id' => $page->user_id, 'image_path' => $imageMeta['storage_path']]);

                DriveImagePost::create([
                    'user_id' => $page->user_id,
                    'page_id' => $page->id,
                    'drive_file_id' => $imageMeta['file_id'],
                    'drive_folder_id' => $data['folder_id'] ?? null,
                    'image_url' => $imageUrl,
                    'caption' => $data['caption'],
                    'platforms' => $platforms,
                    'response_json' => ['status' => 'queued', 'facebook_post_record_id' => $post->id],
                ]);

                PublishPostJob::dispatch($post->id);

                $results[] = [
                    'file_id' => $imageMeta['file_id'],
                    'success' => true,
                    'message' => 'Queued for publishing.',
                ];
            } catch (\Throwable $exception) {
                Log::error('Drive image queue failed', [
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
                ? 'All images queued for background publishing.'
                : "Queued {$successCount} of ".count($results).' images.',
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

        $driveApiKey = DriveApiKey::query()->ownedBy(Auth::user())
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
            'media_type' => 'nullable|string|in:image,video',
            'image_url' => 'nullable|url|max:2048',
            'video' => 'nullable|file|mimes:mp4|max:51200',
            'video_url' => 'nullable|url|max:2048',
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
        return FacebookPage::query()->ownedBy(Auth::user())
            ->whereKey($pageId)
            ->where('facebook_app_id', $appId)
            ->where('is_active', true)
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
                $post->images()->create(['user_id' => $post->user_id, 'image_path' => $path]);
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

    private function ensureInstagramEligibleImage(?string $imageUrl, array $platforms): ?string
    {
        if (!$imageUrl || !in_array('instagram', $platforms, true)) {
            return $imageUrl;
        }

        try {
            return $this->driveService->prepareInstagramEligibleFromUrl($imageUrl);
        } catch (\Throwable $exception) {
            Log::warning('Unable to normalize image for Instagram in manual flow; using original URL.', [
                'image_url' => $imageUrl,
                'error' => $exception->getMessage(),
            ]);

            return $imageUrl;
        }
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

    private function storeVideoAndResolveUrl(Request $request): array
    {
        $video = $request->file('video');

        if (!$video) {
            throw ValidationException::withMessages([
                'video' => 'Please upload an MP4 video file.',
            ]);
        }

        $extension = strtolower((string) $video->getClientOriginalExtension());
        if ($extension !== 'mp4') {
            throw ValidationException::withMessages([
                'video' => 'Only MP4 videos are allowed.',
            ]);
        }

        $videoPath = $video->storeAs('posts/videos', Str::uuid()->toString().'.mp4', 'public');
        $videoUrl = url(Storage::disk('public')->url($videoPath));

        return [
            'video_path' => $videoPath,
            'video_url' => $videoUrl,
        ];
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
