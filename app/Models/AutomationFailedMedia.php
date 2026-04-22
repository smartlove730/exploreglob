<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationFailedMedia extends Model
{
    use BelongsToUserScope;

    protected $table = 'automation_failed_media';

    protected $fillable = [
        'user_id',
        'automation_config_id',
        'page_id',
        'drive_folder_id',
        'drive_file_id',
        'drive_file_name',
        'media_type',
        'source_url',
        'platforms',
        'failure_reason',
        'last_failed_at',
        'fail_count',
    ];

    protected $casts = [
        'platforms' => 'array',
        'last_failed_at' => 'datetime',
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
