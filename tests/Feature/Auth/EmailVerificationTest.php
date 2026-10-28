<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    /** Sends a code to the user and returns it, as the email would show it. */
    private function sendCode(User $user): string
    {
        Notification::fake();
        $user->sendEmailVerificationNotification();
        $code = null;
        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_registering_emails_a_six_digit_code_and_only_its_hash_is_stored(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'organization_name' => 'Libsong Senior Citizens Association',
            'barangay' => array_values(config('barangays'))[0],
            'sector' => config('sectors')[0],
            'name' => 'Ana Reyes',
            'email' => 'ana@example.ph',
            'phone' => '0917 123 4567',
            'password' => 'Lingayen-Coast-2026', 'privacy_consent' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ana@example.ph')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $n) use ($user) {
            return preg_match('/^\d{6}$/', $n->code) && $user->email_code_hash !== $n->code
                && str_contains($n->toMail($user)->subject, $n->code);
        });
        $this->assertFalse($user->hasVerifiedEmail());
        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('6-digit code')->assertSee('ana@example.ph');
        $this->actingAs($user)->get(route('cso.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_the_right_code_confirms_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        Event::fake([Verified::class]);

        $this->actingAs($user)->post(route('verification.code'), ['code' => substr($code, 0, 3).' '.substr($code, 3)])
            ->assertRedirect(route('cso.dashboard', absolute: false).'?verified=1')->assertSessionHas('status');

        Event::assertDispatched(Verified::class);
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNull($user->email_code_hash);
    }

    public function test_wrong_codes_count_down_and_lock_after_five_tries(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong])
            ->assertSessionHasErrors(['code' => 'That code is not right. 4 tries left.']);
        foreach (range(2, 5) as $_) {
            $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong]);
        }

        // Even the right code is refused now, until a new one is sent.
        $this->actingAs($user)->post(route('verification.code'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Too many wrong codes. Send a new one.']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $user = $user->fresh();
        $fresh = $this->sendCode($user);
        $this->actingAs($user)->post(route('verification.code'), ['code' => $fresh])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_expired_or_replaced_code_is_refused(): void
    {
        $user = User::factory()->unverified()->create();
        $first = $this->sendCode($user);
        $second = $this->sendCode($user); // replaces the first

        if ($first !== $second) {
            $this->actingAs($user)->post(route('verification.code'), ['code' => $first])->assertSessionHasErrors('code');
        }

        $this->travel(User::EMAIL_CODE_MINUTES + 1)->minutes();
        $this->actingAs($user)->post(route('verification.code'), ['code' => $second])
            ->assertSessionHasErrors(['code' => 'This code has expired. Send a new one.']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_send_a_new_code_mails_a_fresh_one(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmailCode::class);
    }

    public function test_a_mail_outage_during_registration_leaves_the_person_on_the_code_page(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

        $this->post(route('register'), [
            'organization_name' => 'Balococ Volunteers Group',
            'barangay' => array_values(config('barangays'))[0],
            'sector' => config('sectors')[0],
            'name' => 'Maria Santos',
            'email' => 'maria@example.ph',
            'phone' => '0917 555 1234',
            'password' => 'Balococ-Group-2026!', 'privacy_consent' => '1',
        ])->assertRedirect(route('verification.notice'))->assertSessionHasErrors('code');

        $this->assertAuthenticatedAs(User::where('email', 'maria@example.ph')->firstOrFail());
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
