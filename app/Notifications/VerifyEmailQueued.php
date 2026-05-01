<?php

namespace App\Notifications;

use App\Services\DynamicMailConfigService;
use App\Services\EmailLogService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;

class VerifyEmailQueued extends VerifyEmail
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Ensure the active SMTP settings from the database are applied
        // (.env defaults to 'log' driver which just writes to laravel.log)
        app(DynamicMailConfigService::class)->apply();

        app(EmailLogService::class)->queued('email_verification', $notifiable->email, 'Verify Email Address', $notifiable);

        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create an account, no further action is required.');
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('email_verification', 'unknown', $exception->getMessage(), 'Verify Email Address');
    }
}
