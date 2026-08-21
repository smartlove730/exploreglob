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
        if ($account) {
            $templates = WhatsappTemplate::where('whatsapp_account_id', $account->id)->orderBy('created_at', 'desc')->get();
        }
        
        return view('admin.whatsapp.templates', compact('templates', 'account'));
    }
    
    public function store(Request $request)
    {
        $account = WhatsappAccount::where('user_id', auth()->id())->firstOrFail();
        
        if (!$account->business_account_id) {
            return response()->json(['success' => false, 'error' => 'WhatsApp Business Account ID is missing in settings.'], 400);
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
