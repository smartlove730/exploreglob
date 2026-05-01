<?php

namespace App\Notifications;

use App\Models\FacebookPost;
use App\Models\ScheduledPost;
use App\Services\EmailLogService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostFailedNotification extends Notification
{

    public function __construct(
        public FacebookPost|ScheduledPost $post,
        public string $error,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        app(EmailLogService::class)->queued('failed_post', $notifiable->email, 'Post publishing failed', $notifiable, [
            'post_id' => $this->post->id,
            'post_type' => $this->post::class,
        ]);

        return (new MailMessage)
            ->subject('Post publishing failed')
            ->greeting('Postzy could not publish one of your posts')
            ->line('A social post failed after the publishing queue retried it.')
            ->line('Post ID: '.$this->post->id)
            ->line('Error: '.$this->error)
            ->action('Review Posts', route('admin.posts.index'));
    }

    public function failed(\Throwable $exception): void
    {
        app(EmailLogService::class)->failed('failed_post', 'unknown', $exception->getMessage(), 'Post publishing failed', null, [
            'post_id' => $this->post->id,
            'post_type' => $this->post::class,
        ]);
    }
}
