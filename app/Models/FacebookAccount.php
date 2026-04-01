<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookAccount extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
        'facebook_app_id',
        'long_lived_user_token',
        'token_expires_at',
        'token_last_refreshed_at',
        'reauthorization_required',
        'reauthorization_reason',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'token_last_refreshed_at' => 'datetime',
        'reauthorization_required' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(FacebookApp::class, "facebook_app_id");
    }

    public function pages(): HasMany
    {
        return $this->hasMany(FacebookPage::class);
    }
}
