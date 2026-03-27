<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriveApiKeyController extends Controller
{
    public function index()
    {
        $keys = DriveApiKey::orderByDesc('created_at')->paginate(20);

        return view('admin.google-drive-keys.index', compact('keys'));
    }

    public function create()
    {
        return view('admin.google-drive-keys.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        DriveApiKey::create($data);

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google Drive key added successfully.');
    }

    public function edit(DriveApiKey $google_drive_key)
    {
        return view('admin.google-drive-keys.edit', ['driveKey' => $google_drive_key]);
    }

    public function update(Request $request, DriveApiKey $google_drive_key): RedirectResponse
    {
        $data = $this->validateData($request);
        $google_drive_key->update($data);

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google Drive key updated successfully.');
    }

    public function destroy(DriveApiKey $google_drive_key): RedirectResponse
    {
        $google_drive_key->delete();

        return redirect()->route('admin.facebook.google-drive-keys.index')->with('success', 'Google Drive key deleted successfully.');
    }

    public function callback(Request $request)
    {
        return view('admin.google-drive-keys.callback', [
            'code' => $request->query('code'),
            'scope' => $request->query('scope'),
            'error' => $request->query('error'),
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|email|max:255',
            'redirect_url' => 'nullable|url|max:2048',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
