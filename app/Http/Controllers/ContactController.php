<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    // Emails the message to the office (config/office.php) with Reply-To set to the visitor, and keeps
    // a copy in storage/logs/enquiries.log so nothing is lost if the mail server is down.
    public function send(StoreContactMessageRequest $request): RedirectResponse
    {
        $enquiry = $request->validated();
        Log::channel('enquiries')->info('Contact enquiry', $enquiry);

        try {
            Mail::to(config('office.office.email'), config('office.office.name'))->send(new ContactEnquiry($enquiry));
        } catch (Throwable $e) {
            Log::channel('enquiries')->error('Contact enquiry could not be emailed', ['email' => $enquiry['email'], 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Your message could not be sent just now. Please try again later, or call or email the office directly.');
        }

        return back()->with('status', 'Thank you. Your message was sent to the Civil Society Desk Office, and they will reply to '.$enquiry['email'].'.');
    }
}
