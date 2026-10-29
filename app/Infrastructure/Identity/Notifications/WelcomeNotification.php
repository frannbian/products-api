<?php

namespace App\Infrastructure\Identity\Notifications;

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(UserModel $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(UserModel $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail.welcome.subject', ['app' => config('app.name')]))
            ->greeting(__('mail.welcome.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.welcome.body'));
    }
}
