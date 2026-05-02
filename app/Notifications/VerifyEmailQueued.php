<?php

namespace App\Notifications;

use App\Services\DynamicMailConfigService;
use App\Services\EmailLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class VerifyEmailQueued extends Notification
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

        app(EmailLogService::class)->queued('email_verification', $notifiable->email, 'Verify Email OTP', $notifiable);
        Log::info('Queued email verification OTP mail.', ['user_id' => $notifiable->id, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject('Your email verification OTP')
            ->line('Use the OTP below to verify your email address.')
            ->line('OTP: '.$notifiable->email_verification_otp)
            ->line('This OTP is valid for 10 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('email_verification', 'unknown', $exception->getMessage(), 'Verify Email OTP');
        Log::error('Failed to send email verification OTP mail.', ['error' => $exception->getMessage()]);
    }
}
