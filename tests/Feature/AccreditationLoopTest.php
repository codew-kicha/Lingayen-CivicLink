<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ActivityVerified;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The core loop from PRD §16: submit, review, approve, log activity, verify, score, dashboard.
 */
class AccreditationLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_accreditation_and_monitoring_loop(): void
    {
        Storage::fake('local');
        Notification::fake();

        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();
        $admin = User::factory()->admin()->create();

        // Apply online: members, the form, the uploads, then submit. The form and the officer list are
        // produced by the system, so every requirement is on file.
        $application = $this->applyOnline($rep, 'new', optional: true);
        $this->assertSame('submitted', $application->status);
        $this->assertCount(count(config('document_types')), $application->documents);
        $this->assertSame(2, $application->documents->where('ocr_status', 'generated')->count());
        Notification::assertNothingSentTo($rep);

        // Review: record each Sangguniang Bayan stage in turn. Skipping ahead is refused.
        $this->actingAs($admin)
            ->patch(route('admin.applications.stage', $application), ['sb_stage' => 'second_reading'])
            ->assertSessionHasErrors('sb_stage');
        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertSessionHas('error');
        $this->assertSame('submitted', $application->refresh()->status);

        foreach (['first_reading', 'second_reading', 'third_reading', 'endorsed'] as $stage) {
            $this->actingAs($admin)
                ->patch(route('admin.applications.stage', $application), ['sb_stage' => $stage])
                ->assertSessionHasNoErrors();
            $this->assertSame($stage, $application->refresh()->sb_stage);
        }
        $this->assertSame('under_review', $application->status);
        Notification::assertSentTo($rep, ApplicationStatusChanged::class);

        // Approve (now that the SB has endorsed it): issues the accreditation in the same transaction.
        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertSame('endorsed', $application->sb_stage);
        $this->assertSame($admin->id, $application->reviewed_by);

        $accreditation = Accreditation::firstOrFail();
        $this->assertSame('active', $accreditation->status);
        $this->assertSame($organization->id, $accreditation->active_org_marker);
        $this->assertNotEmpty($accreditation->verification_code);

        // The organization now appears in the public directory.
        $this->get(route('directory'))->assertOk()->assertSee($organization->name);

        // Log an activity: pending until verified, so it must not count yet.
        $this->actingAs($rep)
            ->post(route('cso.activities.store'), [
                'title' => 'Coastal clean-up drive',
                'description' => 'Volunteers cleared debris along the shoreline.',
                'activity_date' => now()->subDay()->toDateString(),
                'participants_estimate' => 120,
                'activity_source' => 'independent',
            ])
            ->assertRedirect();

        $activity = Activity::firstOrFail();
        $this->assertSame('pending', $activity->status);
        $this->get(route('directory.show', $organization))->assertOk()->assertDontSee('Coastal clean-up drive');

        // Verify: the activity counts and a score is computed.
        $this->actingAs($admin)
            ->post(route('admin.activities.verify', $activity), ['activity_source' => 'independent'])
            ->assertRedirect();

        $activity->refresh();
        $this->assertSame('verified', $activity->status);
        $this->assertSame($admin->id, $activity->verified_by);
        Notification::assertSentTo($rep, ActivityVerified::class);

        $score = $organization->performanceScores()->firstOrFail();
        $this->assertGreaterThan(0, $score->total_score);
        $this->assertEqualsWithDelta(
            ($score->activity_frequency + $score->community_reach
                + $score->compliance_timeliness + $score->document_currency) / 4,
            $score->total_score,
            0.0001,
        );

        // Dashboard shows the score, the activity list shows the entry, and the public
        // profile now carries the verified work.
        $this->actingAs($rep)->get(route('cso.dashboard'))
            ->assertOk()
            ->assertSee(number_format($score->total_score * 100, 0).'%')
            ->assertSee('Recent activities')
            ->assertSee('Coastal clean-up drive');
        $this->actingAs($rep)->get(route('cso.activities.index'))
            ->assertOk()
            ->assertSee('Coastal clean-up drive');
        $this->get(route('directory.show', $organization))->assertOk()->assertSee('Coastal clean-up drive');
    }

    public function test_approving_twice_does_not_leave_two_active_accreditations(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->admin()->create();

        foreach (range(1, 2) as $ignored) {
            $application = ApplicationModel::factory()->for($organization)->create(['status' => 'under_review', 'sb_stage' => 'endorsed']);
            $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertRedirect();
        }

        $this->assertSame(1, $organization->accreditations()->where('status', 'active')->count());
        $this->assertSame(2, $organization->accreditations()->count());
    }

    public function test_a_closed_application_cannot_be_approved_again(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->rejected()->create();

        $this->actingAs($admin)
            ->post(route('admin.applications.approve', $application))
            ->assertStatus(422);

        $this->assertDatabaseCount('accreditations', 0);
    }

    public function test_the_optional_dole_sec_certification_can_be_omitted_but_required_ones_cannot(): void
    {
        Storage::fake('local');

        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        // Without the optional DOLE/SEC certificate: still complete.
        $application = $this->applyOnline($rep);
        $this->assertCount(count(\App\Models\Document::requiredTypes()), $application->documents);

        // Without the receipt: the requirements step says so and the application stays a draft.
        $other = User::factory()->create();
        Organization::factory()->for($other)->create();
        $draft = $this->startApplication($other);
        $this->uploadRequirements($other, $this->requirementFiles(['constitution_bylaws']))
            ->assertRedirect(route('cso.application.step', 'requirements'))
            ->assertSessionHasErrors('documents.fee_receipt');
        $this->actingAs($other)->post(route('cso.application.submit'))->assertRedirect(route('cso.application.step', 'requirements'));
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_renewals_do_not_need_the_constitution_and_by_laws_but_new_applications_do(): void
    {
        Storage::fake('local');
        $renewing = User::factory()->create();
        Organization::factory()->for($renewing)->create();
        $this->startApplication($renewing, 'renewal');
        $this->uploadRequirements($renewing, $this->requirementFiles(['fee_receipt']))
            ->assertSessionHasNoErrors()->assertRedirect(route('cso.application.step', 'review'));
        $this->actingAs($renewing)->get(route('cso.application.step', 'requirements'))->assertDontSee('Constitution and By-Laws');

        $applying = User::factory()->create();
        Organization::factory()->for($applying)->create();
        $this->startApplication($applying, 'new');
        $this->uploadRequirements($applying, $this->requirementFiles(['fee_receipt']))
            ->assertSessionHasErrors('documents.constitution_bylaws');
    }

    public function test_unknown_document_keys_are_not_stored(): void
    {
        Storage::fake('local');

        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        $this->startApplication($rep);
        $documents = $this->requirementFiles(['fee_receipt', 'constitution_bylaws', 'made_up_type', 'accreditation_form']);
        $this->uploadRequirements($rep, $documents)->assertRedirect();

        // Only the uploads this step asks for are stored; the form is made online, never uploaded here.
        $this->assertDatabaseMissing('documents', ['document_type' => 'accreditation_form']);

        $this->assertDatabaseMissing('documents', ['document_type' => 'made_up_type']);
    }

    /**
     * Turns "documents[x]" keys into the nested array the request expects.
     */
    private function nest(array $flat): array
    {
        $nested = [];

        foreach ($flat as $key => $value) {
            preg_match('/^(\w+)\[(\w+)\]$/', $key, $matches);
            $nested[$matches[1]][$matches[2]] = $value;
        }

        return $nested;
    }
}
