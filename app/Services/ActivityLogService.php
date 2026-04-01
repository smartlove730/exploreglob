<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLogService
{
    public function log(string $action, ?User $user = null, array $context = []): void
    {
        $safeContext = Arr::except($context, [
            'token',
            'access_token',
            'refresh_token',
            'long_lived_user_token',
            'oauth_access_token',
            'oauth_refresh_token',
            'signature',
            'key_secret',
            'webhook_secret',
        ]);

        try {
            ActivityLog::query()->create([
                'user_id' => $user?->id,
                'action' => $action,
                'context' => $safeContext,
                'ip_address' => request()?->ip(),
                'user_agent' => (string) request()?->userAgent(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to persist activity log entry', [
                'action' => $action,
                'user_id' => $user?->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
