<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormSubmissionMail;
use App\Models\Plan;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home');
    }

    public function features()
    {
        return view('marketing.features');
    }

    public function pricing()
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('marketing.pricing', compact('plans'));
    }

    public function about()
    {
        return view('marketing.about');
    }

    public function contact()
    {
        return view('marketing.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $recipient = config('mail.from.address');
        if ($recipient) {
            Mail::to($recipient)->send(new ContactFormSubmissionMail($data));
        }
        app(ActivityLogService::class)->log('public.contact.submitted', null, [
            'email' => $data['email'],
            'subject' => $data['subject'],
        ]);

        return back()->with('success', 'Thanks for reaching out. We received your message.');
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }

    public function dataDeletion()
    {
        return view('marketing.data-deletion');
    }
}
