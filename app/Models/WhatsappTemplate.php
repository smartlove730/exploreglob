<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'whatsapp_account_id',
        'name',
        'category',
        'language',
        'header_type',
        'header_content',
        'body',
        'footer',
        'buttons',
        'status',
    ];

    protected $casts = [
        'buttons' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }
}
