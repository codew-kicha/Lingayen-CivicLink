<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sends a notification without letting a mail outage undo or hide the action around it. The
 * decision (approval, verification, invitation) is already saved when this runs; if the mail server
 * refuses, the error is logged and the caller tells the admin the email didn't go out.
 */
class Mailer
{
    /** Notice for the admin when the email failed, to flash next to the success message. */
    public const FAILED = 'Saved, but the email to the organization could not be sent. Check the mail settings in System check.';

    /** True when sent (or there was nobody to send to), false when the mail server failed. */
    public static function send(?User $user, Notification $notification): bool
    {
        if (! $user) {
            return true;
        }

        try {
            $user->notify($notification);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
