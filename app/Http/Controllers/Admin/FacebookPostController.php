<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishFacebookPostJob;
use App\Models\FacebookPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacebookPostController extends Controller
{
    public function index()
    {
        $posts = FacebookPost::with('page')
            ->whereHas('page.facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->latest()
            ->paginate(20);

        return view('admin.facebook.posts', compact('posts'));
    }

    public function create()
    {
        $activePage = Auth::user()?->facebookAccount?->pages()->where('is_active', true)->first();

        return view('admin.facebook.create-post', compact('activePage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $activePage = Auth::user()?->facebookAccount?->pages()->where('is_active', true)->first();

        if (!$activePage) {
            return back()->with('error', 'Select an active page in Facebook Settings first.');
        }

        $status = filled($data['scheduled_at'] ?? null)
            ? FacebookPost::STATUS_SCHEDULED
            : FacebookPost::STATUS_PENDING;

        $post = FacebookPost::create([
            'page_id' => $activePage->id,
            'message' => $data['message'],
            'image_url' => $data['image_url'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $status,
        ]);

        if ($status === FacebookPost::STATUS_PENDING) {
            PublishFacebookPostJob::dispatch($post->id);
            return redirect()->route('admin.facebook.posts')->with('success', 'Facebook post queued for immediate publishing.');
        }

        PublishFacebookPostJob::dispatch($post->id)->delay($post->scheduled_at);

        return redirect()->route('admin.facebook.posts')->with('success', 'Facebook post scheduled successfully.');
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
