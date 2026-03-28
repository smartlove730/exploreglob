<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveFolder extends Model
{
    use BelongsToUserScope;

    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driveApiKey(): BelongsTo
    {
        return $this->belongsTo(DriveApiKey::class, 'drive_api_key_id');
    }
}
