<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRunLog extends Model
{
    protected $fillable = [
        'automation_rule_id',
        'automation_queue_item_id',
        'page_id',
        'status',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(AutomationQueueItem::class, 'automation_queue_item_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FacebookPage::class, 'page_id');
    }
}
