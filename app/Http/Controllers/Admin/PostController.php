<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeletePostJob;
use App\Jobs\PublishPostJob;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Models\DriveImagePost;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
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
use Illuminate\Support\Facades\Schema;
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

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'app_id' => (int) $request->query('app_id', 0),
            'page_id' => (int) $request->query('page_id', 0),
            'status' => (string) $request->query('status', ''),
            'media_type' => (string) $request->query('media_type', ''),
            'platform' => (string) $request->query('platform', ''),
            'posted_from' => (string) $request->query('posted_from', ''),
            'posted_to' => (string) $request->query('posted_to', ''),
        ];

        $posts = FacebookPost::query()
            ->ownedBy(Auth::user())
            ->whereNull('deleted_at')
            ->with(['page.facebookAccount.app', 'images'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($innerQuery) use ($filters) {
                    $innerQuery
                        ->where('message', 'like', '%'.$filters['search'].'%')
                        ->orWhere('id', $filters['search']);
                });
            })
            ->when($filters['app_id'] > 0, fn ($query) => $query->whereHas('page', fn ($pageQuery) => $pageQuery->where('facebook_app_id', $filters['app_id'])))
            ->when($filters['page_id'] > 0, fn ($query) => $query->where('page_id', $filters['page_id']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['media_type'] !== '', fn ($query) => $query->where('media_type', $filters['media_type']))
            ->when($filters['platform'] !== '', fn ($query) => $query->whereJsonContains('platforms', $filters['platform']))
            ->when($filters['posted_from'] !== '', fn ($query) => $query->whereDate('posted_at', '>=', $filters['posted_from']))
            ->when($filters['posted_to'] !== '', fn ($query) => $query->whereDate('posted_at', '<=', $filters['posted_to']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusOptions = [
            FacebookPost::STATUS_PENDING,
            FacebookPost::STATUS_PROCESSING,
            FacebookPost::STATUS_PUBLISHED,
            FacebookPost::STATUS_FAILED,
        ];

        $mediaTypeOptions = [
            FacebookPost::MEDIA_TYPE_IMAGE,
            FacebookPost::MEDIA_TYPE_VIDEO,
        ];

        $platformOptions = ['facebook', 'instagram'];

        $apps = FacebookApp::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $pages = FacebookPage::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderBy('page_name')
            ->get(['id', 'page_name', 'facebook_app_id']);

        return view('admin.facebook.posts', compact(
            'posts',
            'filters',
            'statusOptions',
            'mediaTypeOptions',
            'platformOptions',
            'apps',
            'pages',
        ));
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

        $selectedDriveApiKeyId = (int) old(
            'drive_api_key_id',
            $request->integer('drive_api_key_id', $this->resolvePreferredDriveApiKeyId())
        );

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'pages' => $pages,
            'selectedPageIds' => collect(old('page_ids', array_filter([(int) old('page_id', $request->integer('page_id'))])))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all(),
            'driveApiKeys' => DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get(),
            'selectedDriveApiKeyId' => $selectedDriveApiKeyId,
            'driveFolders' => DriveFolder::query()->ownedBy(Auth::user())->with('driveApiKey')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        @set_time_limit(120);

        $data = $this->validatePostRequest($request);
        $pages = $this->resolveAuthorizedPages((int) $data['app_id'], $data['page_ids'] ?? []);
        if ($pages->isEmpty()) {
            return back()->withInput()->with('error', 'Select at least one valid page for this app/user.');
        }

        $mediaType = $data['media_type'] ?? FacebookPost::MEDIA_TYPE_IMAGE;

        if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO) {
            $videoMeta = $this->storeVideoAndResolveUrl($request);
        }

        $createdCount = 0;
        $nextPublishAt = now();
        $cachedEligibleImageUrl = null;
        if ($mediaType === FacebookPost::MEDIA_TYPE_IMAGE && !empty($data['image_url'])) {
            $cachedEligibleImageUrl = $this->ensureInstagramEligibleImage((string) $data['image_url'], $data['platforms']);
            if ($cachedEligibleImageUrl) {
                $this->assertPublicHttpsImageUrl($cachedEligibleImageUrl);
            }
        }
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
                'google_location_id' => null,
                'status' => FacebookPost::STATUS_PENDING,
                'last_error' => null,
            ]);

            if ($mediaType === FacebookPost::MEDIA_TYPE_VIDEO) {
                $post->update([
                    'video_path' => $videoMeta['video_path'],
                    'video_url' => $videoMeta['video_url'],
                    'image_url' => null,
                ]);

                $nextPublishAt = $this->nextPostDispatchAt($nextPublishAt, 5, 10);
                $post->update(['scheduled_at' => $nextPublishAt]);
                PublishPostJob::dispatch($post->id)->delay($nextPublishAt);
                $createdCount++;
                continue;
            }

            $this->syncImages($post, $request, []);
            $publishImageUrl = $cachedEligibleImageUrl ?: $this->resolvePublishImageUrl($post);
            if (!$cachedEligibleImageUrl) {
                $publishImageUrl = $this->ensureInstagramEligibleImage($publishImageUrl, $data['platforms']);
            }

            if (in_array('instagram', $data['platforms'], true) && !$publishImageUrl) {
                throw ValidationException::withMessages(['images' => 'Instagram requires at least one image URL or upload.']);
            }

            $post->update([
                'image_url' => $publishImageUrl,
                'google_location_id' => null,
                'status' => FacebookPost::STATUS_PENDING,
                'last_error' => null,
            ]);

            $nextPublishAt = $this->nextPostDispatchAt($nextPublishAt, 5, 10);
            $post->update(['scheduled_at' => $nextPublishAt]);
            PublishPostJob::dispatch($post->id)->delay($nextPublishAt);
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
            'google_location_id' => null,
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

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $post = FacebookPost::query()->ownedBy(Auth::user())->whereNull('deleted_at')->with(['images', 'page.facebookAccount'])->findOrFail($id);

        if ($post->deletion_status === FacebookPost::DELETION_STATUS_PENDING) {
            return $this->deleteAlreadyQueuedResponse($request);
        }

        $post->forceFill([
            'deletion_status' => FacebookPost::DELETION_STATUS_PENDING,
            'last_error' => null,
        ])->save();

        $this->dispatchDeletePostJob($post->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Post deletion queued.',
                'post_id' => $post->id,
            ]);
        }

        return redirect()->route('admin.posts.index')->with('success', 'Post deletion queued. Refresh shortly to see updated status.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['required', 'integer'],
        ]);

        $posts = FacebookPost::query()
            ->ownedBy(Auth::user())
            ->whereNull('deleted_at')
            ->whereIn('id', $data['post_ids'])
            ->get();

        $accepted = [];
        $skipped = [];

        foreach ($posts as $post) {
            if ($post->deletion_status === FacebookPost::DELETION_STATUS_PENDING) {
                $skipped[] = [
                    'post_id' => $post->id,
                    'reason' => 'already_pending',
                ];
                continue;
            }

            $post->forceFill([
                'deletion_status' => FacebookPost::DELETION_STATUS_PENDING,
                'last_error' => null,
            ])->save();

            $this->dispatchDeletePostJob($post->id);

            $accepted[] = $post->id;
        }

        return response()->json([
            'success' => true,
            'message' => count($accepted).' post deletion job(s) queued.',
            'accepted' => $accepted,
            'skipped' => $skipped,
            'not_found' => array_values(array_diff($data['post_ids'], $posts->pluck('id')->all())),
        ]);
    }

    private function deleteAlreadyQueuedResponse(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Deletion is already queued for this post.',
            ], 409);
        }

        return redirect()->route('admin.posts.index')->with('error', 'Deletion is already queued for this post.');
    }

    private function dispatchDeletePostJob(int $postId): void
    {
        DeletePostJob::dispatch($postId)->onQueue('default');
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
            'drive_api_key_id' => 'nullable|integer|exists:drive_api_keys,id',
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

        $driveApiKey = $this->resolveDriveApiKeyFromRequest($data);

        if (!$driveApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'No active Google Drive connection found. Connect OAuth or select an active Drive account.',
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

        $driveToken = null;
        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $mediaItems = $this->googleDriveService->listPublicFolderMedia(
            $folderId,
            $driveApiKey->api_key,
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
        @set_time_limit(180);

        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
            'drive_api_key_id' => 'nullable|integer|exists:drive_api_keys,id',
            'folder_id' => 'nullable|string|max:255',
            'caption' => 'required|string|max:60000',
            'post_mode' => 'nullable|string|in:separate,combined',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram',
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
        $driveApiKey = $this->resolveDriveApiKeyFromRequest($data);

        if (!$driveApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'No active Google Drive connection found. Connect OAuth or select an active Drive account.',
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
                    $driveApiKey
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
        $nextPublishAt = now();
        $instagramEligibleCache = [];
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

        if ($postMode === 'combined') {
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
                    ->map(fn (array $imageMeta) => $this->ensureInstagramEligibleImageCached(
                        $instagramEligibleCache,
                        (string) $imageMeta['file_id'].'|'.implode(',', $platforms),
                        $imageMeta['public_url'],
                        $platforms
                    ))
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
                    'google_location_id' => null,
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

                $nextPublishAt = $this->nextPostDispatchAt($nextPublishAt, 5, 10);
                $post->update(['scheduled_at' => $nextPublishAt]);
                PublishPostJob::dispatch($post->id)->delay($nextPublishAt);
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
                    ? $this->ensureInstagramEligibleImageCached(
                        $instagramEligibleCache,
                        (string) $imageMeta['file_id'].'|'.implode(',', $platformsToPublish),
                        $imageMeta['public_url'],
                        $platformsToPublish
                    )
                    : null;
                $videoUrl = $mediaType === FacebookPost::MEDIA_TYPE_VIDEO ? $imageMeta['public_url'] : null;
                if ($imageUrl) {
                    $this->assertPublicHttpsImageUrl($imageUrl);
                }

                try {
                    $post = FacebookPost::create([
                        'user_id' => $page->user_id,
                        'page_id' => $page->id,
                        'message' => $data['caption'],
                        'media_type' => $mediaType,
                        'image_url' => $imageUrl,
                        'video_path' => $mediaType === FacebookPost::MEDIA_TYPE_VIDEO ? $imageMeta['storage_path'] : null,
                        'video_url' => $videoUrl,
                        'platforms' => $platformsToPublish,
                        'google_location_id' => null,
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

                    $nextPublishAt = $this->nextPostDispatchAt($nextPublishAt, 5, 10);
                    $post->update(['scheduled_at' => $nextPublishAt]);
                    PublishPostJob::dispatch($post->id)->delay($nextPublishAt);

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

    public function executeNow(int $id): RedirectResponse
    {
        $post = FacebookPost::query()
            ->ownedBy(Auth::user())
            ->whereNull('deleted_at')
            ->findOrFail($id);

        if ($post->status === FacebookPost::STATUS_PUBLISHED) {
            return back()->with('error', 'Post is already published.');
        }

        $post->update([
            'status' => FacebookPost::STATUS_PENDING,
            'last_error' => null,
            'scheduled_at' => now(),
        ]);

        PublishPostJob::dispatch($post->id);

        return back()->with('success', 'Post queued to execute immediately.');
    }

    public function retry(int $id): RedirectResponse
    {
        $post = FacebookPost::query()
            ->ownedBy(Auth::user())
            ->whereNull('deleted_at')
            ->findOrFail($id);

        if ($post->status !== FacebookPost::STATUS_FAILED) {
            return back()->with('error', 'Only failed posts can be retried.');
        }

        $post->update([
            'status' => FacebookPost::STATUS_PENDING,
            'last_error' => null,
            'scheduled_at' => now(),
        ]);

        PublishPostJob::dispatch($post->id);

        return back()->with('success', 'Retry queued for failed post.');
    }

    public function bulkRetry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_ids' => ['required', 'array', 'min:1'],
            'post_ids.*' => ['required', 'integer'],
        ]);

        $posts = FacebookPost::query()
            ->ownedBy(Auth::user())
            ->whereNull('deleted_at')
            ->whereIn('id', $data['post_ids'])
            ->get()
            ->keyBy('id');

        $accepted = [];
        $skipped = [];
        $notFound = array_values(array_diff($data['post_ids'], $posts->keys()->all()));
        $nextDispatchAt = now();

        foreach ($data['post_ids'] as $postId) {
            /** @var FacebookPost|null $post */
            $post = $posts->get($postId);
            if (!$post) {
                continue;
            }

            if ($post->status !== FacebookPost::STATUS_FAILED) {
                $skipped[] = [
                    'post_id' => $post->id,
                    'reason' => 'not_failed',
                ];
                continue;
            }

            $nextDispatchAt = $this->nextPostDispatchAt($nextDispatchAt, 2, 5);
            $post->update([
                'status' => FacebookPost::STATUS_PENDING,
                'last_error' => null,
                'scheduled_at' => $nextDispatchAt,
            ]);

            PublishPostJob::dispatch($post->id)->delay($nextDispatchAt);
            $accepted[] = $post->id;
        }

        return response()->json([
            'success' => !empty($accepted),
            'message' => count($accepted).' failed post retry job(s) queued.',
            'accepted' => $accepted,
            'skipped' => $skipped,
            'not_found' => $notFound,
        ], !empty($accepted) ? 200 : 422);
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

    private function nextPostDispatchAt(\Illuminate\Support\Carbon $cursor, int $minGapMinutes, int $maxGapMinutes): \Illuminate\Support\Carbon
    {
        return $cursor->copy()->addMinutes(random_int($minGapMinutes, $maxGapMinutes));
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

        $binary = $this->downloadDriveBinaryFromUrl((string) ($data['source_url'] ?? ''));

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
            'page_id' => $isUpdate ? 'required|integer|exists:facebook_pages,id' : 'nullable|integer|exists:facebook_pages,id',
            'page_ids' => $isUpdate ? 'nullable|array|min:1' : 'required_without:page_id|array|min:1',
            'page_ids.*' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'media_type' => 'nullable|string|in:image,video',
            'image_url' => 'nullable|url|max:2048',
            'video' => 'nullable|file|mimes:mp4',
            'video_url' => 'nullable|url|max:2048',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
            'remove_images' => $isUpdate ? 'nullable|array' : 'nullable',
            'remove_images.*' => 'integer|exists:post_images,id',
        ]);
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

    private function ensureInstagramEligibleImageCached(array &$cache, string $cacheKey, ?string $imageUrl, array $platforms): ?string
    {
        if ($cacheKey !== '' && array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $resolved = $this->ensureInstagramEligibleImage($imageUrl, $platforms);

        if ($cacheKey !== '') {
            $cache[$cacheKey] = $resolved;
        }

        return $resolved;
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

    private function storeDriveFileLocally(string $fileId, string $sourceUrl, string $resourceKey, string $mimeType, DriveApiKey $driveApiKey): array
    {
        $driveToken = null;
        if ($driveApiKey->oauth_access_token || $driveApiKey->oauth_refresh_token) {
            $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
            $driveToken = $driveApiKey->oauth_access_token;
        }

        $binary = $this->downloadDriveBinaryFromUrl($sourceUrl, $mimeType)
            ?: $this->fetchDriveBinaryWithCompressionFallback($fileId, $resourceKey, $mimeType, $driveApiKey, $driveToken);
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

    private function fetchDriveBinaryWithCompressionFallback(
        string $fileId,
        string $resourceKey,
        string $mimeType,
        DriveApiKey $driveApiKey,
        ?string $driveToken
    ): array {
        try {
            return $this->googleDriveService->fetchFileBinary($fileId, $driveApiKey->api_key, $resourceKey, $driveToken);
        } catch (\Throwable $exception) {
            $isOversized = str_contains(strtolower($exception->getMessage()), 'too large');
            $isImage = $mimeType === '' || str_starts_with(strtolower($mimeType), 'image/');

            if (!$isOversized || !$isImage) {
                throw $exception;
            }

            $previewUrl = $this->googleDriveService->buildPreviewUrl($fileId, $resourceKey);
            $compressedUrl = $this->driveService->prepareInstagramEligibleFromUrl($previewUrl);
            $compressedPath = $this->extractPublicStoragePathFromUrl($compressedUrl);

            if ($compressedPath === null || !Storage::disk('public')->exists($compressedPath)) {
                throw $exception;
            }

            return [
                'content' => Storage::disk('public')->get($compressedPath),
                'content_type' => 'image/jpeg',
            ];
        }
    }

    private function extractPublicStoragePathFromUrl(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $storagePrefix = '/storage/';

        if ($path === '' || !str_contains($path, $storagePrefix)) {
            return null;
        }

        return ltrim(substr($path, strpos($path, $storagePrefix) + strlen($storagePrefix)), '/');
    }

    private function resolvePreferredDriveApiKeyId(): int
    {
        if (!$this->supportsDriveOauthColumns()) {
            return (int) DriveApiKey::query()
                ->ownedBy(Auth::user())
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->value('id');
        }

        $oauthDriveKeyId = (int) DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNotNull('oauth_access_token')
                    ->orWhereNotNull('oauth_refresh_token');
            })
            ->orderByDesc('updated_at')
            ->value('id');

        if ($oauthDriveKeyId > 0) {
            return $oauthDriveKeyId;
        }

        return (int) DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('id');
    }

    private function supportsDriveOauthColumns(): bool
    {
        return Schema::hasColumns('drive_api_keys', [
            'oauth_access_token',
            'oauth_refresh_token',
        ]);
    }

    private function resolveDriveApiKeyFromRequest(array $data): ?DriveApiKey
    {
        $selectedDriveApiKeyId = (int) ($data['drive_api_key_id'] ?? 0);

        if ($selectedDriveApiKeyId > 0) {
            return DriveApiKey::query()->ownedBy(Auth::user())
                ->whereKey($selectedDriveApiKeyId)
                ->where('is_active', true)
                ->first();
        }

        $preferredDriveApiKeyId = $this->resolvePreferredDriveApiKeyId();
        if ($preferredDriveApiKeyId <= 0) {
            return null;
        }

        return DriveApiKey::query()->ownedBy(Auth::user())
            ->whereKey($preferredDriveApiKeyId)
            ->where('is_active', true)
            ->first();
    }
}
