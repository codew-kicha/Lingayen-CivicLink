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
            'contact_number' => '0917 123 4567', 'place_of_operation' => 'Barangay Poblacion, Lingayen, Pangasinan',
            'certify' => '1', 'certified_name' => $rep->name,
        ])->assertSessionHasNoErrors();

        return ApplicationSteps::draft($rep->organization->fresh());
    }

    /** Fake PDFs for the given requirement keys. */
    protected function requirementFiles(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key) => [$key => UploadedFile::fake()->create("{$key}.pdf", 100, 'application/pdf')])->all();
    }

    /** Step 5: post uploads (and anything else, such as ocr_text) to the draft. */
    protected function uploadRequirements(User $rep, array $documents, array $extra = []): TestResponse
    {
        return $this->actingAs($rep)->post(route('cso.application.requirements'), $extra + ['step' => 'requirements', 'action' => 'next', 'documents' => $documents]);
    }

    /** The whole online application, submitted; with $optional the DOLE/SEC certificate too. */
    protected function applyOnline(User $rep, string $type = 'new', bool $optional = false): ApplicationModel
    {
        $draft = $this->startApplication($rep, $type);
        $keys = collect(ApplicationSteps::uploads($draft))->reject(fn (array $t) => $t['optional'] && ! $optional)->keys()->all();
        $this->uploadRequirements($rep, $this->requirementFiles($keys))->assertSessionHasNoErrors();
        $this->actingAs($rep)->post(route('cso.application.submit'))->assertSessionHasNoErrors();

        return $draft->fresh();
    }
}
