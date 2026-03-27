<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationPostLog extends Model
{
    protected $table = 'automation_posts_logs';

    protected $fillable = [
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
        'posted_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'response_json' => 'array',
        'posted_at' => 'datetime',
    ];

    public function automationConfig(): BelongsTo
    {
        return $this->belongsTo(AutomationConfig::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
