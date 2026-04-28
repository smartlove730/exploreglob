<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\UserMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaLibraryController extends Controller
{
    public function index()
    {
        $mediaItems = UserMedia::query()
            ->ownedBy(Auth::user())
            ->latest()
            ->paginate(24);

        return view('app.media.index', compact('mediaItems'));
    }

    public function list(): JsonResponse
    {
        $items = UserMedia::query()
            ->ownedBy(Auth::user())
            ->latest()
            ->get()
            ->map(fn (UserMedia $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'name' => $item->original_name,
                'mime_type' => $item->mime_type,
                'size' => $item->size,
                'url' => $item->public_url,
            ])->values();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime',
        ]);

        foreach ($data['files'] as $file) {
            $mime = (string) $file->getMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $type = $isVideo ? UserMedia::TYPE_VIDEO : UserMedia::TYPE_IMAGE;
            $folder = $isVideo ? 'user-media/videos' : 'user-media/images';
            $path = $file->store($folder, 'public');

            UserMedia::create([
                'user_id' => Auth::id(),
                'type' => $type,
                'path' => $path,
                'original_name' => (string) $file->getClientOriginalName(),
                'mime_type' => $mime,
                'size' => (int) $file->getSize(),
            ]);
        }

        return back()->with('success', 'Media uploaded successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $item = UserMedia::query()->ownedBy(Auth::user())->findOrFail($id);

        if (Storage::disk('public')->exists($item->path)) {
            Storage::disk('public')->delete($item->path);
        }

        $item->delete();

        return back()->with('success', 'Media deleted successfully.');
    }
}
