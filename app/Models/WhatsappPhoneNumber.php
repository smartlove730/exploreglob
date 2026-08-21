<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappPhoneNumber extends Model
{
    protected $fillable = [
        'whatsapp_account_id',
        'phone_number_id',
        'phone_number',
        'display_name',
        'quality_rating',
        'status',
        'messaging_limit_tier',
        'business_profile_name',
        'business_profile_category',
        'business_profile_description',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }
}
