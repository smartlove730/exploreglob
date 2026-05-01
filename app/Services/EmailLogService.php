<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\User;

class EmailLogService
{
    public function queued(string $type, string $recipient, ?string $subject = null, ?User $user = null, array $meta = []): void
    {
        EmailLog::create([
            'user_id' => $user?->id,
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => 'queued',
            'meta' => $meta,
        ]);
    }

    public function failed(string $type, string $recipient, string $error, ?string $subject = null, ?User $user = null, array $meta = []): void
    {
        EmailLog::create([
            'user_id' => $user?->id,
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => 'failed',
            'error_message' => $error,
            'meta' => $meta,
        ]);
    }
}
