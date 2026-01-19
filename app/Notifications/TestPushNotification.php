<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification; 
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
class TestPushNotification extends Notification
{
    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('✅ Laravel 12 Push')
            ->body('WebPush v10 works!');
    }
}
