<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishFacebookPostJob;
use App\Models\FacebookApp;
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

        $activePageQuery = Auth::user()?->facebookAccounts()
            ->when($selectedAppId > 0, fn ($query) => $query->where('facebook_app_id', $selectedAppId))
            ->with(['pages' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->flatMap(fn ($account) => $account->pages)
            ->first();

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'activePage' => $activePageQuery,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
        ]);

        $activePage = Auth::user()?->facebookAccounts()
            ->where('facebook_app_id', $data['app_id'])
            ->with(['pages' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->flatMap(fn ($account) => $account->pages)
            ->first();

        if (!$activePage) {
            return back()->with('error', 'Select an active page in Facebook Settings first.');
        }

        $post = FacebookPost::create([
            'page_id' => $activePage->id,
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
