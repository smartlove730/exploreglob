<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'whatsapp_conversation_id',
        'whatsapp_campaign_id',
        'direction',
        'type',
        'content',
        'whatsapp_template_id',
        'status',
        'whatsapp_message_id',
        'error_message',
        'media_url',
        'media_mime_type',
        'media_filename',
        'media_caption',
        'reaction_emoji',
        'reaction_whatsapp_message_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'whatsapp_conversation_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}
