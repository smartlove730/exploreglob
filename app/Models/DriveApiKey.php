<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveApiKey extends Model
{
    protected $fillable = [
        'name',
        'api_key',
        'description',
        'email',
        'redirect_url',
        'is_active',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'oauth_expires_at' => 'datetime',
    ];
}
