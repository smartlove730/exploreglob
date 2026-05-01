<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SyncedSocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facebook_page_id',
        'platform',
        'external_post_id',
        'content',
        'media_preview_url',
        'permalink',
        'external_created_at',
        'last_synced_at',
    ];

    protected $casts = [
        'external_created_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'facebook_page_id');
    }

    public function latestDeletionJob(): HasOne
    {
        return $this->hasOne(SocialPostDeletionJob::class)->latestOfMany();
    }
}
