<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappContact extends Model
{
    use BelongsToUserScope, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'opted_in',
        'last_message_at',
    ];

    protected $casts = [
        'opted_in' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(WhatsappGroup::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsappConversation::class);
    }
}
