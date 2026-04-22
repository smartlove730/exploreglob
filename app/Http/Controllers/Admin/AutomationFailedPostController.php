<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationProcessedMedia;
use Illuminate\Support\Facades\Auth;

class AutomationFailedPostController extends Controller
{
    public function index()
    {
        $failedPosts = AutomationProcessedMedia::query()
            ->where('status', AutomationProcessedMedia::STATUS_FAILED)
            ->whereHas('automation', fn ($query) => $query->where('user_id', Auth::id()))
            ->with('automation:id,name')
            ->orderByDesc('failed_at')
            ->orderByDesc('updated_at')
            ->paginate(50);

        return view('admin.automations.failed-posts', compact('failedPosts'));
    }
}
