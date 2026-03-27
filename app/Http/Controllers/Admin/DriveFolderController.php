<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriveFolderController extends Controller
{
    public function __construct(private readonly GoogleDriveService $googleDriveService)
    {
    }

    public function index()
    {
        $folders = DriveFolder::with('driveApiKey')->latest()->paginate(20);

        return view('admin.drive-folders.index', compact('folders'));
    }

    public function create()
    {
        $driveApiKeys = DriveApiKey::where('is_active', true)->orderBy('name')->get();

        return view('admin.drive-folders.create', compact('driveApiKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['folder_id'] = $this->googleDriveService->extractFolderId($data['folder_url']);

        DriveFolder::create($data);

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder saved successfully.');
    }

    public function edit(DriveFolder $drive_folder)
    {
        $driveApiKeys = DriveApiKey::where('is_active', true)->orderBy('name')->get();

        return view('admin.drive-folders.edit', ['driveFolder' => $drive_folder, 'driveApiKeys' => $driveApiKeys]);
    }

    public function update(Request $request, DriveFolder $drive_folder): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['folder_id'] = $this->googleDriveService->extractFolderId($data['folder_url']);
        $drive_folder->update($data);

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder updated successfully.');
    }

    public function destroy(DriveFolder $drive_folder): RedirectResponse
    {
        $drive_folder->delete();

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder deleted successfully.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'folder_url' => 'required|url|max:4096',
            'drive_api_key_id' => 'nullable|integer|exists:drive_api_keys,id',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
