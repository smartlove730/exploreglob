<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FacebookAppController extends Controller
{
    public function index()
    {
        $apps = FacebookApp::orderByDesc('created_at')->paginate(20);

        return view('admin.facebook.apps.index', compact('apps'));
    }

    public function create()
    {
        return view('admin.facebook.apps.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        FacebookApp::create($data);

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app added successfully.');
    }

    public function edit(FacebookApp $app)
    {
        return view('admin.facebook.apps.edit', compact('app'));
    }

    public function update(Request $request, FacebookApp $app): RedirectResponse
    {
        $data = $this->validateData($request, $app->id);

        $app->update($data);

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app updated successfully.');
    }

    public function destroy(FacebookApp $app): RedirectResponse
    {
        $app->delete();

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app deleted successfully.');
    }

    private function validateData(Request $request, ?int $appId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'app_id' => 'required|string|max:255|unique:facebook_apps,app_id,'.($appId ?? 'NULL').',id',
            'app_secret' => 'required|string|max:255',
            'redirect_uri' => 'required|url|max:2048',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
