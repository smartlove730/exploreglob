<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookApp extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
        'name',
        'app_id',
        'app_secret',
        'redirect_uri',
        'is_active',
    ];

    protected $hidden = [
        'app_secret',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
