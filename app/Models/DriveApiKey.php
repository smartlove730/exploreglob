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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
