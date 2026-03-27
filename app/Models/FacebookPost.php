<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookPost extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING = 'draft';
    public const STATUS_SCHEDULED = 'draft';
    public const STATUS_POSTED = 'published';
    public const STATUS_FAILED = 'draft';

    protected $fillable = [
        'page_id',
        'message',
        'image_url',
        'facebook_post_id',
        'instagram_media_id',
        'google_post_name',
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

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class, 'post_id');
    }
}
