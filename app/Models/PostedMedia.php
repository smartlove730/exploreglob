<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;

class PostedMedia extends Model
{
    use BelongsToUserScope;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';

    protected $table = 'posted_media';

    protected $fillable = [
        'user_id',
        'automation_config_id',
        'page_id',
        'drive_file_id',
        'platform',
        'status',
        'reserved_at',
        'posted_at',
        'last_error',
        'response_json',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'posted_at' => 'datetime',
        'response_json' => 'array',
    ];
}
