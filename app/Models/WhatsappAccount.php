<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
        'business_account_id',
        'phone_number_id',
        'access_token',
        'webhook_verify_token',
        'api_version',
        'use_embedded_signup',
        'auto_reply_enabled',
        'welcome_message',
        'away_message',
        'business_hours',
        'auto_reply_delay_seconds',
        'notify_email_enabled',
        'notify_email_address',
        'slack_webhook_url',
        'notify_events',
    ];

    protected $casts = [
        'use_embedded_signup' => 'boolean',
        'auto_reply_enabled' => 'boolean',
        'business_hours' => 'array',
        'notify_email_enabled' => 'boolean',
        'notify_events' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(WhatsappPhoneNumber::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }
}
