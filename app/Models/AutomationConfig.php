<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationConfig extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'prompt',
        'drive_link',
        'drive_api_key_id',
        'app_id',
        'page_id',
        'platforms',
        'runs_per_day',
        'post_limit_per_day',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(FacebookApp::class, 'app_id');
    }

    public function driveApiKey(): BelongsTo
    {
        return $this->belongsTo(DriveApiKey::class, 'drive_api_key_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationPostLog::class);
    }
}
