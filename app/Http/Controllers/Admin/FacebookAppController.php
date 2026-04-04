<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacebookAppController extends Controller
{
    public function index()
    {
        $apps = FacebookApp::query()->ownedBy(Auth::user())->orderByDesc('created_at')->paginate(20);

        return view('admin.facebook.apps.index', compact('apps'));
    }

    public function create()
    {
        return view('admin.facebook.apps.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['user_id'] = Auth::id();

        FacebookApp::create($data);

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app added successfully.');
    }

    public function edit(FacebookApp $app)
    {
        $this->authorizeModel($app);

        return view('admin.facebook.apps.edit', compact('app'));
    }

    public function update(Request $request, FacebookApp $app): RedirectResponse
    {
        $this->authorizeModel($app);

        $data = $this->validateData($request, $app->id);

        $app->update($data);

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app updated successfully.');
    }

    public function destroy(FacebookApp $app): RedirectResponse
    {
        $this->authorizeModel($app);

        $app->delete();

        return redirect()->route('admin.facebook.apps.index')->with('success', 'Facebook app deleted successfully.');
    }

    private function validateData(Request $request, ?int $appId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'app_id' => 'required|string|max:255|unique:facebook_apps,app_id,'.($appId ?? 'NULL').',id,user_id,'.Auth::id(),
            'app_secret' => 'required|string|max:255',
            'redirect_uri' => 'required|url|max:2048',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function authorizeModel(FacebookApp $app): void
    {
        if (!Auth::user()?->isAdmin() && $app->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
