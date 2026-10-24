<?php

namespace App\Notifications;

use App\Models\Accreditation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent by the Desk Office from the organization page or the expiring list ("Send reminder").
 * There is no scheduled sender: the office decides when to remind.
 */
class AccreditationExpiring extends Notification
{
    use Queueable;

    public function __construct(public Accreditation $accreditation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your CSO accreditation expires on '.$this->accreditation->expires_at->format('d F Y'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body())
            ->line('File the renewal online from your dashboard. The office reviews it the same way as a new application.')
            ->action('File a renewal', route('cso.applications.index', ['apply' => 1]))
            ->salutation('Civil Society Desk Office, Lingayen');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'accreditation_id' => $this->accreditation->id,
            'headline' => 'Accreditation expiring',
            'body' => $this->body(),
            'url' => route('cso.applications.index', ['apply' => 1]),
        ];
    }

    private function body(): string
    {
        $days = max(0, (int) now()->startOfDay()->diffInDays($this->accreditation->expires_at->copy()->startOfDay(), false));

        return "Your organization's accreditation ({$this->accreditation->verification_code}) expires on "
            .$this->accreditation->expires_at->format('d F Y')
            .($days ? ", {$days} ".str('day')->plural($days).' from now.' : ', today.')
            .' Renew before then to stay in the public directory.';
    }
}
