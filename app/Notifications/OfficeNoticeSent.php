<?php

namespace App\Notifications;

use App\Models\OfficeNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A Desk Office notice, delivered in the app and, when the office ticks "Also email", by email. The
 * two go separately (Admin\NoticeController) so the in-app copy is there at once.
 */
class OfficeNoticeSent extends Notification
{
    use Queueable;

    /** @param  array<int, string>  $channels */
    public function __construct(public OfficeNotice $notice, private array $channels = ['database']) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notice->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->lines(preg_split('/\R{2,}/', trim($this->notice->body)))
            ->action('Open CivicLink', route('cso.notifications.index'))
            ->salutation('Civil Society Desk Office, Lingayen');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => 'notice',
            'notice_id' => $this->notice->id,
            'headline' => $this->notice->title,
            'body' => $this->notice->body,
            'from' => 'Civil Society Desk Office',
        ];
    }
}
