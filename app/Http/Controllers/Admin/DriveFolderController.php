<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveFolder;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriveFolderController extends Controller
{
    public function __construct(private readonly GoogleDriveService $googleDriveService)
    {
    }

    public function index()
    {
        $folders = $this->scopedFolders()->latest()->paginate(20);

        return view('admin.drive-folders.index', compact('folders'));
    }

    public function create()
    {
        return view('admin.drive-folders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['folder_id'] = $this->googleDriveService->extractFolderId($data['folder_url']);
        $data['user_id'] = Auth::id();

        DriveFolder::create($data);

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder saved successfully.');
    }

    public function edit(DriveFolder $drive_folder)
    {
        $this->authorizeFolder($drive_folder);

        return view('admin.drive-folders.edit', ['driveFolder' => $drive_folder]);
    }

    public function update(Request $request, DriveFolder $drive_folder): RedirectResponse
    {
        $this->authorizeFolder($drive_folder);

        $data = $this->validateData($request);
        $data['folder_id'] = $this->googleDriveService->extractFolderId($data['folder_url']);
        $drive_folder->update($data);

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder updated successfully.');
    }

    public function destroy(DriveFolder $drive_folder): RedirectResponse
    {
        $this->authorizeFolder($drive_folder);

        $drive_folder->delete();

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder deleted successfully.');
    }

    private function scopedFolders()
    {
        return DriveFolder::query()->ownedBy(Auth::user());
    }

    private function authorizeFolder(DriveFolder $driveFolder): void
    {
        if (!Auth::user()?->isAdmin() && $driveFolder->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'folder_url' => 'required|url|max:4096',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
