<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AccountInvitation extends Notification
{
    use Queueable;

    public const VALID_HOURS = 72;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('invitation.show', now()->addHours(self::VALID_HOURS), ['user' => $notifiable->id]);

        $message = (new MailMessage)
            ->subject('Your Lingayen CivicLink account')
            ->greeting("Good day, {$notifiable->name}.");

        $message = $notifiable->isAdmin()
            ? $message->line('The Civil Society Desk Office has created a PESO administrator account for you on Lingayen CivicLink.')
            : $message->line('The Civil Society Desk Office has created an account for '.($notifiable->organization?->name ?? 'your organization').' on Lingayen CivicLink, where you can apply for accreditation and log your activities.');

        return $message
            ->action('Set your password', $url)
            ->line('This link works once and expires in '.self::VALID_HOURS.' hours. If it expires, ask the office to send a new one.')
            ->line('If you were not expecting this email, you can ignore it.');
    }
}
