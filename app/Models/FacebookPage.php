<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPage extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
        'facebook_account_id',
        'facebook_app_id',
        'page_id',
        'page_name',
        'page_access_token',
        'instagram_business_account_id',
        'is_active',
    ];

    protected $hidden = [
        'page_access_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(FacebookApp::class, 'facebook_app_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class);
    }
}
