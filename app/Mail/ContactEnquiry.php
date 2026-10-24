<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// A message from the public Contact form, sent to the office's address (config/office.php).
// Reply-To is the visitor, so the office answers with a plain Reply.
class ContactEnquiry extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array{name: string, email: string, organization: ?string, message: string}  $enquiry */
    public function __construct(public array $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry['email'], $this->enquiry['name'])],
            subject: 'Website enquiry from '.$this->enquiry['name'].(filled($this->enquiry['organization'] ?? null) ? ' ('.$this->enquiry['organization'].')' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact-enquiry');
    }
}
