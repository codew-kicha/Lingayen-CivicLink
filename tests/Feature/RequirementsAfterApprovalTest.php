<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pre-final defense panel: the application form is submitted on its own; the official receipt and
 * the other requirements come only after the Desk Office approves it. Flow: submitted ->
 * requirements_pending -> requirements_submitted (Validation) -> under_review (Committee hearing) -> approved.
 */
class RequirementsAfterApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function rep(): User
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create(['name' => 'Libsong Youth Circle']);

        return $rep;
    }

    public function test_requirements_are_refused_by_the_server_until_the_form_is_approved(): void
    {
        $rep = $this->rep();
        $receipt = $this->requirementFiles(['fee_receipt']);

        // While a draft, and after submitting but before approval: refused, nothing stored.
        $draft = $this->startApplication($rep);
        $this->uploadRequirements($rep, $receipt)->assertForbidden();
        $this->actingAs($rep)->post(route('cso.application.submit'));
        $this->assertSame('submitted', $draft->fresh()->status);
        $this->uploadRequirements($rep, $receipt)->assertForbidden();
        $this->uploadRequirements($rep, $receipt, ['action' => 'later'])->assertForbidden();
        $this->assertSame(0, Document::where('document_type', 'fee_receipt')->count());

        // The page itself is locked and says why.
        $this->actingAs($rep)->get(route('cso.application.requirements.show'))->assertRedirect(route('cso.application'));
        $this->actingAs($rep)->get(route('cso.applications.show', $draft))->assertOk()
            ->assertSee('open once it is approved')->assertDontSee('Upload requirements');
    }

    public function test_approving_the_form_unlocks_the_requirements_and_notifies_the_cso(): void
    {
        $rep = $this->rep();
        $admin = User::factory()->admin()->create();
        $application = $this->approveForm($this->submitForm($rep), $admin);

        $this->assertSame('requirements_pending', $application->status);
        $this->assertSame($admin->id, $application->form_approved_by);
        $this->assertNotNull($application->form_approved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.form_approved']);

        // Told in the app and by email, with what to upload and a link to the page.
        Notification::assertSentTo($rep, ApplicationStatusChanged::class, function ($notification, $channels) use ($rep) {
            $data = $notification->toDatabase($rep);

            return $channels === ['database', 'mail']
                && $data['headline'] === 'Application form approved: upload your requirements'
                && str_contains($data['body'], 'Official Receipt of the Accreditation Fee')
                && $data['url'] === route('cso.application.requirements.show');
        });

        // The dashboard and "Apply" both lead to the now-open page.
        $dashboard = $this->actingAs($rep)->get(route('cso.dashboard'))->assertSee('Your application form was approved')
            ->assertSee(route('cso.application.requirements.show'), false);
        $this->assertSame(1, substr_count($dashboard->getContent(), 'Upload requirements'));   // once, in its card
        $this->actingAs($rep)->get(route('cso.application'))->assertRedirect(route('cso.application.requirements.show'));
        $this->actingAs($rep)->get(route('cso.application.requirements.show'))->assertOk()
            ->assertSee('Your application form was approved')->assertSee('Submit requirements');

        // Submitting them sends the application back to the office.
        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt', 'constitution_bylaws']))->assertSessionHasNoErrors();
        $this->assertSame('requirements_submitted', $application->fresh()->status);
        $this->assertNotNull($application->fresh()->requirements_submitted_at);
        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt']))->assertForbidden();   // closed again
    }

    public function test_the_committee_hearing_waits_for_the_requirements(): void
    {
        $rep = $this->rep();
        $admin = User::factory()->admin()->create();
        $application = $this->submitForm($rep);

        $this->recordHearing($application, admin: $admin)->assertStatus(422);
        $this->forwardToCommittee($application, $admin)->assertStatus(422);
        $this->approveForm($application, $admin);
        $this->recordHearing($application, admin: $admin)->assertStatus(422);
        $this->forwardToCommittee($application, $admin)->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertStatus(422);
        $this->assertNull($application->fresh()->hearing_outcome);

        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt', 'constitution_bylaws']));
        $this->forwardToCommittee($application, $admin)->assertSessionHasNoErrors();
        $this->recordHearing($application, admin: $admin)->assertSessionHasNoErrors();
        $this->assertSame(['under_review', 'favorable'], [$application->fresh()->status, $application->fresh()->hearing_outcome]);
    }

    public function test_the_office_can_send_the_requirements_back_with_a_note(): void
    {
        $rep = $this->rep();
        $admin = User::factory()->admin()->create();
        $application = $this->applyOnline($rep);

        $this->actingAs($admin)->post(route('admin.applications.return-requirements', $application), ['requirements_note' => ''])
            ->assertSessionHasErrors('requirements_note');
        $this->actingAs($admin)->post(route('admin.applications.return-requirements', $application), ['requirements_note' => 'The receipt is unreadable.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('requirements_pending', $application->fresh()->status);
        Notification::assertSentTo($rep, ApplicationStatusChanged::class, fn ($n) => $n->toDatabase($rep)['headline'] === 'Requirements returned for correction'
            && str_contains($n->toDatabase($rep)['body'], 'The receipt is unreadable.'));
        $this->actingAs($rep)->get(route('cso.application.requirements.show'))->assertSee('The Desk Office returned your requirements')
            ->assertSee('The receipt is unreadable.');

        // Resubmitting clears the note.
        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt']))->assertSessionHasNoErrors();
        $this->assertSame(['requirements_submitted', null], [$application->fresh()->status, $application->fresh()->requirements_note]);
    }

    public function test_a_paper_application_with_everything_attached_goes_straight_to_requirement_validation(): void
    {
        $organization = Organization::factory()->create();
        $application = ApplicationModel::factory()->for($organization)->create(['status' => 'submitted', 'type' => 'renewal', 'submission_channel' => 'assisted']);
        $organization->documents()->create([
            'application_id' => $application->id, 'document_type' => 'fee_receipt', 'file_path' => 'documents/x.pdf',
            'original_filename' => 'receipt.pdf', 'mime_type' => 'application/pdf',
        ]);

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.applications.approve-form', $application));

        $this->assertSame('requirements_submitted', $application->fresh()->status);
    }

    public function test_only_the_office_approves_forms_and_only_submitted_ones(): void
    {
        $rep = $this->rep();
        $application = $this->submitForm($rep);

        $this->actingAs($rep)->post(route('admin.applications.approve-form', $application))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.applications.return-requirements', $application), ['requirements_note' => 'x'])->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->approveForm($application, $admin);
        $this->actingAs($admin)->post(route('admin.applications.approve-form', $application))->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.applications.return-requirements', $application), ['requirements_note' => 'x'])->assertStatus(422);
    }

    public function test_waiting_for_the_cso_is_not_counted_as_waiting_for_the_office(): void
    {
        $admin = User::factory()->admin()->create();
        $this->approveForm($this->submitForm($this->rep()), $admin);

        $this->actingAs($admin)->get(route('admin.applications.index', ['status' => 'review']))->assertOk()->assertDontSee('Libsong Youth Circle');
        $this->actingAs($admin)->get(route('admin.applications.index', ['status' => 'requirements']))->assertOk()
            ->assertSee('Libsong Youth Circle')->assertSee('Requirements pending');
        $this->assertSame(0, ApplicationModel::whereIn('status', ApplicationModel::WITH_OFFICE)->count());
    }
}
