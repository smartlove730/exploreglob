<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishPostJob;
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
use Illuminate\Support\Collection;
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
        $apps = FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();
        $selectedAppId = (int) $request->integer('app_id');

        if ($selectedAppId === 0 && $apps->isNotEmpty()) {
            $selectedAppId = (int) $apps->first()->id;
        }

        $pages = FacebookPage::query()->ownedBy(Auth::user())
            ->where('is_active', true)
            ->when($selectedAppId > 0, fn ($query) => $query->where('facebook_app_id', $selectedAppId))
            ->orderBy('page_name')
            ->get();

        $googleAccount = GoogleAccount::query()->where('user_id', Auth::id())->first();

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'pages' => $pages,
            'selectedPageIds' => collect(old('page_ids', array_filter([(int) old('page_id', $request->integer('page_id'))])))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all(),
            'driveFolders' => DriveFolder::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'googleAccount' => $googleAccount,
            'googleLocations' => GoogleLocation::query()->where('user_id', Auth::id())->orderByDesc('is_default')->orderBy('name')->get(),
            'defaultGoogleLocationId' => old('google_location_id', optional(GoogleLocation::query()->where('user_id', Auth::id())->where('is_default', true)->first())->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePostRequest($request);
        $pages = $this->resolveAuthorizedPages((int) $data['app_id'], $data['page_ids'] ?? []);
        if ($pages->isEmpty()) {
            return back()->withInput()->with('error', 'Select at least one valid page for this app/user.');
        }

        $googleLocationId = $this->resolveGoogleLocationId($data);

        $mediaType = $data['media_type'] ?? FacebookPost::MEDIA_TYPE_IMAGE;
        if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO && in_array('google_business', $data['platforms'], true)) {
            throw ValidationException::withMessages([
                'platforms' => 'Google Business posting currently supports image posts only.',
            ]);
        }

        if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO) {
            $videoMeta = $this->storeVideoAndResolveUrl($request);
        }

        $createdCount = 0;
        foreach ($pages as $page) {
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
                $post->update([
                    'video_path' => $videoMeta['video_path'],
                    'video_url' => $videoMeta['video_url'],
                    'image_url' => null,
                ]);

                PublishPostJob::dispatch($post->id);
                $createdCount++;
                continue;
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
            $createdCount++;
        }

        return redirect()->route('admin.posts.index')->with('success', $createdCount.' post(s) queued for background publishing.');
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
            'page_id' => 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
        ]);

        if (empty($data['folder_url']) && empty($data['folder_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a folder link or select a saved folder.',
            ], 422);
        }

        $pages = $this->resolveAuthorizedPages((int) $data['app_id'], $data['page_ids'] ?? [$data['page_id'] ?? null]);
        if ($pages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected pages are not valid for this app/user.',
            ], 422);
        }

        $googleAccount = GoogleAccount::query()->where('user_id', Auth::id())->first();
        if (!$googleAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Google account is not connected. Please connect via OAuth first.',
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
        $folderResourceKey = $this->googleDriveService->extractFolderResourceKey($folderUrl);

        $googleAccount = $this->googleService->ensureValidGoogleAccountToken($googleAccount);
        $driveToken = $googleAccount->access_token;

        $mediaItems = $this->googleDriveService->listPublicFolderMedia(
            $folderId,
            null,
            $driveToken,
            $folderResourceKey
        )->all();

        $postedByImage = DriveImagePost::query()->ownedBy(Auth::user())
            ->whereIn('page_id', $pages->pluck('id')->all())
            ->whereIn('drive_file_id', collect($mediaItems)->pluck('id')->all())
            ->get()
            ->groupBy('drive_file_id');

        $payload = collect($mediaItems)->map(function (array $media) use ($postedByImage) {
            $records = $postedByImage->get($media['id'], collect());
            $postedPlatforms = $records
                ->flatMap(fn ($record) => $record->platforms ?? [])
                ->filter(fn ($platform) => in_array($platform, ['facebook', 'instagram'], true))
                ->unique()
                ->values()
                ->all();

            return array_merge($media, [
                'preview_url' => (string) ($media['thumbnail_url'] ?: $media['preview_url']),
                'is_posted' => !empty($postedPlatforms),
                'posted_platforms' => $postedPlatforms,
            ]);
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'folder_id' => $folderId,
                'media' => $payload,
                'images' => $payload,
            ],
        ]);
    }

    public function postDriveImages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
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
            'images.*.mime_type' => 'nullable|string|max:120',
        ]);

        $pages = $this->resolveAuthorizedPages((int) $data['app_id'], $data['page_ids'] ?? [$data['page_id'] ?? null]);
        if ($pages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected pages are not valid for this app/user.',
            ], 422);
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();
        $postMode = (string) ($data['post_mode'] ?? 'separate');
        $googleAccount = GoogleAccount::query()->where('user_id', Auth::id())->first();
        if (!$googleAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Google account is not connected. Please connect via OAuth first.',
            ], 422);
        }

        $preparedMedia = [];

        foreach ($data['images'] as $imageData) {
            try {
                $preparedMedia[] = $this->storeDriveFileLocally(
                    (string) $imageData['id'],
                    (string) $imageData['url'],
                    (string) ($imageData['resource_key'] ?? ''),
                    (string) ($imageData['mime_type'] ?? ''),
                    $googleAccount
                );
            } catch (\Throwable $exception) {
                Log::error('Drive image preparation failed', [
                    'file_id' => $imageData['id'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (empty($preparedMedia)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid media files could be prepared for posting.',
                'data' => ['results' => []],
            ], 422);
        }

        $results = [];
        $requestedMetaPlatforms = collect($platforms)
            ->filter(fn (string $platform) => in_array($platform, ['facebook', 'instagram'], true))
            ->values()
            ->all();

        $existingPublishedPlatforms = $this->resolveExistingDrivePublishedPlatforms($pages->pluck('id')->all(), $preparedMedia);

        $containsVideo = collect($preparedMedia)->contains(fn (array $item) => ($item['media_type'] ?? FacebookPost::MEDIA_TYPE_IMAGE) === FacebookPost::MEDIA_TYPE_VIDEO);
        if ($containsVideo && $postMode === 'combined') {
            return response()->json([
                'success' => false,
                'message' => 'Combined posting supports images only. Please use separate mode for videos/reels.',
                'data' => ['results' => []],
            ], 422);
        }

        if ($containsVideo && in_array('google_business', $platforms, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Google Business does not support video/reel publishing in this flow.',
                'data' => ['results' => []],
            ], 422);
        }

        if ($postMode === 'combined') {
            $googleLocationId = $this->resolveGoogleLocationId($data);
            foreach ($pages as $page) {
                $publishableMedia = [];
                foreach ($preparedMedia as $imageMeta) {
                    $alreadyPublishedPlatforms = $this->resolvePreviouslyPublishedPlatformsForFile(
                        $existingPublishedPlatforms,
                        (int) $page->id,
                        (string) $imageMeta['file_id']
                    );

                    if (!empty(array_intersect($requestedMetaPlatforms, $alreadyPublishedPlatforms))) {
                        $results[] = [
                            'page_id' => $page->id,
                            'file_id' => $imageMeta['file_id'],
                            'success' => false,
                            'message' => 'Skipped: media is already published on '.implode(', ', $alreadyPublishedPlatforms).'.',
                            'skipped' => true,
                        ];
                        continue;
                    }

                    $publishableMedia[] = $imageMeta;
                }

                if (empty($publishableMedia)) {
                    continue;
                }

                $queueImageUrls = collect($publishableMedia)
                    ->map(fn (array $imageMeta) => $this->ensureInstagramEligibleImage($imageMeta['public_url'], $platforms))
                    ->all();
                foreach ($queueImageUrls as $queueImageUrl) {
                    if ($queueImageUrl) {
                        $this->assertPublicHttpsImageUrl($queueImageUrl);
                    }
                }

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

                foreach ($publishableMedia as $imageMeta) {
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
                        'page_id' => $page->id,
                        'file_id' => $imageMeta['file_id'],
                        'success' => true,
                        'message' => 'Queued for publishing.',
                    ];
                }

                PublishPostJob::dispatch($post->id);
            }

            $successCount = count($results);

            return response()->json([
                'success' => $successCount > 0,
                'message' => $successCount === count($results)
                ? 'Combined post queued for background publishing.'
                    : "Queued {$successCount} of ".count($results).' media files.',
                'data' => ['results' => $results],
            ], $successCount > 0 ? 200 : 422);
        }

        foreach ($pages as $page) {
            foreach ($preparedMedia as $imageMeta) {
                $alreadyPublishedPlatforms = $this->resolvePreviouslyPublishedPlatformsForFile(
                    $existingPublishedPlatforms,
                    (int) $page->id,
                    (string) $imageMeta['file_id']
                );
                $platformsToPublish = array_values(array_diff($platforms, $alreadyPublishedPlatforms));

                if (empty($platformsToPublish)) {
                    $results[] = [
                        'page_id' => $page->id,
                        'file_id' => $imageMeta['file_id'],
                        'success' => false,
                        'message' => 'Skipped: media is already published on selected platform(s).',
                        'skipped' => true,
                    ];
                    continue;
                }

                $mediaType = $imageMeta['media_type'] ?? FacebookPost::MEDIA_TYPE_IMAGE;
                $imageUrl = $mediaType === FacebookPost::MEDIA_TYPE_IMAGE
                    ? $this->ensureInstagramEligibleImage($imageMeta['public_url'], $platformsToPublish)
                    : null;
                $videoUrl = $mediaType === FacebookPost::MEDIA_TYPE_VIDEO ? $imageMeta['public_url'] : null;
                if ($imageUrl) {
                    $this->assertPublicHttpsImageUrl($imageUrl);
                }

                try {
                    $googleLocationId = $this->resolveGoogleLocationId($data);
                    $post = FacebookPost::create([
                        'user_id' => $page->user_id,
                        'page_id' => $page->id,
                        'message' => $data['caption'],
                        'media_type' => $mediaType,
                        'image_url' => $imageUrl,
                        'video_path' => $mediaType === FacebookPost::MEDIA_TYPE_VIDEO ? $imageMeta['storage_path'] : null,
                        'video_url' => $videoUrl,
                        'platforms' => $platformsToPublish,
                        'google_location_id' => $googleLocationId,
                        'status' => FacebookPost::STATUS_PENDING,
                        'last_error' => null,
                    ]);

                    if ($mediaType === FacebookPost::MEDIA_TYPE_IMAGE) {
                        $post->images()->create(['user_id' => $page->user_id, 'image_path' => $imageMeta['storage_path']]);
                    }

                    DriveImagePost::create([
                        'user_id' => $page->user_id,
                        'page_id' => $page->id,
                        'drive_file_id' => $imageMeta['file_id'],
                        'drive_folder_id' => $data['folder_id'] ?? null,
                        'image_url' => $imageMeta['public_url'],
                        'caption' => $data['caption'],
                        'platforms' => $platformsToPublish,
                        'response_json' => ['status' => 'queued', 'facebook_post_record_id' => $post->id],
                    ]);

                    PublishPostJob::dispatch($post->id);

                    $results[] = [
                        'page_id' => $page->id,
                        'file_id' => $imageMeta['file_id'],
                        'success' => true,
                        'message' => 'Queued for publishing.',
                        'platforms' => $platformsToPublish,
                    ];
                } catch (\Throwable $exception) {
                    Log::error('Drive image queue failed', [
                        'page_id' => $page->id,
                        'file_id' => $imageMeta['file_id'],
                        'error' => $exception->getMessage(),
                    ]);

                    $results[] = [
                        'page_id' => $page->id,
                        'file_id' => $imageMeta['file_id'],
                        'success' => false,
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'success' => $successCount > 0,
            'message' => $successCount === count($results)
                ? 'All media queued for background publishing.'
                : "Queued {$successCount} of ".count($results).' media files.',
            'data' => [
                'results' => $results,
            ],
        ], $successCount > 0 ? 200 : 422);
    }

    private function resolveExistingDrivePublishedPlatforms(array $pageIds, array $preparedMedia): array
    {
        if (empty($pageIds) || empty($preparedMedia)) {
            return [];
        }

        return DriveImagePost::query()->ownedBy(Auth::user())
            ->whereIn('page_id', $pageIds)
            ->whereIn('drive_file_id', collect($preparedMedia)->pluck('file_id')->all())
            ->whereNotNull('posted_at')
            ->get()
            ->groupBy(fn (DriveImagePost $record) => $record->page_id.':'.$record->drive_file_id)
            ->map(fn ($records) => $records
                ->flatMap(fn (DriveImagePost $record) => $record->platforms ?? [])
                ->filter(fn (string $platform) => in_array($platform, ['facebook', 'instagram'], true))
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    private function resolvePreviouslyPublishedPlatformsForFile(array $publishedMap, ?int $pageId, string $fileId): array
    {
        if ($pageId) {
            return $publishedMap[$pageId.':'.$fileId] ?? [];
        }

        return collect($publishedMap)
            ->filter(fn (array $platforms, string $key) => str_ends_with($key, ':'.$fileId))
            ->flatMap(fn (array $platforms) => $platforms)
            ->unique()
            ->values()
            ->all();
    }

    public function proxyDriveImage(Request $request)
    {
        $data = $request->validate([
            'source_url' => 'nullable|url|max:4096',
            'file_id' => 'required|string|max:255',
            'resource_key' => 'nullable|string|max:255',
        ]);

        $googleAccount = GoogleAccount::query()->where('user_id', Auth::id())->firstOrFail();
        $googleAccount = $this->googleService->ensureValidGoogleAccountToken($googleAccount);
        $driveToken = $googleAccount->access_token;

        $binary = $this->downloadDriveBinaryFromUrl((string) ($data['source_url'] ?? ''));

        if (!$binary) {
            $binary = $this->googleDriveService->fetchImageBinary(
                (string) $data['file_id'],
                null,
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
            'page_id' => $isUpdate ? 'required|integer|exists:facebook_pages,id' : 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => $isUpdate ? 'nullable|array|min:1' : 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
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

    private function resolveAuthorizedPages(int $appId, array $pageIds): Collection
    {
        $pageIds = collect($pageIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($pageIds->isEmpty()) {
            return collect();
        }

        $pages = FacebookPage::query()->ownedBy(Auth::user())
            ->whereIn('id', $pageIds->all())
            ->where('facebook_app_id', $appId)
            ->where('is_active', true)
            ->get();

        return $pages->count() === $pageIds->count() ? $pages : collect();
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

    private function storeDriveFileLocally(string $fileId, string $sourceUrl, string $resourceKey, string $mimeType, GoogleAccount $googleAccount): array
    {
        $googleAccount = $this->googleService->ensureValidGoogleAccountToken($googleAccount);
        $driveToken = $googleAccount->access_token;

        $binary = $this->downloadDriveBinaryFromUrl($sourceUrl, $mimeType)
            ?: $this->googleDriveService->fetchFileBinary($fileId, null, $resourceKey, $driveToken);
        $contentType = strtolower((string) ($binary['content_type'] ?? $mimeType ?: 'image/jpeg'));
        $mediaType = str_starts_with($contentType, 'video/') ? FacebookPost::MEDIA_TYPE_VIDEO : FacebookPost::MEDIA_TYPE_IMAGE;
        $extension = match ($contentType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/quicktime' => 'mov',
            'video/mp4' => 'mp4',
            default => 'jpg',
        };

        $storagePath = ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO ? 'drive-posts/videos/' : 'drive-posts/images/').$fileId.'-'.uniqid().'.'.$extension;
        Storage::disk('public')->put($storagePath, $binary['content']);

        return [
            'file_id' => $fileId,
            'media_type' => $mediaType,
            'storage_path' => $storagePath,
            'public_url' => url(Storage::disk('public')->url($storagePath)),
        ];
    }

    private function downloadDriveBinaryFromUrl(string $sourceUrl, string $mimeType = ''): ?array
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
                ->retry(2, 250)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders(['Accept' => $mimeType !== '' ? $mimeType : 'image/*,video/*'])
                ->get($sourceUrl);

            if (!$response->successful()) {
                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type', ''));
            if (!str_starts_with($contentType, 'image/') && !str_starts_with($contentType, 'video/')) {
                return null;
            }

            $content = $response->body();
            if ($content === '') {
                return null;
            }

            return [
                'content' => $content,
                'content_type' => $response->header('Content-Type', 'image/jpeg'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

}
