<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The 6-digit code a new account types on the "Check your email" page. Receiving it proves the
 * address is real and belongs to the person registering. Sent at once, never queued.
 */
class VerifyEmailCode extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->code} is your CSO Desk Office confirmation code")
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this code to confirm your email address and finish registering your organization:')
            ->line("# {$this->code}")
            ->line('The code expires in '.User::EMAIL_CODE_MINUTES.' minutes. Never share it with anyone, including office staff.')
            ->line('If you did not register on the CSO Desk Office website, you can ignore this email.')
            ->salutation('Civil Society Desk Office, Lingayen');
    }
}
