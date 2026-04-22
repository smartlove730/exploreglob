<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
