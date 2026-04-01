<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserMedia extends Model
{
    use BelongsToUserScope;

    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    protected $table = 'user_media';

    protected $fillable = [
        'user_id',
        'type',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $appends = ['public_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return url(Storage::disk('public')->url($this->path));
    }
}
