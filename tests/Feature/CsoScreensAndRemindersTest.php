<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccreditationExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The representative's Apply and Log dialogs, the settings-style organization profile, and the
 * office's manual "Send reminder" email for an expiring accreditation.
 */
class CsoScreensAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function rep(): User
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        return $rep;
    }

    private function accredit(Organization $organization, int $daysLeft = 45): Accreditation
    {
        $application = ApplicationModel::factory()->for($organization)->approved()->create();

        return $organization->accreditations()->create([
            'application_id' => $application->id, 'verification_code' => 'RMND-'.$organization->id, 'status' => 'active',
            'active_org_marker' => $organization->id, 'issued_at' => now()->subYears(3), 'expires_at' => now()->addDays($daysLeft),
        ]);
    }

    // Dialogs

    public function test_a_failed_application_reopens_the_apply_dialog(): void
    {
        $rep = $this->rep();
        $page = route('cso.applications.index');

        $this->actingAs($rep)->from($page)
            ->post(route('cso.applications.store'), ['type' => 'new', '_dialog' => 'apply'])
            ->assertRedirect($page)->assertSessionHasErrors('documents.accreditation_form');

        $this->actingAs($rep)->get($page)->assertSee("dialog('apply', true)", false)->assertSee('Attach the files again');
    }

    public function test_activities_log_in_a_dialog_and_list_one_row_each(): void
    {
        $rep = $this->rep();
        $this->accredit($rep->organization);
        $page = route('cso.activities.index');
        Activity::factory()->for($rep->organization)->create(['title' => 'Coastal clean-up']);

        $this->actingAs($rep)->get($page)->assertOk()
            ->assertSee("dialog('log-activity', false)", false)
            ->assertSee('Coastal clean-up')
            ->assertDontSee('value="Coastal clean-up"', false); // the dialog starts empty, not with a listed activity
        $this->actingAs($rep)->get(route('cso.activities.index', ['log' => 1]))->assertSee("dialog('log-activity', true)", false);

        $this->actingAs($rep)->from($page)
            ->post(route('cso.activities.store'), ['title' => 'Tree planting', '_dialog' => 'log-activity'])
            ->assertRedirect($page)->assertSessionHasErrors('description');
        $this->actingAs($rep)->get($page)->assertSee("dialog('log-activity', true)", false)->assertSee('value="Tree planting"', false);
    }

    // Profile

    public function test_the_profile_saves_member_rows_and_skips_blank_ones(): void
    {
        $rep = $this->rep();
        $organization = $rep->organization;

        $this->actingAs($rep)->get(route('cso.profile.edit'))->assertOk()
            ->assertSee('Public listing')->assertSee('Officers and members')->assertSee('Preview in the directory');

        $this->actingAs($rep)->patch(route('cso.profile.update'), [
            'name' => $organization->name,
            'sector' => $organization->sector,
            'barangay' => $organization->barangay,
            'members' => [
                0 => ['name' => 'Ana Reyes', 'position' => 'President', 'contact_number' => '', 'email' => ''],
                3 => ['name' => '', 'position' => '', 'contact_number' => '', 'email' => ''],
                7 => ['name' => 'Ben Cruz', 'position' => 'Treasurer', 'contact_number' => '0917 000 0000', 'email' => 'ben@example.ph'],
            ],
        ])->assertRedirect(route('cso.profile.edit'))->assertSessionHasNoErrors();

        $this->assertSame(['Ana Reyes', 'Ben Cruz'], $organization->members()->orderBy('name')->pluck('name')->all());
    }

    public function test_the_logo_can_be_removed(): void
    {
        Storage::fake('public');
        $rep = $this->rep();
        $organization = $rep->organization;
        $fields = ['name' => $organization->name, 'sector' => $organization->sector, 'barangay' => $organization->barangay];

        $this->actingAs($rep)->patch(route('cso.profile.update'), $fields + ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)]);
        $path = $organization->fresh()->logo_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($rep)->patch(route('cso.profile.update'), $fields + ['remove_logo' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($organization->fresh()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    // Expiry reminders

    public function test_the_office_can_email_an_expiry_reminder_once_a_day(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $rep = $this->rep();
        $this->accredit($rep->organization);
        $page = route('admin.organizations.edit', $rep->organization);

        $this->actingAs($admin)->get($page)->assertSee('Send expiry reminder');

        $this->actingAs($admin)->from($page)->post(route('admin.organizations.remind', $rep->organization))
            ->assertRedirect($page)->assertSessionHas('status');
        Notification::assertSentTo($rep, AccreditationExpiring::class, fn ($n, $channels) => $channels === ['database', 'mail']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'accreditation.reminder_sent', 'subject_id' => $rep->organization->id]);

        $this->actingAs($admin)->from($page)->post(route('admin.organizations.remind', $rep->organization))->assertSessionHas('error');
        Notification::assertSentToTimes($rep, AccreditationExpiring::class, 1);
        $this->actingAs($admin)->get($page)->assertSee('Last reminded');
    }

    public function test_a_reminder_needs_an_accreditation_and_an_account_and_an_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $notAccredited = $this->rep()->organization;
        $noLogin = Organization::factory()->create(['user_id' => null]);
        $this->accredit($noLogin);

        $this->actingAs($admin)->post(route('admin.organizations.remind', $notAccredited))->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.organizations.remind', $noLogin))->assertSessionHas('error');

        $rep = $this->rep();
        $this->actingAs($rep)->post(route('admin.organizations.remind', $notAccredited))->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_a_mail_outage_never_blocks_an_approval(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $admin = User::factory()->admin()->create();
        $rep = $this->rep();
        $application = ApplicationModel::factory()->for($rep->organization)->create(['status' => 'submitted']);

        $this->actingAs($admin)->post(route('admin.applications.approve', $application))
            ->assertRedirect(route('admin.applications.show', $application))
            ->assertSessionHas('status')->assertSessionHas('error', \App\Support\Mailer::FAILED);

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame(1, $rep->organization->accreditations()->where('status', 'active')->count());
    }

    public function test_the_expiring_filter_lists_only_accreditations_ending_within_90_days(): void
    {
        $admin = User::factory()->admin()->create();
        $soon = Organization::factory()->create(['name' => 'Soon Weavers']);
        $later = Organization::factory()->create(['name' => 'Later Farmers']);
        $this->accredit($soon, 30);
        $this->accredit($later, 400);

        $this->actingAs($admin)->get(route('admin.organizations.index', ['status' => 'expiring']))->assertOk()
            ->assertSee('Soon Weavers')->assertDontSee('Later Farmers');
    }
}
