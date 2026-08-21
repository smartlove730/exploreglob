<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappSettingsController extends Controller
{
    public function index()
    {
        $settings = Auth::user()->whatsappAccount ?? new WhatsappAccount();
        return view('admin.whatsapp.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_account_id' => 'nullable|string|max:255',
            'phone_number_id' => 'nullable|string|max:255',
            'access_token' => 'nullable|string',
            'webhook_verify_token' => 'nullable|string|max:255',
            'api_version' => 'nullable|string|max:20',
            'use_embedded_signup' => 'nullable|boolean',
            
            'notify_events' => 'nullable|array',
            'notify_events.*' => 'string',
            
            'auto_reply_enabled' => 'nullable|boolean',
            'welcome_message' => 'nullable|string',
            'away_message' => 'nullable|string',
            'auto_reply_delay_seconds' => 'nullable|integer|min:0|max:60',
            
            'notify_email_enabled' => 'nullable|boolean',
            'notify_email_address' => 'nullable|email|max:255',
            'slack_webhook_url' => 'nullable|url|max:255',
        ]);
        
        $data['use_embedded_signup'] = $request->has('use_embedded_signup');
        $data['auto_reply_enabled'] = $request->has('auto_reply_enabled');
        $data['notify_email_enabled'] = $request->has('notify_email_enabled');
        $data['notify_events'] = $request->input('notify_events', []);

        $account = Auth::user()->whatsappAccount;
        
        if ($account) {
            $account->update($data);
        } else {
            Auth::user()->whatsappAccount()->create($data);
        }

        return redirect()->route('admin.whatsapp.settings')->with('success', 'WhatsApp settings updated successfully.');
    }
}
