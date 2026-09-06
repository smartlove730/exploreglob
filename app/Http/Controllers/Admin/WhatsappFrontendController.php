<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappPhoneNumber;
use App\Events\WhatsappMessageReceived;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappFrontendController extends Controller
{
    public function getConversations(Request $request)
    {
        // For now, get all conversations for the user's accounts
        $userId = auth()->id() ?? 1; // Fallback to 1 for testing if needed
        
        $conversations = WhatsappConversation::whereHas('phoneNumber.account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['contact', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function($query) {
                $query->where('direction', 'inbound')->where('status', 'received');
            }])
            ->orderBy('last_message_at', 'desc')
            ->get();
            
        return response()->json($conversations);
    }
    
    public function getMessages($conversationId)
    {
        $userId = auth()->id() ?? 1;
        
        $conversation = WhatsappConversation::whereHas('phoneNumber.account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('contact', 'phoneNumber')
            ->findOrFail($conversationId);
            
        // Mark unread inbound messages as read
        $unreadMessages = WhatsappMessage::where('whatsapp_conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->where('status', 'received')
            ->get();
            
        if ($unreadMessages->count() > 0) {
            WhatsappMessage::whereIn('id', $unreadMessages->pluck('id'))->update(['status' => 'read']);
            
            // Optionally, we could send a Read Receipt to WhatsApp Meta API here
            $account = $conversation->phoneNumber->account;
            foreach($unreadMessages as $msg) {
                if ($msg->whatsapp_message_id) {
                    try {
                        Http::withToken($account->access_token)
                            ->post("https://graph.facebook.com/{$account->api_version}/{$conversation->phoneNumber->phone_number_id}/messages", [
                                'messaging_product' => 'whatsapp',
                                'status' => 'read',
                                'message_id' => $msg->whatsapp_message_id,
                            ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to send read receipt to Meta: ' . $e->getMessage());
                    }
                }
            }
        }
            
        $messages = WhatsappMessage::where('whatsapp_conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();
            
        $lastInboundMessage = WhatsappMessage::where('whatsapp_conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();
            
        $isWithin24Hours = false;
        if ($lastInboundMessage) {
            $isWithin24Hours = $lastInboundMessage->created_at->diffInHours(now()) < 24;
        }
            
        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
            'is_within_24_hours' => $isWithin24Hours
        ]);
    }
    
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'message' => 'required|string'
        ]);
        
        $userId = auth()->id() ?? 1;
        
        $conversation = WhatsappConversation::whereHas('phoneNumber.account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('phoneNumber.account', 'contact')
            ->findOrFail($conversationId);
            
        $account = $conversation->phoneNumber->account;
        $contact = $conversation->contact;
        
        $lastInboundMessage = WhatsappMessage::where('whatsapp_conversation_id', $conversationId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();
            
        $isWithin24Hours = false;
        if ($lastInboundMessage) {
            $isWithin24Hours = $lastInboundMessage->created_at->diffInHours(now()) < 24;
        }

        if (!$isWithin24Hours) {
            return response()->json([
                'success' => false, 
                'error' => 'The 24-hour window has closed. You cannot send free-form messages to this user.'
            ], 403);
        }
        
        $messageText = $request->input('message');
        
        // 1. Save message to DB immediately as 'sending'
        $message = WhatsappMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'type' => 'text',
            'content' => $messageText,
            'status' => 'pending',
        ]);
        
        // 2. Call Meta API to send message
        try {
            $response = Http::withToken($account->access_token)
                ->post("https://graph.facebook.com/{$account->api_version}/{$conversation->phoneNumber->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $contact->phone_number,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $messageText
                    ]
                ]);
                
            $result = $response->json();
            
            if ($response->successful() && isset($result['messages'][0]['id'])) {
                $message->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $result['messages'][0]['id']
                ]);
            } else {
                $message->update([
                    'status' => 'failed',
                    'error_message' => json_encode($result)
                ]);
            }
            
            // Broadcast the event so sender's UI updates
            broadcast(new WhatsappMessageReceived($message))->toOthers();
            
            return response()->json(['success' => true, 'message' => $message]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message: ' . $e->getMessage());
            
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function sendReaction(Request $request, $conversationId)
    {
        $request->validate([
            'message_id' => 'required|integer',
            'emoji' => 'required|string|max:20'
        ]);
        
        $userId = auth()->id() ?? 1;
        
        $conversation = WhatsappConversation::whereHas('phoneNumber.account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('phoneNumber.account', 'contact')
            ->findOrFail($conversationId);
            
        $account = $conversation->phoneNumber->account;
        $message = WhatsappMessage::where('whatsapp_conversation_id', $conversationId)
            ->findOrFail($request->input('message_id'));
        
        if (!$message->whatsapp_message_id) {
            return response()->json(['success' => false, 'error' => 'Cannot react to this message'], 400);
        }
        
        try {
            $response = Http::withToken($account->access_token)
                ->post("https://graph.facebook.com/{$account->api_version}/{$conversation->phoneNumber->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $conversation->contact->phone_number,
                    'type' => 'reaction',
                    'reaction' => [
                        'message_id' => $message->whatsapp_message_id,
                        'emoji' => $request->input('emoji')
                    ]
                ]);
                
            if ($response->successful()) {
                $message->update(['reaction_emoji' => $request->input('emoji')]);
                return response()->json(['success' => true]);
            }
            
            return response()->json(['success' => false, 'error' => 'Failed to send reaction'], 500);
        } catch (\Exception $e) {
            Log::error('Failed to send reaction: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function checkUpdates(Request $request)
    {
        $userId = auth()->id() ?? 1;
        $lastCheckTime = $request->input('last_check_time');
        
        if (!$lastCheckTime) {
            return response()->json([
                'has_updates' => false,
                'server_time' => round(microtime(true) * 1000)
            ]);
        }
        
        $hasUpdates = WhatsappMessage::whereHas('conversation.phoneNumber.account', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('updated_at', '>', \Carbon\Carbon::createFromTimestamp($lastCheckTime / 1000))
            ->exists();
            
        return response()->json([
            'has_updates' => $hasUpdates,
            'server_time' => round(microtime(true) * 1000)
        ]);
    }
}
