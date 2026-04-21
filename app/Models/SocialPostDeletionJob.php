<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostDeletionJob extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'facebook_page_id',
        'synced_social_post_id',
        'platform',
        'external_post_id',
        'post_created_at',
        'content_preview',
        'media_preview_url',
        'status',
        'error_message',
        'scheduled_for',
        'processed_at',
        'attempts_count',
        'meta',
    ];

    protected $casts = [
        'post_created_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'attempts_count' => 'integer',
        'meta' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id');
    }

    public function syncedPost(): BelongsTo
    {
        return $this->belongsTo(SyncedSocialPost::class, 'synced_social_post_id');
    }
}
