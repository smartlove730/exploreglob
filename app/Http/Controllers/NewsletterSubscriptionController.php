<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;

class NewsletterSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'blog_id' => ['nullable', 'integer', 'exists:blogs,id'],
        ]);

        NewsletterSubscription::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'blog_id' => $validated['blog_id'] ?? null,
        ]);

        return back()->with('newsletter_success', 'Thanks for subscribing!');
    }
}
