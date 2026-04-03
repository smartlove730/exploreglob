<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationPostLog extends Model
{
    use BelongsToUserScope;

    protected $table = 'automation_posts_logs';

    protected $fillable = [
        'user_id',
        'automation_config_id',
        'page_id',
        'drive_file_id',
        'drive_file_name',
        'image_url',
        'caption',
        'platforms',
        'facebook_post_id',
        'instagram_media_id',
        'status',
        'message',
        'response_json',
        'scheduled_for',
        'started_at',
        'completed_at',
        'posted_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'response_json' => 'array',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function automationConfig(): BelongsTo
    {
        return $this->belongsTo(AutomationConfig::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
