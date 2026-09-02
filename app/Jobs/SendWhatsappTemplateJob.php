<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WhatsappAccount;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappContact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsappTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $accountId;
    public $templateId;
    public $contactId;
    public $components;
    public $campaignId;

    public function __construct($accountId, $templateId, $contactId, $components = null, $campaignId = null)
    {
        $this->accountId = $accountId;
        $this->templateId = $templateId;
        $this->contactId = $contactId;
        $this->components = $components;
        $this->campaignId = $campaignId;
    }

    public function handle(): void
    {
        $account = WhatsappAccount::find($this->accountId);
        $template = WhatsappTemplate::find($this->templateId);
        $contact = WhatsappContact::find($this->contactId);

        if (!$account || !$template || !$contact) {
            return;
        }

        if (!$contact->opted_in) {
            Log::info('SendWhatsappTemplateJob: Skipped sending to opted-out contact', ['contact_id' => $contact->id]);
            return;
        }

        $phoneNumberId = $account->phone_number_id;
        $phoneNumberModel = null;

        if ($phoneNumberId) {
            $phoneNumberModel = $account->phoneNumbers()->where('phone_number_id', $phoneNumberId)->first();
        }
        
        if (!$phoneNumberModel) {
            $phoneNumberModel = $account->phoneNumbers()->first();
            if ($phoneNumberModel) {
                $phoneNumberId = $phoneNumberModel->phone_number_id;
            }
        }

        if (!$phoneNumberId || !$phoneNumberModel) {
            Log::error('SendWhatsappTemplateJob: No phone number id found for account ' . $account->id);
            return;
        }

        $conversation = \App\Models\WhatsappConversation::firstOrCreate([
            'whatsapp_phone_number_id' => $phoneNumberModel->id,
            'whatsapp_contact_id' => $contact->id,
        ], [
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $messageRecord = \App\Models\WhatsappMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'whatsapp_campaign_id' => $this->campaignId,
            'direction' => 'outbound',
            'type' => 'template',
            'content' => $template->name, // store template name as content
            'whatsapp_template_id' => $template->id,
            'status' => 'pending',
        ]);

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $contact->phone_number,
            "type" => "template",
            "template" => [
                "name" => $template->name,
                "language" => [
                    "code" => $template->language
                ]
            ]
        ];

        if (!empty($this->components)) {
            $payload['template']['components'] = $this->components;
        }

        try {
            $response = Http::withToken($account->access_token)
                ->post("https://graph.facebook.com/{$account->api_version}/{$phoneNumberId}/messages", $payload);
            
            $result = $response->json();
            
            if ($response->successful() && isset($result['messages'][0]['id'])) {
                $messageRecord->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $result['messages'][0]['id'],
                ]);
                $conversation->update(['last_message_at' => now()]);
            } else {
                Log::error('SendWhatsappTemplateJob: Failed to send template', ['response' => $result]);
                $messageRecord->update([
                    'status' => 'failed',
                    'error_message' => json_encode($result['error'] ?? $result),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SendWhatsappTemplateJob: Exception ' . $e->getMessage());
            $messageRecord->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
