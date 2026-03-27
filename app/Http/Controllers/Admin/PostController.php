<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Services\MetaPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(private readonly MetaPostService $metaPostService)
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePostRequest($request);
        $page = $this->resolveAuthorizedPage((int) $data['app_id'], (int) $data['page_id']);

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

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
            $publishResult = $this->metaPostService->publish($page, $post->message, $publishImageUrl, $data['platforms']);

            $post->update([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'posted_at' => now(),
                'facebook_post_id' => $publishResult['facebook_post_id'],
                'instagram_media_id' => $publishResult['instagram_media_id'],
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
                $publishResult = $this->metaPostService->publish($page, $post->message, $publishImageUrl, $data['platforms']);
                $post->update([
                    'status' => FacebookPost::STATUS_PUBLISHED,
                    'posted_at' => now(),
                    'facebook_post_id' => $publishResult['facebook_post_id'],
                    'instagram_media_id' => $publishResult['instagram_media_id'],
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

    private function validatePostRequest(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
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
}
