<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_account_id',
        'campaign_id',
        'whatsapp_template_id',
        'status',
        'scheduled_at',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function account()
    {
        return $this->belongsTo(\App\Models\WhatsappAccount::class, 'whatsapp_account_id');
    }

    public function template()
    {
        return $this->belongsTo(\App\Models\WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function messages()
    {
        return $this->hasMany(\App\Models\WhatsappMessage::class, 'whatsapp_campaign_id');
    }
}
