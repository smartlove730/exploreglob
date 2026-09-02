<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappAccount;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->first();
        $templates = [];
        $contacts = [];
        if ($account) {
            $templates = WhatsappTemplate::where('whatsapp_account_id', $account->id)->orderBy('created_at', 'desc')->get();
            $contacts = \App\Models\WhatsappContact::where('user_id', auth()->id())->get();
        }
        
        return view('admin.whatsapp.templates', compact('templates', 'account', 'contacts'));
    }

    public function create()
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->first();
        if (!$account) {
            return redirect()->route('admin.whatsapp.settings')->with('error', 'Please connect WhatsApp first.');
        }

        return view('admin.whatsapp.templates-create', compact('account'));
    }

    public function reports(Request $request)
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->first();
        if (!$account) {
            return redirect()->route('admin.whatsapp.settings')->with('error', 'Please connect WhatsApp first.');
        }

        $query = \App\Models\WhatsappMessage::where('type', 'template')
            ->whereHas('conversation.phoneNumber', function ($q) use ($account) {
                $q->where('whatsapp_account_id', $account->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('conversation.contact', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $messages = $query->with(['template', 'conversation.contact'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.whatsapp.reports', compact('messages'));
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:whatsapp_templates,id',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:whatsapp_contacts,id',
            'schedule_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $account = WhatsappAccount::where('user_id', auth()->id())->firstOrFail();
        $template = WhatsappTemplate::findOrFail($validated['template_id']);
        
        if ($template->whatsapp_account_id !== $account->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $delay = null;
        if (!empty($validated['schedule_at'])) {
            $delay = \Carbon\Carbon::parse($validated['schedule_at']);
        }

        $optedOutCount = 0;
        $queuedCount = 0;

        foreach ($validated['contact_ids'] as $contactId) {
            $contact = \App\Models\WhatsappContact::find($contactId);
            if (!$contact || !$contact->opted_in) {
                $optedOutCount++;
                continue;
            }

            if ($delay) {
                \App\Jobs\SendWhatsappTemplateJob::dispatch($account->id, $template->id, $contactId)->delay($delay);
            } else {
                \App\Jobs\SendWhatsappTemplateJob::dispatch($account->id, $template->id, $contactId);
            }
            $queuedCount++;
        }

        $message = $delay ? "Scheduled {$queuedCount} templates." : "Queued {$queuedCount} templates.";
        if ($optedOutCount > 0) {
            $message .= " Skipped {$optedOutCount} contacts who have opted out.";
        }

        return response()->json([
            'success' => true, 
            'message' => $message
        ]);
    }
    
    public function store(Request $request)
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->firstOrFail();
        
        if (!$account->business_account_id) {
            return response()->json(['success' => false, 'error' => 'WhatsApp Business Account ID is missing in settings.'], 400);
        }

        // Decode buttons from JSON string if sent that way (from the create page form)
        if ($request->has('buttons') && is_string($request->input('buttons'))) {
            $decoded = json_decode($request->input('buttons'), true);
            if (is_array($decoded)) {
                $request->merge(['buttons' => $decoded]);
            } else {
                $request->merge(['buttons' => null]);
            }
        }
        
        $validated = $request->validate([
            'name' => 'required|string|regex:/^[a-z0-9_]+$/',
            'category' => 'required|string|in:MARKETING,UTILITY,AUTHENTICATION',
            'language' => 'required|string',
            'body' => 'required|string',
            'header_type' => 'nullable|string|in:none,text,image,document,video',
            'header_content' => 'nullable|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);
        
        $components = [];
        
        if (!empty($validated['header_type']) && $validated['header_type'] !== 'none') {
            $headerComponent = [
                'type' => 'HEADER',
                'format' => strtoupper($validated['header_type']),
            ];
            
            if ($validated['header_type'] === 'text' && !empty($validated['header_content'])) {
                $headerComponent['text'] = $validated['header_content'];
                if (preg_match_all('/\{\{(\d+)\}\}/', $validated['header_content'], $headerMatches)) {
                    $headerComponent['example'] = [
                        'header_text' => array_fill(0, count($headerMatches[1]), 'Sample')
                    ];
                }
            }
            
            $components[] = $headerComponent;
        }
        
        $bodyComponent = [
            'type' => 'BODY',
            'text' => $validated['body'],
        ];
        
        if (preg_match_all('/\{\{(\d+)\}\}/', $validated['body'], $bodyMatches)) {
            $bodyComponent['example'] = [
                'body_text' => [array_fill(0, count($bodyMatches[1]), 'Sample')]
            ];
        }
        
        $components[] = $bodyComponent;
        
        if (!empty($validated['footer'])) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $validated['footer']
            ];
        }
        
        if (!empty($validated['buttons'])) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => $validated['buttons']
            ];
        }
        
        $payload = [
            'name' => $validated['name'],
            'language' => $validated['language'],
            'category' => $validated['category'],
            'components' => $components,
        ];
        
        try {
            $response = Http::withToken($account->access_token)
                ->post("https://graph.facebook.com/{$account->api_version}/{$account->business_account_id}/message_templates", $payload);
                
            $result = $response->json();
            
            if ($response->successful() && isset($result['id'])) {
                WhatsappTemplate::create([
                    'whatsapp_account_id' => $account->id,
                    'name' => $validated['name'],
                    'category' => $validated['category'],
                    'language' => $validated['language'],
                    'header_type' => $validated['header_type'],
                    'header_content' => $validated['header_content'],
                    'body' => $validated['body'],
                    'footer' => $validated['footer'],
                    'buttons' => $validated['buttons'] ?? null,
                    'status' => 'pending',
                ]);
                
                return response()->json(['success' => true, 'message' => 'Template created and submitted for approval.']);
            }
            
            // Extract more detailed error message from Meta if available
            $errorMsg = $result['error']['message'] ?? 'Failed to submit template to Meta';
            if (isset($result['error']['error_user_msg'])) {
                $errorMsg .= ' - ' . $result['error']['error_user_msg'];
            }
            if (isset($result['error']['error_data']['details'])) {
                $errorMsg .= ' - Details: ' . $result['error']['error_data']['details'];
            }
            
            Log::error('Meta API Error Response: ' . json_encode($result));
            return response()->json(['success' => false, 'error' => $errorMsg, 'meta_response' => $result], 400);
            
        } catch (\Exception $e) {
            Log::error('Template creation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->firstOrFail();
        $template = WhatsappTemplate::where('id', $id)
            ->where('whatsapp_account_id', $account->id)
            ->firstOrFail();

        try {
            // Delete from Meta API
            $response = Http::withToken($account->access_token)
                ->delete("https://graph.facebook.com/{$account->api_version}/{$account->business_account_id}/message_templates", [
                    'name' => $template->name
                ]);
                
            $result = $response->json();
            $errorMessage = isset($result['error']['message']) ? strtolower($result['error']['message']) : '';
            
            // Meta may return success or error if it doesn't exist on their end.
            // We'll delete it locally regardless if it's not found on Meta or if parameters are invalid.
            if ($response->successful() || str_contains($errorMessage, 'does not exist') || str_contains($errorMessage, 'invalid parameter')) {
                $template->delete();
                return response()->json(['success' => true, 'message' => 'Template deleted successfully.']);
            }
            
            return response()->json(['success' => false, 'error' => $result['error']['message'] ?? 'Failed to delete template from Meta'], 400);
            
        } catch (\Exception $e) {
            Log::error('Template deletion failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
