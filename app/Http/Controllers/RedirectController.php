<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    public function appDashboard(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    public function categories(): RedirectResponse
    {
        return redirect('/travel', 301);
    }

    public function category(string $slug): RedirectResponse
    {
        return redirect('/travel/'.$slug, 301);
    }
}
