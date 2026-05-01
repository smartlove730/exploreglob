<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveApiKey extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'description',
        'email',
        'redirect_url',
        'is_active',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_expires_at',
        'oauth_token_last_refreshed_at',
        'oauth_reauthorization_required',
        'oauth_reauthorization_reason',
    ];

    protected $hidden = [
        'api_key',
        'oauth_access_token',
        'oauth_refresh_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'oauth_expires_at' => 'datetime',
        'oauth_token_last_refreshed_at' => 'datetime',
        'oauth_reauthorization_required' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
