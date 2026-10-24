<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** Findings from the October 2026 security review: sessions, rate limits, and contact messages. */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Lingayen-Coast-2026';

    private function openSession(User $user, string $id): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }

    public function test_changing_your_password_signs_out_your_other_sessions(): void
    {
        $user = User::factory()->create();
        $this->openSession($user, 'phone-left-signed-in');
        $before = $user->remember_token;

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password', 'password' => self::STRONG, 'password_confirmation' => self::STRONG,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'phone-left-signed-in']);
        $this->assertNotSame($before, $user->refresh()->remember_token, 'Old remember-me cookies stop working.');
    }

    public function test_a_password_reset_ends_every_session(): void
    {
        $user = User::factory()->create();
        $this->openSession($user, 'attacker-session');

        $this->post(route('password.store'), [
            'token' => Password::createToken($user), 'email' => $user->email,
            'password' => self::STRONG, 'password_confirmation' => self::STRONG,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG, $user->refresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'attacker-session']);
    }

    public function test_one_address_trying_many_accounts_is_slowed_down(): void
    {
        foreach (range(1, 20) as $i) {
            $this->post(route('login'), ['email' => "person{$i}@example.ph", 'password' => 'Guess-2026!']);
        }

        $this->post(route('login'), ['email' => 'someone-new@example.ph', 'password' => 'Guess-2026!'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many login attempts', session('errors')->first('email'));
    }

    public function test_public_forms_that_send_email_are_rate_limited(): void
    {
        foreach (range(1, 6) as $i) {
            $this->post(route('password.email'), ['email' => "nobody{$i}@example.ph"]);
        }
        $this->post(route('password.email'), ['email' => 'nobody7@example.ph'])->assertStatus(429);

        foreach (range(1, 6) as $i) {
            $this->post(route('register'), []);
        }
        $this->post(route('register'), [])->assertStatus(429);
    }

    public function test_re_entering_a_password_is_rate_limited(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (range(1, 6) as $i) {
            $this->post(route('password.confirm'), ['password' => "wrong-{$i}"]);
        }
        $this->post(route('password.confirm'), ['password' => 'wrong-7'])->assertStatus(429);
    }

    public function test_contact_messages_are_kept_even_when_the_live_log_level_is_warning(): void
    {
        config(['logging.channels.single.level' => 'warning']);
        Log::shouldReceive('channel')->once()->with('enquiries')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Contact enquiry', \Mockery::on(fn ($data) => $data['email'] === 'juan@example.ph'));

        $this->post(route('contact.send'), ['name' => 'Juan', 'email' => 'juan@example.ph', 'message' => 'How do we renew our accreditation?'])
            ->assertSessionHas('status');
        $this->assertSame('info', config('logging.channels.enquiries.level'));
    }
}
