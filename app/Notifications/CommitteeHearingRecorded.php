<?php

namespace App\Notifications;

use App\Models\ApplicationHearing;
use App\Models\ApplicationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the organization after the Desk Office records its Sangguniang Bayan committee hearing:
 * when it was held, the outcome, the office's remarks, and what happens next. In the app and by
 * email, like the other application updates.
 */
class CommitteeHearingRecorded extends Notification
{
    use Queueable;

    public function __construct(public ApplicationModel $application, public ApplicationHearing $hearing) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your accreditation application: '.$this->headline())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->summary())
            ->line('Outcome: '.$this->hearing->outcomeLabel().'. '.$this->hearing->meaning());

        if (filled($this->hearing->remarks)) {
            $message->line('Remarks from the Desk Office: '.$this->hearing->remarks);
        }

        return $message
            ->line($this->hearing->nextStep())
            ->action('View your application', route('cso.applications.show', $this->application))
            ->salutation('Public Employment Service Office, Lingayen');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'hearing',
            'application_id' => $this->application->id,
            'hearing_id' => $this->hearing->id,
            'outcome' => $this->hearing->outcome,
            'headline' => $this->headline(),
            'body' => $this->summary()
                ."\n".$this->hearing->meaning()
                .(filled($this->hearing->remarks) ? "\nRemarks: ".$this->hearing->remarks : '')
                ."\n".$this->hearing->nextStep(),
            'url' => route('cso.applications.show', $this->application),
        ];
    }

    /** "Committee hearing: Favorable". */
    public function headline(): string
    {
        return 'Committee hearing: '.$this->hearing->outcomeLabel();
    }

    private function summary(): string
    {
        $committee = filled($this->hearing->committee) ? 'The '.$this->hearing->committee : 'The Sangguniang Bayan committee';

        return "{$committee} heard your application on {$this->hearing->held_on->format('j F Y')}.";
    }
}
