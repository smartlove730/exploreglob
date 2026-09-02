<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappAccount;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappContact;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Events\WhatsappMessageReceived;

class WhatsappController extends Controller
{
    private $verifyToken = 'random_string_token_12345';

    public function verify(Request $request)
    {
        Log::info('WhatsApp Webhook Verification hit (GET)', $request->all());
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $this->verifyToken) {
                Log::info('WEBHOOK_VERIFIED');
                return response($challenge, 200);
            } else {
                Log::warning('WEBHOOK_VERIFICATION_FAILED', ['expected' => $this->verifyToken, 'received' => $token]);
                return response('Forbidden', 403);
            }
        }
        
        return response($this->verifyToken);
    }
    
    /**
     * Handle incoming WhatsApp messages and status updates (POST)
     */
    public function handleWebhook(Request $request)
    {
        // Log the incoming POST request data for debugging
        Log::info('WhatsApp Webhook Event received (POST)', $request->all());
        
        $body = $request->all();
        
        if (isset($body['object']) && $body['object'] === 'whatsapp_business_account') {
            
            $change = $body['entry'][0]['changes'][0] ?? null;
            $field = $change['field'] ?? null;
            $value = $change['value'] ?? null;
            
            if ($field === 'message_template_status_update') {
                $templateName = $value['message_template_name'] ?? null;
                $language = $value['message_template_language'] ?? null;
                $event = $value['event'] ?? null;
                
                if ($templateName && $language && $event) {
                    \App\Models\WhatsappTemplate::where('name', $templateName)
                        ->where('language', $language)
                        ->update(['status' => strtolower($event)]);
                        
                    Log::info('Template status updated via webhook', ['name' => $templateName, 'status' => $event]);
                }
                return response('EVENT_RECEIVED', 200);
            }
            
            if ($value && $field === 'messages') {
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                $displayPhoneNumber = $value['metadata']['display_phone_number'] ?? null;
                
                // Process incoming messages
                if (isset($value['messages'][0])) {
                    $message = $value['messages'][0];
                    $from = $message['from'] ?? null;
                    $msgBody = $message['text']['body'] ?? '';
                    $messageId = $message['id'];
                    
                    // Get contact profile name if available
                    $contactName = $value['contacts'][0]['profile']['name'] ?? $from;
                    
                    Log::info('Message received', ['from' => $from, 'body' => $msgBody, 'phone_number_id' => $phoneNumberId]);
                    
                    // Find the associated WhatsApp Account
                    $account = \App\Models\WhatsappAccount::where('phone_number_id', $phoneNumberId)->first();
                    
                    if ($account) {
                        // Get or create Phone Number record
                        $phoneNumber = \App\Models\WhatsappPhoneNumber::firstOrCreate(
                            ['phone_number_id' => $phoneNumberId],
                            [
                                'whatsapp_account_id' => $account->id, 
                                'phone_number' => $displayPhoneNumber ?? $phoneNumberId,
                                'display_name' => $displayPhoneNumber ?? $phoneNumberId,
                            ]
                        );
                        
                        // Get or create Contact
                        $contact = \App\Models\WhatsappContact::firstOrCreate(
                            ['user_id' => $account->user_id, 'phone_number' => $from],
                            ['name' => $contactName, 'opted_in' => true, 'last_message_at' => now()]
                        );
                        
                        // Check for Opt-in / Opt-out keywords
                        $msgTextLower = trim(strtolower($msgBody));
                        $optOutKeywords = ['stop', 'unsubscribe', 'cancel', 'quit'];
                        $optInKeywords = ['start', 'unstop', 'subscribe'];
                        
                        $optStatusChanged = false;
                        if (in_array($msgTextLower, $optOutKeywords)) {
                            $contact->opted_in = false;
                            $optStatusChanged = true;
                            Log::info('Contact opted OUT', ['contact_id' => $contact->id, 'phone' => $from]);
                        } elseif (in_array($msgTextLower, $optInKeywords)) {
                            $contact->opted_in = true;
                            $optStatusChanged = true;
                            Log::info('Contact opted IN', ['contact_id' => $contact->id, 'phone' => $from]);
                        }
                        
                        if ($optStatusChanged || $contact->name !== $contactName) {
                            if ($contact->name !== $contactName) {
                                $contact->name = $contactName;
                            }
                            $contact->last_message_at = now();
                            $contact->save();
                        } else {
                            $contact->update(['last_message_at' => now()]);
                        }
                        
                        // Get or create Conversation
                        $conversation = \App\Models\WhatsappConversation::firstOrCreate(
                            [
                                'whatsapp_phone_number_id' => $phoneNumber->id,
                                'whatsapp_contact_id' => $contact->id,
                            ],
                            ['status' => 'open', 'last_message_at' => now()]
                        );
                        
                        // Update conversation last_message_at
                        $conversation->update(['last_message_at' => now()]);
                        
                        // Create Message
                        $dbMessage = \App\Models\WhatsappMessage::create([
                            'whatsapp_conversation_id' => $conversation->id,
                            'direction' => 'inbound',
                            'type' => $message['type'] ?? 'text',
                            'content' => $message['type'] === 'text' ? $msgBody : json_encode($message),
                            'status' => 'received',
                            'whatsapp_message_id' => $messageId,
                        ]);
                        
                        // Broadcast if necessary
                        if (class_exists(\App\Events\WhatsappMessageReceived::class)) {
                            broadcast(new \App\Events\WhatsappMessageReceived($dbMessage))->toOthers();
                        }
                    } else {
                        Log::warning('Received WhatsApp message for unknown phone_number_id', ['phone_number_id' => $phoneNumberId]);
                    }
                }
                
            // Process message statuses (delivered, read, failed, etc.)
                if (isset($value['statuses'][0])) {
                    $statusData = $value['statuses'][0];
                    $messageId = $statusData['id'];
                    $status = $statusData['status'];
                    
                    $updateData = ['status' => $status];
                    
                    // Capture error message if status is failed
                    if ($status === 'failed' && isset($statusData['errors'][0])) {
                        $error = $statusData['errors'][0];
                        $errorMsg = $error['title'] ?? $error['message'] ?? 'Unknown error';
                        if (isset($error['code'])) {
                            $errorMsg = "Code {$error['code']}: {$errorMsg}";
                        }
                        if (isset($error['error_data']['details'])) {
                            $errorMsg .= " - " . $error['error_data']['details'];
                        }
                        $updateData['error_message'] = $errorMsg;
                    }
                    
                    \App\Models\WhatsappMessage::where('whatsapp_message_id', $messageId)->update($updateData);
                    
                    Log::info('Message status updated', ['id' => $messageId, 'status' => $status]);
                    
                    $dbMessage = \App\Models\WhatsappMessage::where('whatsapp_message_id', $messageId)->first();
                    if ($dbMessage && class_exists(\App\Events\WhatsappMessageReceived::class)) {
                        broadcast(new \App\Events\WhatsappMessageReceived($dbMessage))->toOthers();
                    }
                }
            }
            
            // Process messaging_handovers (if passed thread control)
            $messaging = $body['entry'][0]['messaging'][0] ?? null;
            if ($messaging) {
                if (isset($messaging['pass_thread_control'])) {
                    Log::info('messaging_handovers: Thread control passed to us', $messaging['pass_thread_control']);
                    // Additional logic can be added here (e.g. marking conversation as human-handled)
                }
                
                if (isset($messaging['take_thread_control'])) {
                    Log::info('messaging_handovers: Thread control taken from us', $messaging['take_thread_control']);
                }
            }
            
            // Return a '200 OK' response to all requests
            return response('EVENT_RECEIVED', 200);
        } else if (isset($body['object']) && $body['object'] === 'page') {
            // If they accidentally subscribed to Page webhooks (Messenger) for handovers
            $messaging = $body['entry'][0]['messaging'][0] ?? null;
            if ($messaging && isset($messaging['pass_thread_control'])) {
                Log::info('messaging_handovers (Page): Thread control passed', $messaging['pass_thread_control']);
            }
            return response('EVENT_RECEIVED', 200);
        } else {
            // Return a '404 Not Found' if event is not from a WhatsApp/Page API
            return response('', 404);
        }
    }
}
