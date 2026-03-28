<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'razorpay_plan_id',
        'price',
        'currency',
        'interval',
        'post_limit',
        'facebook_enabled',
        'instagram_enabled',
        'google_business_enabled',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'facebook_enabled' => 'boolean',
        'instagram_enabled' => 'boolean',
        'google_business_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
