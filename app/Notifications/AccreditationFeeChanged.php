<?php

namespace App\Notifications;

use App\Models\ApplicationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when the Desk Office waives the accreditation fee on an application, or decides it applies
 * after all. Matters most while the organization is preparing its requirements: it should not pay
 * a fee that was waived, nor submit without a receipt that is now required.
 */
class AccreditationFeeChanged extends Notification
{
    use Queueable;

    public function __construct(public ApplicationModel $application, public bool $reopened = false) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your accreditation application: '.$this->headline())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body())
            ->action($this->application->status === 'requirements_pending' ? 'Upload your requirements' : 'View your application', $this->url())
            ->salutation('Public Employment Service Office, Lingayen');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'fee',
            'application_id' => $this->application->id,
            'fee_waived' => $this->application->fee_waived,
            'headline' => $this->headline(),
            'body' => $this->body(),
            'url' => $this->url(),
        ];
    }

    public function headline(): string
    {
        return $this->application->fee_waived ? 'No accreditation fee for your application' : 'The accreditation fee applies to your application';
    }

    private function body(): string
    {
        if ($this->application->fee_waived) {
            return 'The Desk Office waived the accreditation fee on your application. You do not need to pay or upload an official receipt. '
                .'Your accreditation will run for '.$this->application->validityYears().' years once approved.';
        }

        return 'Pay the '.$this->application->feeLabel().' at the Municipal Treasury and upload the official receipt with your requirements.'
            .($this->reopened ? ' Your requirements were reopened so you can add it.' : '')
            .' Accreditation filed outside a free period runs for one year and is renewed yearly.';
    }

    private function url(): string
    {
        return $this->application->status === 'requirements_pending'
            ? route('cso.application.requirements.show')
            : route('cso.applications.show', $this->application);
    }
}
