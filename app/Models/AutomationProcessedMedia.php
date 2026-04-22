<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationProcessedMedia extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    protected $table = 'automation_processed_media';

    protected $fillable = [
        'automation_id',
        'file_id',
        'folder_id',
        'status',
        'platform',
        'last_error',
        'failed_at',
    ];

    protected $casts = [
        'failed_at' => 'datetime',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(AutomationConfig::class, 'automation_id');
    }
}
