<?php

namespace App\Notifications;

use App\Services\EmailLogService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailQueued extends VerifyEmail
{

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
