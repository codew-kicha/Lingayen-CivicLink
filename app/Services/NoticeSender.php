<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OfficeNotice;
use App\Models\User;
use App\Notifications\OfficeNoticeSent;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

use function Illuminate\Support\defer;

/**
 * Sends a Desk Office notice to an audience (one organization, a sector, a barangay, the accredited
 * ones, or everyone): in each representative's Notifications and, with $email, to their inbox after
 * the page has answered. Used by Notices and by the announcement of an LGU event.
 */
class NoticeSender
{
    /** The audience fields as posted (audience, audience_value). */
    public static function rules(Request $request): array
    {
        return [
            'audience' => ['required', Rule::in(array_keys(OfficeNotice::AUDIENCES))],
            'audience_value' => [
                'nullable', 'required_if:audience,organization,sector,barangay', 'string', 'max:255',
                Rule::when($request->input('audience') === 'sector', [Rule::in(config('sectors'))]),
                Rule::when($request->input('audience') === 'barangay', [Rule::in(config('barangays'))]),
            ],
        ];
    }

    /** The notice sent, or null when no organization account matches the audience. */
    public static function send(User $sender, string $title, string $body, string $audience, ?string $value, bool $email): ?OfficeNotice
    {
        $recipients = OfficeNotice::recipients($audience, $value)->get();
        if ($recipients->isEmpty()) {
            return null;
        }

        $notice = OfficeNotice::create([
            'sender_id' => $sender->id,
            'title' => $title,
            'body' => $body,
            'audience' => $audience,
            'audience_value' => in_array($audience, ['all', 'accredited'], true) ? null : $value,
            'recipients' => $recipients->count(),
            'emailed' => $email,
        ]);

        Notification::send($recipients, new OfficeNoticeSent($notice));
        if ($notice->emailed) {
            defer(fn () => $recipients->filter->hasVerifiedEmail()
                ->each(fn ($user) => Mailer::send($user, new OfficeNoticeSent($notice, ['mail']))));
        }

        AuditLog::record('notice.sent', $notice, ['audience' => $notice->audienceLabel(), 'recipients' => $notice->recipients, 'emailed' => $notice->emailed]);

        return $notice;
    }
}
