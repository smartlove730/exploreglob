<?php

namespace App\Notifications;

use App\Services\EmailLogService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordQueued extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 180, 300];

    public function toMail($notifiable): MailMessage
    {
        app(EmailLogService::class)->queued('password_reset', $notifiable->email, 'Reset Password Notification', $notifiable);

        return parent::toMail($notifiable)->subject('Reset your password');
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('password_reset', 'unknown', $exception->getMessage(), 'Reset Password Notification');
    }
}
