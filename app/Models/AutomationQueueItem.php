<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationQueueItem extends Model
{
    use BelongsToUserScope;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'automation_rule_id',
        'user_id',
        'page_id',
        'facebook_post_id',
        'source_id',
        'media_type',
        'media_url',
        'caption',
        'platforms',
        'status',
        'attempts',
        'last_error',
        'response_json',
        'facebook_post_id_external',
        'instagram_media_id',
        'scheduled_for',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'response_json' => 'array',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }

    public function facebookPost(): BelongsTo
    {
        return $this->belongsTo(FacebookPost::class);
    }
}
