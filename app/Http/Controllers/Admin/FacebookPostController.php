<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishFacebookPostJob;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FacebookPostController extends Controller
{
    public function index()
    {
        $posts = FacebookPost::with(['page.facebookAccount.app'])
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
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram',
        ]);

        if (!empty($data['image_url'])) {
            $this->assertPublicHttpsImageUrl($data['image_url']);
        }

        $page = FacebookPage::query()
            ->whereKey($data['page_id'])
            ->where('facebook_app_id', $data['app_id'])
            ->where('is_active', true)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();

        if (in_array('instagram', $platforms, true) && mb_strlen($data['message']) > 2200) {
            throw ValidationException::withMessages([
                'message' => 'Instagram caption must be 2,200 characters or fewer.',
            ]);
        }

        if (in_array('instagram', $platforms, true) && empty($data['image_url'])) {
            throw ValidationException::withMessages([
                'image_url' => 'An HTTPS public image URL is required for Instagram posting.',
            ]);
        }

        $post = FacebookPost::create([
            'page_id' => $page->id,
            'message' => $data['message'],
            'image_url' => $data['image_url'] ?? null,
            'platforms' => $platforms,
            'scheduled_at' => null,
            'status' => FacebookPost::STATUS_PENDING,
        ]);

        PublishFacebookPostJob::dispatch($post->id);

        return redirect()->route('admin.facebook.posts')->with('success', 'Post queued for publishing.');
    }

    public function retry(FacebookPost $post): RedirectResponse
    {
        abort_unless($post->page->facebookAccount->user_id === Auth::id(), 403);

        $post->update([
            'status' => FacebookPost::STATUS_PENDING,
            'response_json' => null,
        ]);

        PublishFacebookPostJob::dispatch($post->id);

        return back()->with('success', 'Post retry queued.');
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
}
