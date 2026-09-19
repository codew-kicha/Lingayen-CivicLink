<?php

namespace App\Notifications;

use App\Models\ApplicationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public ApplicationModel $application) {}

    /**
     * SMS lands with the Brevo integration in Milestone 2 (PRD §12).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your accreditation application: '.$this->headline())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body());

        if ($this->application->status === 'rejected' && $this->application->rejection_reason) {
            $message->line('Reason given: '.$this->application->rejection_reason);
        }

        return $message
            ->action('View your application', route('cso.applications.show', $this->application))
            ->salutation('Public Employment Service Office, Lingayen');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'status' => $this->application->status,
            'sb_stage' => $this->application->sb_stage,
            'headline' => $this->headline(),
            'body' => $this->body(),
            'url' => route('cso.applications.show', $this->application),
        ];
    }

    private function headline(): string
    {
        return match ($this->application->status) {
            'approved' => 'Approved',
            'rejected' => 'Not approved',
            default => Str::headline($this->application->sb_stage),
        };
    }

    private function body(): string
    {
        return match ($this->application->status) {
            'approved' => 'Your organization is now accredited and listed in the public directory. Your accreditation runs until '
                .$this->application->accreditation?->expires_at?->format('d F Y').'.',
            'rejected' => 'Your application was not approved. You may correct the issues raised and file again.',
            default => 'Your application is now at the '.Str::lower(Str::headline($this->application->sb_stage))
                .' stage with the Sangguniang Bayan.',
        };
    }
}
