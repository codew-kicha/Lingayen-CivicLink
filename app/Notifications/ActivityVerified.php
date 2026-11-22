<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityVerified extends Notification
{
    use Queueable;

    /** @param  array<int, string>  $channels  in the app and by email; an LGU event's attendance is in the app only */
    public function __construct(public Activity $activity, public array $channels = ['database', 'mail']) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verified = $this->activity->status === 'verified';

        $message = (new MailMessage)
            ->subject('Activity '.($verified ? 'verified' : 'not verified').': '.$this->activity->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body());

        if (! $verified && $this->activity->rejection_reason) {
            $message->line('Reason given: '.$this->activity->rejection_reason);
        }

        return $message
            ->action('View your activities', route('cso.activities.index'))
            ->salutation('Public Employment Service Office, Lingayen');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'activity_id' => $this->activity->id,
            'status' => $this->activity->status,
            'headline' => $this->activity->lgu_event_id ? 'LGU event attendance recorded' : ($this->activity->status === 'verified' ? 'Activity verified' : 'Activity not verified'),
            'body' => $this->body(),
            'url' => route('cso.activities.index'),
        ];
    }

    private function body(): string
    {
        if ($this->activity->lgu_event_id) {
            return 'The Desk Office recorded your organization\'s attendance at the LGU event "'.$this->activity->title.'" ('
                .$this->activity->activity_date->format('d M Y').'). It counts toward your performance score as an LGU-organized activity.';
        }

        return $this->activity->status === 'verified'
            ? '"'.$this->activity->title.'" has been verified by PESO and now counts toward your performance score.'
            : '"'.$this->activity->title.'" was not verified. You may correct the details and log it again.';
    }
}
