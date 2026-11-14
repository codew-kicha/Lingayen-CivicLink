<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Models\User;
use App\Services\ApplicationSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Who may reach what, and that what people type stays text, for the features added after the
 * pre-final defense: the application steps, member lists, notices, dashboard details and checks.
 */
class NewFeatureSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function rep(array $user = []): User
    {
        $rep = User::factory()->create($user);
        Organization::factory()->for($rep)->create();

        return $rep;
    }

    public function test_the_application_steps_are_for_signed_in_confirmed_representatives_only(): void
    {
        $this->get(route('cso.application'))->assertRedirect(route('login'));
        $this->post(route('cso.members.import'))->assertRedirect(route('login'));

        $unconfirmed = $this->rep(['email_verified_at' => null]);
        $this->actingAs($unconfirmed)->get(route('cso.application'))->assertRedirect(route('verification.notice'));

        $admin = User::factory()->admin()->create();
        foreach ([
            ['get', route('cso.application')],
            ['get', route('cso.application.step', 'members')],
            ['post', route('cso.application.members')],
            ['post', route('cso.application.submit')],
            ['get', route('cso.members.template')],
            ['post', route('cso.members.import')],
        ] as [$method, $url]) {
            $this->actingAs($admin)->{$method}($url)->assertForbidden();
        }
    }

    public function test_one_organization_cannot_touch_anothers_application(): void
    {
        Storage::fake('local');
        $owner = $this->rep();
        $this->startApplication($owner);
        $this->uploadRequirements($owner, $this->requirementFiles(['fee_receipt']), ['action' => 'later']);
        $receipt = $owner->organization->documents()->firstOrFail();
        $ownerDraft = ApplicationSteps::draft($owner->organization);

        $other = $this->rep();
        $this->startApplication($other);

        // Removing, downloading or viewing the owner's draft and files is refused.
        $this->actingAs($other)->delete(route('cso.application.documents.destroy', $receipt))->assertNotFound();
        $this->actingAs($other)->get(route('documents.download', $receipt))->assertForbidden();
        $this->actingAs($other)->get(route('cso.applications.show', $ownerDraft))->assertForbidden();
        $this->assertModelExists($receipt);

        // Steps only ever act on the signed-in organization's own draft.
        $this->actingAs($other)->post(route('cso.application.submit'));
        $this->assertSame('draft', $ownerDraft->fresh()->status);
    }

    public function test_a_draft_can_never_be_reviewed_or_approved(): void
    {
        $rep = $this->rep();
        $draft = $this->startApplication($rep);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.applications.show', $draft))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.applications.stage', $draft), ['sb_stage' => 'first_reading'])->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.applications.approve', $draft))->assertStatus(422);
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame(0, $rep->organization->accreditations()->count());
    }

    public function test_submitting_twice_files_one_application(): void
    {
        Storage::fake('local');
        $rep = $this->rep();
        $this->applyOnline($rep);
        $this->actingAs($rep)->post(route('cso.application.submit'))->assertRedirect();

        $this->assertSame(1, $rep->organization->applications()->count());
        $this->assertSame(2, $rep->organization->documents()->where('ocr_status', 'generated')->count());
    }

    public function test_what_people_type_is_shown_as_text_everywhere(): void
    {
        Storage::fake('local');
        $script = '<script>alert(1)</script>';
        $rep = $this->rep();
        $admin = User::factory()->admin()->create();

        // A name, a member and the form's answers.
        $this->actingAs($rep)->patch(route('profile.update'), ['last_name' => $script, 'first_name' => 'Ana', 'email' => $rep->email, 'phone' => '0917 123 4567'])
            ->assertSessionHasNoErrors();
        $members = $this->memberRows();
        $members[1]['first_name'] = $script;
        $this->actingAs($rep)->get(route('cso.application'));
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $members]);
        $this->actingAs($rep)->get(route('cso.dashboard'))->assertDontSee($script, false)->assertSee(e($script), false);
        $this->actingAs($rep)->get(route('cso.application.step', 'form'))->assertDontSee($script, false);

        // A notice body on both sides.
        $this->actingAs($admin)->post(route('admin.notices.store'), ['audience' => 'all', 'title' => $script, 'body' => $script]);
        $this->actingAs($admin)->get(route('admin.notices.index'))->assertDontSee($script, false);
        $this->actingAs($rep)->get(route('cso.notifications.index'))->assertDontSee($script, false)->assertSee(e($script), false);
    }

    public function test_office_only_pages_and_actions_refuse_representatives(): void
    {
        $rep = $this->rep();
        foreach ([
            ['get', route('admin.notices.index')],
            ['get', route('admin.notices.count', ['audience' => 'all'])],
            ['post', route('admin.notices.store')],
            ['post', route('admin.activities.verify-consistent')],
            ['post', route('admin.activities.read-sheets')],
            ['get', route('admin.accreditation.form', 'new')],
        ] as [$method, $url]) {
            $this->actingAs($rep)->{$method}($url)->assertForbidden();
        }
        $this->assertSame(0, OfficeNotice::count());
    }

    public function test_bulk_verify_never_touches_flagged_or_decided_activities(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->accredited()->create();
        $flagged = Activity::factory()->for($organization)->create(['status' => 'pending', 'check_status' => 'flagged']);
        $rejected = Activity::factory()->for($organization)->create(['status' => 'rejected', 'check_status' => 'consistent']);
        $consistent = Activity::factory()->for($organization)->create(['status' => 'pending', 'check_status' => 'consistent']);

        $this->actingAs($admin)->post(route('admin.activities.verify-consistent'));

        $this->assertSame('verified', $consistent->fresh()->status);
        $this->assertSame('pending', $flagged->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_member_import_accepts_only_spreadsheets(): void
    {
        $rep = $this->rep();

        $this->actingAs($rep)->postJson(route('cso.members.import'), ['file' => UploadedFile::fake()->create('evil.php', 5, 'application/x-php')])
            ->assertStatus(422)->assertJsonValidationErrors('file');
        $this->actingAs($rep)->postJson(route('cso.members.import'), ['file' => UploadedFile::fake()->create('big.csv', 3000, 'text/csv')])
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_uploads_in_the_steps_are_rate_limited(): void
    {
        Storage::fake('local');
        $rep = $this->rep();
        $this->startApplication($rep);

        foreach (range(1, 30) as $_) {
            $this->uploadRequirements($rep, [], ['action' => 'later']);
        }
        $this->uploadRequirements($rep, [], ['action' => 'later'])->assertStatus(429);
    }
}
