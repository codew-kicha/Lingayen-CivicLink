<?php

namespace Tests\Feature;

use App\Mail\ContactEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** The public Contact form emails the office, with Reply-To set to the visitor. */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private array $message = [
        'name' => 'Juan dela Cruz', 'email' => 'juan@example.ph', 'organization' => 'Baay Pedicab Drivers Association',
        'message' => 'How do we renew our accreditation?',
    ];

    public function test_a_message_is_emailed_to_the_office_and_replies_go_to_the_visitor(): void
    {
        Mail::fake();

        $this->from(route('contact'))->post(route('contact.send'), $this->message)
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status', 'Thank you. Your message was sent to the Civil Society Desk Office, and they will reply to juan@example.ph.');

        Mail::assertSent(ContactEnquiry::class, function (ContactEnquiry $mail) {
            $mail->assertTo(config('office.office.email'));
            $mail->assertHasReplyTo('juan@example.ph', 'Juan dela Cruz');
            $mail->assertHasSubject('Website enquiry from Juan dela Cruz (Baay Pedicab Drivers Association)');
            $mail->assertSeeInHtml('How do we renew our accreditation?');

            return true;
        });

        $this->get(route('contact'))->assertSee('they will reply to juan@example.ph');
    }

    public function test_the_visitor_is_told_when_the_email_could_not_be_sent(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->from(route('contact'))->post(route('contact.send'), $this->message)
            ->assertRedirect(route('contact'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('message', 'How do we renew our accreditation?');
    }

    public function test_the_form_explains_what_to_put_in_the_email_field(): void
    {
        $this->get(route('contact'))->assertOk()
            ->assertSee('Your own email, so the office can write back to you.')
            ->assertSee('It goes to the Civil Society Desk Office')
            ->assertSee('Required');
    }
}
