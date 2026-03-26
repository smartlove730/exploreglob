<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    protected $fillable = [
        'facebook_account_id',
        'page_id',
        'page_name',
        'page_access_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class);
    }
}
