<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveImagePost extends Model
{
    protected $fillable = [
        'page_id',
        'drive_file_id',
        'drive_folder_id',
        'image_url',
        'caption',
        'platforms',
        'facebook_post_id',
        'instagram_media_id',
        'response_json',
        'posted_at',
    ];

    protected $casts = [
        'platforms' => 'array',
        'response_json' => 'array',
        'posted_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
