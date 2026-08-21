<?php

namespace App\Events;

use App\Models\WhatsappMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsappMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(WhatsappMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast on a private channel for the specific conversation
        return [
            new PrivateChannel('whatsapp.conversation.' . $this->message->whatsapp_conversation_id),
            // Also broadcast on a generic channel for the account to update sidebar
            new PrivateChannel('whatsapp.account.' . $this->message->conversation->phoneNumber->whatsapp_account_id),
            new PrivateChannel('whatsapp.user.' . $this->message->conversation->phoneNumber->account->user_id),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'whatsapp_conversation_id' => $this->message->whatsapp_conversation_id,
            'direction' => $this->message->direction,
            'type' => $this->message->type,
            'content' => $this->message->content,
            'status' => $this->message->status,
            'created_at' => $this->message->created_at->format('g:i A'),
            'contact' => [
                'id' => $this->message->conversation->contact->id,
                'name' => $this->message->conversation->contact->name,
                'phone' => $this->message->conversation->contact->phone_number,
            ]
        ];
    }
}
