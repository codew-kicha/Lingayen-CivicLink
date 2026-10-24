<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function details(array $overrides = []): array
    {
        return $overrides + [
            'organization_name' => 'Libsong Senior Citizens Association',
            'barangay' => array_values(config('barangays'))[0],
            'sector' => config('sectors')[0],
            'name' => 'Ana Reyes',
            'email' => 'ana@example.ph',
            'phone' => '0917 123 4567',
            'password' => 'Lingayen-Coast-2026',
        ];
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('Step 1 of 2')
            ->assertSee('Organization name')
            ->assertDontSee('name="password_confirmation"', false)
            ->assertSee('Already registered?');
    }

    public function test_an_organization_registers_with_a_single_password_field(): void
    {
        $this->post(route('register'), $this->details())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ana@example.ph')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('cso_rep', $user->role);
        $this->assertSame('Libsong Senior Citizens Association', Organization::where('user_id', $user->id)->value('name'));
    }

    public function test_the_form_reopens_on_the_step_that_holds_the_error(): void
    {
        // A problem with the organization (step 1) reopens step 1.
        $this->from(route('register'))->post(route('register'), $this->details(['organization_name' => '']))
            ->assertSessionHasErrors('organization_name');
        $this->get(route('register'))->assertSee('step: 1,', false);

        // Organization fine, representative wrong (step 2): reopens step 2.
        $this->from(route('register'))->post(route('register'), $this->details(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->get(route('register'))->assertSee('step: 2,', false);
    }
}
