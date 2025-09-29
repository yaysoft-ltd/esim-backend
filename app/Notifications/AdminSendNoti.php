<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class AdminSendNoti extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $description;
    protected $image;

    public function __construct($title, $description,$image)
    {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
    }

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(
            notification: new FcmNotification(
                title: (string) $this->title,
                body: (string) $this->description ?? '',
                image: $this->image ? asset($this->image) : ''
            )
        ))
            ->data([
                'type' => '8',
            ]);
    }
}
