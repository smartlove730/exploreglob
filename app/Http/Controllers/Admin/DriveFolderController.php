<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use App\Models\DriveFolder;
use App\Services\GoogleDriveService;
use App\Services\GoogleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriveFolderController extends Controller
{
    public function __construct(
        private readonly GoogleDriveService $googleDriveService,
        private readonly GoogleService $googleService,
    ) {}

    public function index()
    {
        $folders = $this->scopedFolders()->with('driveApiKey')->latest()->paginate(20);

        return view('admin.drive-folders.index', compact('folders'));
    }

    public function create()
    {
        $driveApiKeys = DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();

        return view('admin.drive-folders.create', compact('driveApiKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $this->assertAuthorizedDriveKey($data['drive_api_key_id'] ?? null);
        $data['folder_id'] = $this->googleDriveService->extractFolderId($data['folder_url']);
        $data['user_id'] = Auth::id();

        DriveFolder::create($data);

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', 'Drive folder saved successfully.');
    }

    public function edit(DriveFolder $drive_folder)
    {
        $this->authorizeFolder($drive_folder);

        $driveApiKeys = DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();

        return view('admin.drive-folders.edit', ['driveFolder' => $drive_folder, 'driveApiKeys' => $driveApiKeys]);
    }

    public function update(Request $request, DriveFolder $drive_folder): RedirectResponse
    {
        $this->authorizeFolder($drive_folder);

        $data = $this->validateData($request);
        $this->assertAuthorizedDriveKey($data['drive_api_key_id'] ?? null);
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

    public function sync(): RedirectResponse
    {
        $driveApiKeys = DriveApiKey::query()->ownedBy(Auth::user())->where('is_active', true)->get();
        $syncedCount = 0;

        foreach ($driveApiKeys as $driveApiKey) {
            try {
                $token = null;
                if ($driveApiKey->oauth_access_token) {
                    $driveApiKey = $this->googleService->ensureValidDriveToken($driveApiKey);
                    $token = $driveApiKey->oauth_access_token;
                }

                $folders = $this->googleDriveService->listAccessibleFolders($driveApiKey->api_key, $token);
                foreach ($folders as $folder) {
                    DriveFolder::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'drive_api_key_id' => $driveApiKey->id,
                            'folder_id' => $folder['id'],
                        ],
                        [
                            'name' => $folder['name'],
                            'folder_url' => $folder['url'],
                            'is_active' => true,
                        ]
                    );
                    $syncedCount++;
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return redirect()->route('admin.facebook.drive-folders.index')->with('success', "Sync completed. {$syncedCount} folders synced.");
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

    private function assertAuthorizedDriveKey(?int $driveApiKeyId): void
    {
        if (!$driveApiKeyId) {
            return;
        }

        $exists = DriveApiKey::query()
            ->ownedBy(Auth::user())
            ->whereKey($driveApiKeyId)
            ->exists();

        abort_unless($exists, 422, 'Selected Drive API key is not accessible.');
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
