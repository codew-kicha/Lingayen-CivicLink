<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Lingayen-Coast-2026';

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function confirmed(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    public function test_admin_registers_an_organization_and_invites_its_representative(): void
    {
        Notification::fake();

        $this->actingAs($this->admin())->post(route('admin.organizations.store'), [
            'name' => 'Baay Pedicab Drivers Association',
            'sector' => 'Pedicab Drivers',
            'barangay' => array_values(config('barangays'))[1],
            'rep_name' => 'Jose Ramos',
            'rep_email' => 'jose@example.ph',
            'rep_phone' => '09181234567',
        ])->assertRedirect();

        $rep = User::where('email', 'jose@example.ph')->firstOrFail();
        $this->assertSame('cso_rep', $rep->role);
        $this->assertNull($rep->email_verified_at);
        $this->assertSame('Baay Pedicab Drivers Association', $rep->organization->name);
        Notification::assertSentTo($rep, AccountInvitation::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.created']);
    }

    public function test_admin_can_register_an_organization_without_a_login(): void
    {
        $this->actingAs($this->admin())->post(route('admin.organizations.store'), [
            'name' => 'Tonton Senior Circle',
            'sector' => 'Senior Citizen',
            'barangay' => array_values(config('barangays'))[2],
        ])->assertRedirect();

        $this->assertNull(Organization::where('name', 'Tonton Senior Circle')->firstOrFail()->user_id);
    }

    public function test_an_invitation_sets_the_password_once_and_cannot_be_replayed(): void
    {
        $rep = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('invitation.store', now()->addHours(72), ['user' => $rep->id]);

        $this->post($url, ['password' => self::STRONG, 'password_confirmation' => self::STRONG])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($rep);
        $this->assertNotNull($rep->refresh()->email_verified_at);

        auth()->logout();
        $this->post($url, ['password' => 'Another-Pass-2026', 'password_confirmation' => 'Another-Pass-2026'])
            ->assertStatus(410);
    }

    public function test_tampered_or_expired_invitation_links_are_refused(): void
    {
        $rep = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('invitation.show', now()->addHours(72), ['user' => $rep->id]);
        $this->get(str_replace("/invitation/{$rep->id}", "/invitation/{$other->id}", $url))->assertForbidden();

        $expired = URL::temporarySignedRoute('invitation.show', now()->subMinute(), ['user' => $rep->id]);
        $this->get($expired)->assertForbidden();
    }

    public function test_deactivation_requires_password_confirmation_and_a_reason(): void
    {
        $admin = $this->admin();
        $rep = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.accounts.deactivate', $rep), ['reason' => 'Organization dissolved in 2026.'])
            ->assertRedirect(route('password.confirm'));
        $this->assertTrue($rep->refresh()->is_active);

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.accounts.deactivate', $rep), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.accounts.deactivate', $rep), ['reason' => 'Organization dissolved in 2026.'])
            ->assertRedirect();
        $this->assertFalse($rep->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.deactivated', 'subject_id' => $rep->id]);
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->withSession($this->confirmed())
            ->patch(route('admin.accounts.deactivate', $admin), ['reason' => 'Trying to lock myself out.'])
            ->assertForbidden();
    }

    public function test_cso_representatives_cannot_reach_account_management(): void
    {
        $rep = User::factory()->create();

        $this->actingAs($rep)->get(route('admin.accounts.index'))->assertForbidden();
        $this->actingAs($rep)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.organizations.store'), ['name' => 'X', 'sector' => 'OFW', 'barangay' => array_values(config('barangays'))[0]])
            ->assertForbidden();
    }

    public function test_creating_an_admin_requires_password_confirmation(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.accounts.store'), ['name' => 'New Admin', 'email' => 'staff@lingayen.gov.ph'])
            ->assertRedirect(route('password.confirm'));
        $this->assertDatabaseMissing('users', ['email' => 'staff@lingayen.gov.ph']);

        $this->actingAs($admin)->withSession($this->confirmed())
            ->post(route('admin.accounts.store'), ['name' => 'New Admin', 'email' => 'staff@lingayen.gov.ph'])
            ->assertRedirect(route('admin.accounts.index'));
        $this->assertSame('admin', User::where('email', 'staff@lingayen.gov.ph')->firstOrFail()->role);
    }

    public function test_revoking_an_accreditation_frees_the_organization_for_a_new_one(): void
    {
        $organization = Organization::factory()->create();
        $accreditation = Accreditation::create([
            'organization_id' => $organization->id,
            'application_id' => ApplicationModel::factory()->for($organization)->create()->id,
            'verification_code' => 'TEST-REVK-0001',
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(3)->toDateString(),
        ]);

        $this->actingAs($this->admin())->withSession($this->confirmed())
            ->post(route('admin.organizations.revoke', $organization), ['reason' => 'No activities logged for two years.'])
            ->assertRedirect();

        $accreditation->refresh();
        $this->assertSame('revoked', $accreditation->status);
        $this->assertNull($accreditation->active_org_marker);
    }

    public function test_an_account_can_be_attached_to_an_organization_without_one_but_not_twice(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $organization = Organization::factory()->create(['user_id' => null]);

        $this->actingAs($admin)->post(route('admin.organizations.account', $organization), ['name' => 'Ana Cruz', 'email' => 'ana@example.ph'])
            ->assertRedirect();
        $this->assertSame('ana@example.ph', $organization->refresh()->user->email);

        $this->actingAs($admin)->post(route('admin.organizations.account', $organization), ['name' => 'Second Person', 'email' => 'second@example.ph'])
            ->assertStatus(409);
    }
}
