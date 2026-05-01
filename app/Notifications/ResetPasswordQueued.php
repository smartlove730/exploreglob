<?php

namespace App\Notifications;

use App\Services\DynamicMailConfigService;
use App\Services\EmailLogService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordQueued extends ResetPassword
{

    public function toMail($notifiable): MailMessage
    {
        // Ensure the active SMTP settings from the database are applied
        app(DynamicMailConfigService::class)->apply();

        app(EmailLogService::class)->queued('password_reset', $notifiable->email, 'Reset Password Notification', $notifiable);

        return parent::toMail($notifiable)->subject('Reset your password');
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('password_reset', 'unknown', $exception->getMessage(), 'Reset Password Notification');
    }
}
