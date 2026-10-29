<?php

namespace Tests\Feature;

use App\Exports\ReportSheet;
use App\Models\Accreditation;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;
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
            'create_login' => '1',
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

    public function test_the_representative_login_is_created_only_when_ticked_and_then_needs_a_name_and_email(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $organization = ['sector' => 'Senior Citizen', 'barangay' => array_values(config('barangays'))[2]];

        // Unticked: typed representative details are ignored, not half-saved.
        $this->actingAs($admin)->post(route('admin.organizations.store'), $organization + [
            'name' => 'Libsong Senior Circle', 'rep_name' => 'Ana Cruz', 'rep_email' => 'ana@example.ph',
        ])->assertRedirect();
        $this->assertNull(Organization::where('name', 'Libsong Senior Circle')->firstOrFail()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.ph']);

        // Ticked: both fields are required, and the page the dialog was on comes back with the errors.
        $this->actingAs($admin)->from(route('admin.applications.index'))->post(route('admin.organizations.store'), $organization + [
            'name' => 'Poblacion Senior Circle', 'create_login' => '1', '_dialog' => 'register-organization',
        ])->assertRedirect(route('admin.applications.index'))->assertSessionHasErrors(['rep_name', 'rep_email']);
        $this->assertDatabaseMissing('organizations', ['name' => 'Poblacion Senior Circle']);
    }

    public function test_the_old_register_page_opens_the_dialog(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.organizations.create'))
            ->assertRedirect(route('admin.organizations.index', ['register' => 1]));
        $this->actingAs($admin)->get(route('admin.organizations.index', ['register' => 1]))
            ->assertOk()->assertSee('Register an organization')->assertSee('Create a login for the representative');
    }

    public function test_an_admin_can_correct_a_representatives_login_and_set_a_new_password(): void
    {
        $admin = $this->admin();
        $rep = User::factory()->create(['email' => 'old@example.ph', 'email_verified_at' => null]);

        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), [
            'name' => 'Maria Santos', 'email' => 'maria@example.ph', 'phone' => '0917 123 4567',
            'reset_password' => '1', 'new_password' => self::STRONG, 'admin_password' => 'password',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $rep->refresh();
        $this->assertSame(['Maria Santos', 'maria@example.ph', '+639171234567'], [$rep->name, $rep->email, $rep->phone]);
        $this->assertTrue(Hash::check(self::STRONG, $rep->password));
        $this->assertNotNull($rep->email_verified_at, 'A password set by the office makes a pending invitation usable.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.password_reset', 'subject_id' => $rep->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.credentials_updated', 'subject_id' => $rep->id]);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'maria@example.ph', 'password' => self::STRONG]);
        $this->assertAuthenticatedAs($rep);
    }

    public function test_changing_a_login_needs_the_admins_own_password_and_a_strong_new_one(): void
    {
        $admin = $this->admin();
        $rep = User::factory()->create(['email' => 'rep@example.ph']);
        $before = $rep->password;

        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), [
            'name' => $rep->name, 'email' => 'rep@example.ph',
            'reset_password' => '1', 'new_password' => 'short', 'admin_password' => 'wrong-password',
        ])->assertSessionHasErrors(['new_password', 'admin_password']);
        $this->assertSame($before, $rep->refresh()->password);

        // Unticked, a typed password is ignored and only the details change.
        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), [
            'name' => 'Renamed Rep', 'email' => 'rep@example.ph', 'new_password' => self::STRONG, 'admin_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Renamed Rep', $before], [$rep->refresh()->name, $rep->password]);
    }

    public function test_typing_the_representatives_password_instead_of_your_own_is_explained(): void
    {
        $admin = User::factory()->admin()->create(['password' => ' Admin-Pass-2026 ']);
        $rep = User::factory()->create(['name' => 'Maria Santos', 'email' => 'rep2@example.ph', 'password' => self::STRONG]);
        $details = ['name' => 'Maria Santos', 'email' => 'rep2@example.ph', 'reset_password' => '1', 'new_password' => 'Brand-New-Pass-77'];

        // The representative's password (what a browser fills in on this form) gets a specific message.
        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), $details + ['admin_password' => self::STRONG])
            ->assertSessionHasErrors(['admin_password' => "That is Maria Santos's current password. Enter your own admin password, the one you signed in with."]);

        // The admin's own password works as typed, spaces included (passwords are never trimmed).
        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), $details + ['admin_password' => ' Admin-Pass-2026 '])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Brand-New-Pass-77', $rep->refresh()->password));
    }

    public function test_admin_accounts_and_cso_users_cannot_use_the_login_editor(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $rep = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.accounts.update', $otherAdmin), [
            'name' => 'X', 'email' => 'x@example.ph', 'admin_password' => 'password',
        ])->assertForbidden();
        $this->actingAs($rep)->patch(route('admin.accounts.update', User::factory()->create()), [
            'name' => 'X', 'email' => 'x@example.ph', 'admin_password' => 'password',
        ])->assertForbidden();
    }

    public function test_the_cso_list_downloads_as_excel_all_or_accredited_only(): void
    {
        Excel::fake();
        $admin = $this->admin();
        $accredited = Organization::factory()->create(['name' => 'Accredited Weavers']);
        $application = ApplicationModel::factory()->for($accredited)->approved()->create();
        $accredited->accreditations()->create([
            'application_id' => $application->id, 'verification_code' => 'XLSX-0001', 'status' => 'active',
            'active_org_marker' => $accredited->id, 'issued_at' => now(), 'expires_at' => now()->addYears(3),
        ]);
        Organization::factory()->create(['name' => 'Registered Farmers']);

        $this->actingAs($admin)->get(route('admin.organizations.export', ['scope' => 'accredited']))->assertOk();
        Excel::assertDownloaded('accredited-csos-'.now()->format('Y-m-d').'.xlsx', function (ReportSheet $sheet) {
            return array_column($sheet->array(), 0) === ['Accredited Weavers'] && $sheet->array()[0][5] === 'XLSX-0001';
        });

        $this->actingAs($admin)->get(route('admin.organizations.export'))->assertOk();
        Excel::assertDownloaded('registered-csos-'.now()->format('Y-m-d').'.xlsx', function (ReportSheet $sheet) {
            return array_column($sheet->array(), 0) === ['Accredited Weavers', 'Registered Farmers'];
        });
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.list_exported']);

        $this->actingAs(User::factory()->create())->get(route('admin.organizations.export'))->assertForbidden();
    }

    public function test_an_invitation_sets_the_password_once_and_cannot_be_replayed(): void
    {
        $rep = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('invitation.store', now()->addHours(72), ['user' => $rep->id, 'for' => AccountInvitation::fingerprint($rep)]);

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

        $url = URL::temporarySignedRoute('invitation.show', now()->addHours(72), ['user' => $rep->id, 'for' => AccountInvitation::fingerprint($rep)]);
        $this->get(str_replace("/invitation/{$rep->id}", "/invitation/{$other->id}", $url))->assertForbidden();

        $expired = URL::temporarySignedRoute('invitation.show', now()->subMinute(), ['user' => $rep->id]);
        $this->get($expired)->assertForbidden();
    }

    public function test_an_invitation_sent_to_a_mistyped_email_stops_working_once_the_email_is_corrected(): void
    {
        $admin = $this->admin();
        $rep = User::factory()->unverified()->create(['email' => 'ana@gmial.com']);
        $old = URL::temporarySignedRoute('invitation.store', now()->addHours(72), ['user' => $rep->id, 'for' => AccountInvitation::fingerprint($rep)]);

        $this->actingAs($admin)->patch(route('admin.accounts.update', $rep), [
            'name' => $rep->name, 'email' => 'ana@gmail.com', 'admin_password' => 'password',
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->post($old, ['password' => self::STRONG, 'password_confirmation' => self::STRONG])->assertStatus(410);
        $this->assertNull($rep->refresh()->email_verified_at);

        $fresh = URL::temporarySignedRoute('invitation.store', now()->addHours(72), ['user' => $rep->id, 'for' => AccountInvitation::fingerprint($rep)]);
        $this->post($fresh, ['password' => self::STRONG, 'password_confirmation' => self::STRONG])->assertRedirect(route('dashboard'));
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

    public function test_adding_an_admin_is_hidden_until_switched_on(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.accounts.index'))->assertOk()->assertDontSee('Add PESO admin');
        $this->actingAs($admin)->withSession($this->confirmed())->get(route('admin.accounts.create'))->assertNotFound();
        $this->actingAs($admin)->withSession($this->confirmed())
            ->post(route('admin.accounts.store'), ['name' => 'New Admin', 'email' => 'staff@lingayen.gov.ph'])
            ->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'staff@lingayen.gov.ph']);

        config(['app.admin_invites' => true]);
        $this->actingAs($admin)->get(route('admin.accounts.index'))->assertSee('Add PESO admin');
    }

    public function test_creating_an_admin_requires_password_confirmation(): void
    {
        Notification::fake();
        config(['app.admin_invites' => true]);
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
