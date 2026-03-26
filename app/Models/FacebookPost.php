<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookPost extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'page_id',
        'message',
        'image_url',
        'platforms',
        'status',
        'scheduled_at',
        'posted_at',
        'response_json',
        'attempts',
    ];

    protected $casts = [
        'platforms' => 'array',
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
        'response_json' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
