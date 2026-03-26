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
        ]);

        $page = FacebookPage::query()
            ->whereKey($data['page_id'])
            ->where('facebook_app_id', $data['app_id'])
            ->where('is_active', true)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $post = FacebookPost::create([
            'page_id' => $page->id,
            'message' => $data['message'],
            'image_url' => $data['image_url'] ?? null,
            'scheduled_at' => null,
            'status' => FacebookPost::STATUS_PENDING,
        ]);

        PublishFacebookPostJob::dispatch($post->id);

        return redirect()->route('admin.facebook.posts')->with('success', 'Facebook post queued for immediate publishing.');
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
}
