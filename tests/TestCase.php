<?php

namespace Tests;

use App\Models\ApplicationModel;
use App\Models\User;
use App\Services\ApplicationSteps;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /** A valid member list: the representative first, then enough members to reach the minimum. */
    protected function memberRows(int $count = 5): array
    {
        $rows = [['last_name' => 'Reyes', 'first_name' => 'Ana', 'position' => 'Representative', 'contact_number' => '', 'email' => '']];
        foreach (array_slice(['Cruz', 'Santos', 'Garcia', 'Mendoza', 'Bautista', 'Torres', 'Ramos'], 0, $count - 1) as $i => $last) {
            $rows[] = ['last_name' => $last, 'first_name' => 'Member'.($i + 1), 'position' => $i === 0 ? 'Treasurer' : '', 'contact_number' => '', 'email' => ''];
        }

        return $rows;
    }

    /** Steps 3 and 4 of applying online: the members and the certified form. Returns the draft. */
    protected function startApplication(User $rep, string $type = 'new'): ApplicationModel
    {
        $this->actingAs($rep)->get(route('cso.application'));
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $this->memberRows()])
            ->assertSessionHasNoErrors();
        $this->actingAs($rep)->post(route('cso.application.form'), [
            'step' => 'form', 'action' => 'next', 'type' => $type,
            'organization_name' => $rep->organization->name, 'representative_name' => $rep->name,
            'contact_number' => '0917 123 4567', 'place_barangay' => 'Poblacion', 'place_detail' => '',
            'certify' => '1', 'certified_name' => $rep->name,
        ])->assertSessionHasNoErrors();

        return ApplicationSteps::draft($rep->organization->fresh());
    }

    /** Fake PDFs for the given requirement keys. */
    protected function requirementFiles(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key) => [$key => UploadedFile::fake()->create("{$key}.pdf", 100, 'application/pdf')])->all();
    }

    /** Steps 3 to 5: members, form, and submit. The application waits for the Desk Office (submitted). */
    protected function submitForm(User $rep, string $type = 'new'): ApplicationModel
    {
        $draft = $this->startApplication($rep, $type);
        $this->actingAs($rep)->post(route('cso.application.submit'))->assertSessionHasNoErrors();

        return $draft->fresh();
    }

    /** The Desk Office approves the application form, which asks for the requirements. */
    protected function approveForm(ApplicationModel $application, ?User $admin = null): ApplicationModel
    {
        $this->actingAs($admin ?? User::factory()->admin()->create())
            ->post(route('admin.applications.approve-form', $application))->assertSessionHasNoErrors();

        return $application->fresh();
    }

    /** The Desk Office records a Sangguniang Bayan reading: session date, outcome, remarks for the CSO. */
    protected function recordReading(ApplicationModel $application, string $stage, string $outcome = 'passed', ?User $admin = null, string $remarks = 'Discussed and found in order.'): TestResponse
    {
        return $this->actingAs($admin ?? User::factory()->admin()->create())->patch(route('admin.applications.stage', $application), [
            'sb_stage' => $stage, 'outcome' => $outcome, 'remarks' => $remarks, 'held_on' => now()->toDateString(),
        ]);
    }

    /** Step 6: post uploads (and anything else, such as ocr_text) once the form is approved; submits unless action=later. */
    protected function uploadRequirements(User $rep, array $documents, array $extra = []): TestResponse
    {
        return $this->actingAs($rep)->post(route('cso.application.requirements'), $extra + ['action' => 'submit', 'documents' => $documents]);
    }

    /**
     * The whole online application: form submitted, approved by the Desk Office, requirements
     * submitted (requirements_submitted, ready for the SB readings). With $optional the DOLE/SEC
     * certificate too.
     */
    protected function applyOnline(User $rep, string $type = 'new', bool $optional = false): ApplicationModel
    {
        $application = $this->approveForm($this->submitForm($rep, $type));
        $keys = collect(ApplicationSteps::uploads($application))->reject(fn (array $t) => $t['optional'] && ! $optional)->keys()->all();
        $this->uploadRequirements($rep, $this->requirementFiles($keys))->assertSessionHasNoErrors();

        return $application->fresh();
    }
}
