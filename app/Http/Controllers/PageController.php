<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function contact()
    {
        return view('pages.contact');
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactSubmission::create($validated);

        return back()->with('contact_submitted', true);
    }

    public function policy()
    {
        return view('pages.policy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function about()
    {
        return view('pages.about');
    }
}
