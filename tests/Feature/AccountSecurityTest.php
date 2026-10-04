<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Lingayen-Coast-2026';

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'Wawa Fisherfolk Association',
            'sector' => 'Farmers and Fisherfolks',
            'barangay' => config('barangays')[0],
            'name' => 'Maria Santos',
            'email' => 'maria@example.ph',
            'phone' => '0917 123 4567',
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ], $overrides);
    }

    public function test_registration_creates_the_account_and_its_organization_together(): void
    {
        $this->post('/register', $this->registration())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'maria@example.ph')->firstOrFail();
        $this->assertSame('cso_rep', $user->role);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertSame('Wawa Fisherfolk Association', $user->organization->name);
        $this->assertNull($user->email_verified_at);
    }

    public function test_registration_cannot_create_an_admin(): void
    {
        $this->post('/register', $this->registration(['role' => 'admin']));

        $this->assertSame('cso_rep', User::where('email', 'maria@example.ph')->firstOrFail()->role);
    }

    /**
     * @dataProvider weakPasswords
     */
    public function test_weak_passwords_are_rejected(string $password): void
    {
        $this->post('/register', $this->registration(['password' => $password, 'password_confirmation' => $password]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'maria@example.ph']);
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!short'],
            'no uppercase' => ['lingayen-coast-2026'],
            'no number' => ['Lingayen-Coast-Town'],
            'no symbol' => ['LingayenCoast2026'],
        ];
    }

    public function test_invalid_phone_numbers_are_rejected(): void
    {
        $this->post('/register', $this->registration(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_an_unverified_account_cannot_reach_the_cso_area(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/cso/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_a_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => self::STRONG]);
        $user->forceFill(['is_active' => false])->save();

        $this->post('/login', ['email' => $user->email, 'password' => self::STRONG])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivation_ends_an_existing_session_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/cso/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get('/cso/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_recorded_and_failed_logins_are_audited(): void
    {
        $user = User::factory()->create(['password' => self::STRONG]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login.failed', 'actor_id' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => self::STRONG]);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertSame(1, AuditLog::where('action', 'login')->where('actor_id', $user->id)->count());
    }

    public function test_setting_a_password_from_the_emailed_link_verifies_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => self::STRONG,
                'password_confirmation' => self::STRONG,
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
