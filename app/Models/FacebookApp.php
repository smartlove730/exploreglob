<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookApp extends Model
{
    protected $fillable = [
        'name',
        'app_id',
        'app_secret',
        'redirect_uri',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(FacebookAccount::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(FacebookPage::class, 'facebook_app_id');
    }
}
