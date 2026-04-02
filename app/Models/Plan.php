<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const DEFAULT_POSTS_PER_DAY_LIMIT = 10;
    public const DEFAULT_POSTS_PER_WEEK_LIMIT = 50;
    public const DEFAULT_POSTS_PER_MONTH_LIMIT = 200;
    public const DEFAULT_AUTOMATION_LIMIT = 5;
    public const DEFAULT_CONNECTED_APPS_LIMIT = 2;
    public const DEFAULT_SYNCED_PAGES_LIMIT = 10;

    protected $fillable = [
        'name',
        'slug',
        'razorpay_plan_id',
        'price',
        'currency',
        'interval',
        'post_limit',
        'posts_per_day_limit',
        'posts_per_week_limit',
        'posts_per_month_limit',
        'automation_limit',
        'connected_apps_limit',
        'synced_pages_limit',
        'facebook_enabled',
        'instagram_enabled',
        'google_business_enabled',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'posts_per_day_limit' => 'integer',
        'posts_per_week_limit' => 'integer',
        'posts_per_month_limit' => 'integer',
        'automation_limit' => 'integer',
        'connected_apps_limit' => 'integer',
        'synced_pages_limit' => 'integer',
        'facebook_enabled' => 'boolean',
        'instagram_enabled' => 'boolean',
        'google_business_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function configuredLimit(string $key): int
    {
        $defaults = [
            'posts_per_day_limit' => self::DEFAULT_POSTS_PER_DAY_LIMIT,
            'posts_per_week_limit' => self::DEFAULT_POSTS_PER_WEEK_LIMIT,
            'posts_per_month_limit' => self::DEFAULT_POSTS_PER_MONTH_LIMIT,
            'automation_limit' => self::DEFAULT_AUTOMATION_LIMIT,
            'connected_apps_limit' => self::DEFAULT_CONNECTED_APPS_LIMIT,
            'synced_pages_limit' => self::DEFAULT_SYNCED_PAGES_LIMIT,
        ];

        $default = $defaults[$key] ?? 0;
        $value = (int) ($this->{$key} ?? 0);

        return $value > 0 ? $value : $default;
    }
}
