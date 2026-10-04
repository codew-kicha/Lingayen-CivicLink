<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_for_both_roles(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/profile')->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Test User', 'email' => 'test@example.com', 'phone' => '0917 765 4321'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+639177654321', $user->phone);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.profile_updated', 'subject_id' => $user->id]);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Test User', 'email' => $user->email, 'phone' => '09177654321'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_cso_representatives_must_keep_a_mobile_number(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch('/profile', ['name' => 'Test User', 'email' => 'test@example.com', 'phone' => ''])
            ->assertSessionHasErrors('phone');
    }

    // Accounts are deactivated by the office, never deleted, so the audit trail survives.
    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
