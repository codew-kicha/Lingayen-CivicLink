<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\ApplicationSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Registration and the application as one guided path (pre-final defense panel): steps that save as
 * you go, the accreditation form filled in online, and the documents the system produces from it.
 */
class ApplicationStepsTest extends TestCase
{
    use RefreshDatabase;

    private function rep(): User
    {
        $rep = User::factory()->create(['name' => 'Ana Reyes']);
        Organization::factory()->for($rep)->create(['name' => 'Libsong Youth Circle', 'barangay' => 'Libsong East']);

        return $rep;
    }

    public function test_a_new_organization_goes_from_the_email_code_into_the_steps(): void
    {
        $rep = User::factory()->unverified()->create();
        Organization::factory()->for($rep)->create();
        $rep->forceFill(['email_code_hash' => Hash::make('123456'), 'email_code_expires_at' => now()->addMinutes(10)])->save();

        $this->actingAs($rep)->post(route('verification.code'), ['code' => '123456'])->assertRedirect(route('cso.application'));
        $this->actingAs($rep)->get(route('cso.application'))->assertRedirect(route('cso.application.step', 'members'));
    }

    public function test_steps_save_as_you_go_and_resume_on_the_first_unfinished_one(): void
    {
        $rep = $this->rep();
        $this->actingAs($rep)->get(route('cso.application'))->assertRedirect(route('cso.application.step', 'members'));

        // Later steps wait for earlier ones.
        $this->actingAs($rep)->get(route('cso.application.step', 'review'))->assertRedirect(route('cso.application.step', 'members'));

        // "Finish later" keeps an incomplete member list; continuing needs five.
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'later', 'members' => $this->memberRows(2)])
            ->assertRedirect(route('cso.application.step', 'members'));
        $this->assertCount(2, $rep->organization->members);
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $this->memberRows(2)])
            ->assertSessionHasErrors('members');

        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $this->memberRows()])
            ->assertRedirect(route('cso.application.step', 'form'));

        // The form starts from the organization and the account.
        $this->actingAs($rep)->get(route('cso.application.step', 'form'))->assertOk()
            ->assertSee('Step 4 of 7')->assertSee('value="Libsong Youth Circle"', false)
            ->assertSee('<option value="Libsong East" selected>', false);

        // Leaving and coming back lands on the form again.
        $this->actingAs($rep)->get(route('cso.application'))->assertRedirect(route('cso.application.step', 'form'));
        $this->actingAs($rep)->get(route('cso.dashboard'))->assertSee('Your application is in progress')->assertSee('Step 4 of 7');
    }

    public function test_the_place_of_operation_is_a_barangay_from_the_list_plus_the_specific_place(): void
    {
        $rep = $this->rep();
        $this->actingAs($rep)->get(route('cso.application'));
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $this->memberRows()]);
        $form = [
            'step' => 'form', 'type' => 'new', 'organization_name' => 'Libsong Youth Circle', 'representative_name' => 'Ana Reyes',
            'contact_number' => '0917 123 4567', 'certify' => '1', 'certified_name' => 'Ana Reyes',
        ];

        // Only Lingayen's barangays are accepted.
        $this->actingAs($rep)->post(route('cso.application.form'), $form + ['place_barangay' => 'Makati'])
            ->assertSessionHasErrors('place_barangay');

        // The specific place leads; the form shows both.
        $this->actingAs($rep)->post(route('cso.application.form'), $form + ['place_barangay' => 'Sabangan', 'place_detail' => ' Purok 3, beside the chapel '])
            ->assertSessionHasNoErrors();
        $data = ApplicationSteps::draft($rep->organization)->form_data;
        $this->assertSame(['Sabangan', 'Purok 3, beside the chapel, Barangay Sabangan, Lingayen, Pangasinan'], [$data['place_barangay'], $data['place_of_operation']]);

        // Without one, just the barangay.
        $this->actingAs($rep)->post(route('cso.application.form'), $form + ['place_barangay' => 'Sabangan']);
        $this->assertSame('Barangay Sabangan, Lingayen, Pangasinan', ApplicationSteps::draft($rep->organization)->form_data['place_of_operation']);
    }

    public function test_the_form_needs_a_certification(): void
    {
        $rep = $this->rep();
        $this->actingAs($rep)->get(route('cso.application'));
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $this->memberRows()]);

        $this->actingAs($rep)->post(route('cso.application.form'), [
            'step' => 'form', 'type' => 'new', 'organization_name' => 'Libsong Youth Circle', 'representative_name' => 'Ana Reyes',
            'contact_number' => '0917 123 4567', 'place_barangay' => 'Libsong East',
        ])->assertSessionHasErrors(['certify', 'certified_name']);
        $this->assertNull(ApplicationSteps::draft($rep->organization)->form_data['certified_at'] ?? null);
    }

    public function test_submitting_produces_the_form_and_the_officer_list_and_queues_the_application(): void
    {
        Storage::fake('local');
        $rep = $this->rep();
        $admin = User::factory()->admin()->create();

        $draft = $this->startApplication($rep);

        // A draft is the organization's own business until it is submitted.
        $this->actingAs($admin)->get(route('admin.applications.index'))->assertDontSee('Libsong Youth Circle');
        $this->actingAs($admin)->get(route('admin.applications.show', $draft))->assertNotFound();

        // The completed form can be previewed before submitting.
        $this->actingAs($rep)->get(route('cso.application.preview'))->assertOk()->assertHeader('content-type', 'application/pdf');

        // The form goes on its own; the other requirements are shown as coming after approval.
        $this->actingAs($rep)->get(route('cso.application.step', 'review'))->assertOk()
            ->assertSee('Made from this application when you submit')->assertSee('After your form is approved')
            ->assertSee('Official Receipt', false)->assertSee('Submit application');
        $this->actingAs($rep)->post(route('cso.application.submit'))->assertRedirect(route('cso.applications.show', $draft));

        $application = $draft->fresh('documents');
        $this->assertSame('submitted', $application->status);
        $this->assertNotNull($application->submitted_at);
        $generated = $application->documents->where('ocr_status', 'generated');
        $this->assertEqualsCanonicalizing(['accreditation_form', 'officers_members_list'], $generated->pluck('document_type')->all());
        $generated->each(fn ($document) => Storage::assertExists($document->file_path));
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.submitted', 'subject_id' => $rep->organization->id]);

        // The reviewer sees them as made online; the applicant can't start another until this one is decided.
        $this->actingAs($admin)->get(route('admin.applications.show', $application))->assertOk()->assertSee('Made online');
        $this->actingAs($rep)->get(route('cso.application'))->assertRedirect(route('cso.applications.show', $application));
    }

    public function test_a_saved_upload_can_be_removed_but_not_another_organizations(): void
    {
        Storage::fake('local');
        $rep = $this->rep();
        $this->approveForm($this->submitForm($rep));
        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt']), ['action' => 'later'])->assertSessionHasNoErrors();
        $receipt = $rep->organization->documents()->where('document_type', 'fee_receipt')->firstOrFail();

        $other = $this->rep();
        $this->approveForm($this->submitForm($other));
        $this->actingAs($other)->delete(route('cso.application.documents.destroy', $receipt))->assertNotFound();

        $this->actingAs($rep)->delete(route('cso.application.documents.destroy', $receipt))->assertRedirect(route('cso.application.requirements.show'));
        $this->assertModelMissing($receipt);
        Storage::assertMissing($receipt->file_path);
    }

    public function test_a_corrected_application_starts_from_the_rejected_ones_answers(): void
    {
        $rep = $this->rep();
        ApplicationModel::factory()->for($rep->organization)->rejected()->create([
            'form_data' => ['organization_name' => 'Libsong Youth Circle Inc.', 'contact_number' => '0918 000 1111'],
        ]);

        $this->actingAs($rep)->get(route('cso.application'));
        $draft = ApplicationSteps::draft($rep->organization);

        $this->assertSame('new', $draft->type);
        $this->assertSame('Libsong Youth Circle Inc.', $draft->form_data['organization_name']);
        $this->assertSame('0918 000 1111', $draft->form_data['contact_number']);
    }

    public function test_the_old_form_downloads_are_gone(): void
    {
        $rep = $this->rep();

        $this->actingAs($rep)->get('/requirements/officers_members_list/format.pdf')->assertNotFound();
        $this->actingAs($rep)->get('/accreditation/forms/new')->assertNotFound();
    }
}
