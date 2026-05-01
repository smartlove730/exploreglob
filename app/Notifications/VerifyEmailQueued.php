<?php

namespace App\Notifications;

use App\Services\EmailLogService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 180, 300];

    public function toMail($notifiable): MailMessage
    {
        app(EmailLogService::class)->queued('email_verification', $notifiable->email, 'Verify Email Address', $notifiable);

        return parent::toMail($notifiable)->subject('Verify your email address');
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('email_verification', 'unknown', $exception->getMessage(), 'Verify Email Address');
    }
}
