<?php

namespace App\Notifications;

use App\Models\ApplicationModel;
use App\Models\ApplicationReading;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the CSO after each Sangguniang Bayan reading is recorded (pre-final defense panel): which
 * reading, when it was held, the outcome, the office's remarks, and what happens next. In the app
 * and by email, like the other application updates.
 */
class SbReadingRecorded extends Notification
{
    use Queueable;

    public function __construct(public ApplicationModel $application, public ApplicationReading $reading) {}

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
            ->line('Outcome: '.$this->reading->outcomeLabel().'.');

        if (filled($this->reading->remarks)) {
            $message->line('Remarks from the Desk Office: '.$this->reading->remarks);
        }

        return $message
            ->line($this->reading->nextStep())
            ->action('View your application', route('cso.applications.show', $this->application))
            ->salutation('Public Employment Service Office, Lingayen');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'reading',
            'application_id' => $this->application->id,
            'reading_id' => $this->reading->id,
            'stage' => $this->reading->stage,
            'outcome' => $this->reading->outcome,
            'headline' => $this->headline(),
            'body' => $this->summary()
                .(filled($this->reading->remarks) ? "\nRemarks: ".$this->reading->remarks : '')
                ."\n".$this->reading->nextStep(),
            'url' => route('cso.applications.show', $this->application),
        ];
    }

    /** "First reading: Passed", "Second reading: Deferred", "Endorsed by the Sangguniang Bayan". */
    public function headline(): string
    {
        return $this->reading->stage === 'endorsed'
            ? 'Endorsed by the Sangguniang Bayan'
            : $this->reading->stageLabel().': '.$this->reading->outcomeLabel();
    }

    private function summary(): string
    {
        $when = $this->reading->held_on->format('j F Y');

        return $this->reading->stage === 'endorsed'
            ? "The Sangguniang Bayan endorsed your application on {$when}."
            : "The Sangguniang Bayan held the {$this->lowerStage()} of your application on {$when}.";
    }

    private function lowerStage(): string
    {
        return strtolower($this->reading->stageLabel());
    }
}
