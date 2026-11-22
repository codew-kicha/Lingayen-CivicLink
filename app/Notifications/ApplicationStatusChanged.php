<?php

namespace App\Notifications;

use App\Models\ApplicationModel;
use App\Services\ApplicationSteps;
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
        if ($this->application->status === 'requirements_pending' && $this->application->requirements_note) {
            $message->line('What to fix: '.$this->application->requirements_note);
        }

        return $message
            ->action($this->application->status === 'requirements_pending' ? 'Upload your requirements' : 'View your application', $this->url())
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
            'phase' => $this->application->phase(),
            'headline' => $this->headline(),
            'body' => $this->body(),
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return $this->application->status === 'requirements_pending'
            ? route('cso.application.requirements.show')
            : route('cso.applications.show', $this->application);
    }

    private function headline(): string
    {
        return match ($this->application->status) {
            'approved' => 'Approved',
            'rejected' => 'Not approved',
            'requirements_pending' => $this->application->requirements_note ? 'Requirements returned for correction' : 'Application form approved: upload your requirements',
            'requirements_submitted' => 'Application form approved',
            'under_review' => 'Forwarded to the committee for hearing',
            default => Str::headline($this->application->status),
        };
    }

    private function body(): string
    {
        return match ($this->application->status) {
            'approved' => 'Your organization is now accredited and listed in the public directory. Your accreditation runs until '
                .$this->application->accreditation?->expires_at?->format('d F Y')
                .'. Its reference code is '.$this->application->accreditation?->verification_code.'; quote it when you deal with the Desk Office.',
            'rejected' => 'Your application was not approved. You may correct the issues raised and file again.',
            'requirements_pending' => $this->application->requirements_note
                ? 'The Desk Office returned your requirements: '.$this->application->requirements_note.' Upload the corrected files and submit them again.'
                : 'The Desk Office approved your application form. You can now upload the other requirements: '
                    .collect(ApplicationSteps::uploads($this->application))->map(fn (array $type) => $type['label'].($type['optional'] ? ' (optional)' : ''))->join('; ')
                    .'. '.($this->application->fee_waived
                        ? 'There is no accreditation fee: '.$this->application->feeWaiverReason().'.'
                        : 'Pay the '.$this->application->feeLabel().' at the Municipal Treasury and upload its official receipt.')
                    .' Once they are validated, your application goes to the Sangguniang Bayan committee for its hearing.',
            'requirements_submitted' => 'The Desk Office approved your application form. Its requirements are on file and are being validated.',
            'under_review' => 'The Desk Office validated your application and forwarded it to the Sangguniang Bayan committee for its hearing. You will be told the outcome.',
            default => 'Your application is now: '.Str::lower($this->application->phaseNote()).'.',
        };
    }
}
