<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveFolder extends Model
{
    protected $fillable = [
        'name',
        'folder_url',
        'folder_id',
        'drive_api_key_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function driveApiKey(): BelongsTo
    {
        return $this->belongsTo(DriveApiKey::class, 'drive_api_key_id');
    }
}
