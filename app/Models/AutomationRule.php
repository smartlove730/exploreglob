<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use BelongsToUserScope;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'user_id',
        'app_id',
        'name',
        'page_ids',
        'platforms',
        'media_source_type',
        'media_source_payload',
        'post_frequency',
        'schedule_times',
        'timezone',
        'daily_limit',
        'caption_templates',
        'hashtag_templates',
        'status',
        'next_run_at',
        'last_run_at',
        'paused_at',
        'stopped_at',
        'queued_count',
        'success_count',
        'failed_count',
    ];

    protected $casts = [
        'page_ids' => 'array',
        'platforms' => 'array',
        'media_source_payload' => 'array',
        'schedule_times' => 'array',
        'caption_templates' => 'array',
        'hashtag_templates' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(FacebookApp::class, 'app_id');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(AutomationQueueItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationRunLog::class);
    }
}
