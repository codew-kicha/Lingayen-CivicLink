<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\FeeFreePeriod;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccreditationFeeChanged;
use App\Notifications\ApplicationStatusChanged;
use App\Services\ApplicationSteps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The client (Nov 2026): accreditation is free during the period a DILG memorandum opens after each
 * new LGU administration, and then lasts three years. Filed outside it, the fee applies (PHP 1,000
 * new, PHP 500 renewal) and the accreditation lasts one year, renewed yearly. The Desk Office
 * records the periods and can waive or require the fee on one application.
 */
class AccreditationFeeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    private function rep(): User
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create(['name' => 'Sabangan Youth Council']);

        return $rep;
    }

    private function openPeriod(): FeeFreePeriod
    {
        return FeeFreePeriod::create(['memorandum' => 'DILG MC No. 2028-010', 'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(2)]);
    }

    public function test_filed_within_a_free_period_needs_no_receipt_and_runs_three_years(): void
    {
        $this->openPeriod();
        $rep = $this->rep();

        $application = $this->approveForm($this->submitForm($rep, 'renewal'), $this->admin);

        $this->assertTrue($application->fee_waived);
        $this->assertSame(0, $application->fee());
        $this->assertArrayNotHasKey('fee_receipt', ApplicationSteps::uploads($application));
        Notification::assertSentTo($rep, ApplicationStatusChanged::class, fn ($n) => str_contains($n->toDatabase($rep)['body'], 'There is no accreditation fee: filed within the free period under DILG MC No. 2028-010.')
            && ! str_contains($n->toDatabase($rep)['body'], 'Official Receipt'));

        $this->actingAs($rep)->get(route('cso.application.requirements.show'))->assertOk()
            ->assertSee('No accreditation fee')->assertSee('filed within the free period under DILG MC No. 2028-010')->assertDontSee('Official Receipt of the Accreditation Fee');

        // Nothing else to upload on a renewal: submitting without a receipt is accepted.
        $this->uploadRequirements($rep, [])->assertSessionHasNoErrors();
        $this->assertSame('requirements_submitted', $application->fresh()->status);

        $this->forwardToCommittee($application, $this->admin);
        $this->recordHearing($application, admin: $this->admin);
        $this->actingAs($this->admin)->get(route('admin.applications.show', $application))
            ->assertSee('No fee: filed within the free period under DILG MC No. 2028-010')
            ->assertSee('value="'.now()->addYears(3)->toDateString().'"', false);
        $this->actingAs($this->admin)->post(route('admin.applications.approve', $application))->assertSessionHasNoErrors();
        $this->assertSame(now()->addYears(3)->toDateString(), $application->fresh()->accreditation->expires_at->toDateString());
    }

    public function test_filed_outside_every_period_pays_and_runs_one_year(): void
    {
        FeeFreePeriod::create(['memorandum' => 'DILG MC No. 2025-060', 'starts_on' => '2025-07-01', 'ends_on' => '2025-09-30']);
        $rep = $this->rep();

        $application = $this->approveForm($this->submitForm($rep), $this->admin);

        $this->assertFalse($application->fee_waived);
        $this->assertSame(1000, $application->fee());
        Notification::assertSentTo($rep, ApplicationStatusChanged::class, fn ($n) => str_contains($n->toDatabase($rep)['body'], 'Pay the PHP 1,000 accreditation fee at the Municipal Treasury'));
        $this->actingAs($rep)->get(route('cso.application.requirements.show'))
            ->assertSee('Pay the PHP 1,000 accreditation fee at the Municipal Treasury');

        // Submitting without the receipt is refused.
        $this->uploadRequirements($rep, $this->requirementFiles(['constitution_bylaws']))->assertSessionHasErrors();
        $this->assertContains('Upload your Official Receipt of the Accreditation Fee.', session('errors')->all());
        $this->assertSame('requirements_pending', $application->fresh()->status);

        $this->uploadRequirements($rep, $this->requirementFiles(['fee_receipt']))->assertSessionHasNoErrors();
        $this->forwardToCommittee($application, $this->admin);
        $this->recordHearing($application, admin: $this->admin);
        $this->actingAs($this->admin)->post(route('admin.applications.approve', $application));
        $this->assertSame(now()->addYear()->toDateString(), $application->fresh()->accreditation->expires_at->toDateString());
    }

    public function test_the_office_can_waive_the_fee_and_the_cso_is_told(): void
    {
        $rep = $this->rep();
        $application = $this->approveForm($this->submitForm($rep), $this->admin);

        $this->actingAs($this->admin)->get(route('admin.applications.show', $application))->assertSee('Waive the fee');
        $this->actingAs($this->admin)->patch(route('admin.applications.fee', $application), ['fee_waived' => '1'])->assertSessionHasNoErrors();

        $this->assertSame([true, 'No fee: waived by the Desk Office'], [$application->fresh()->fee_waived, $application->fresh()->feeNote()]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.fee_waived']);
        Notification::assertSentTo($rep, AccreditationFeeChanged::class, fn ($n) => $n->toDatabase($rep)['headline'] === 'No accreditation fee for your application');
        $this->actingAs($rep)->get(route('cso.applications.show', $application))->assertSee('No fee: waived by the Desk Office');
    }

    public function test_requiring_the_fee_after_submission_reopens_the_requirements(): void
    {
        $this->openPeriod();
        $rep = $this->rep();
        $application = $this->applyOnline($rep);
        $this->assertSame(['requirements_submitted', true], [$application->status, $application->fee_waived]);

        $this->actingAs($this->admin)->patch(route('admin.applications.fee', $application), ['fee_waived' => '0'])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(['requirements_pending', false], [$application->status, $application->fee_waived]);
        $this->assertStringContainsString('Upload the official receipt of the PHP 1,000 accreditation fee', $application->requirements_note);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.fee_required']);
        Notification::assertSentTo($rep, AccreditationFeeChanged::class, fn ($n) => $n->reopened
            && str_contains($n->toDatabase($rep)['body'], 'Your requirements were reopened'));
        $this->assertArrayHasKey('fee_receipt', ApplicationSteps::uploads($application));
    }

    public function test_the_fee_is_settled_once_the_application_goes_to_the_committee(): void
    {
        $application = ApplicationModel::factory()->forHearing()->create();

        $this->actingAs($this->admin)->patch(route('admin.applications.fee', $application), ['fee_waived' => '1'])->assertStatus(422);
        $this->actingAs($this->admin)->get(route('admin.applications.show', $application))->assertDontSee('Waive the fee');
        $this->actingAs($this->rep())->patch(route('admin.applications.fee', $application), ['fee_waived' => '1'])->assertForbidden();
        $this->assertFalse($application->fresh()->fee_waived);
    }

    public function test_the_office_records_free_periods(): void
    {
        $this->actingAs($this->admin)->get(route('admin.fee-periods.index'))->assertOk()->assertSee('No free periods recorded');

        $this->actingAs($this->admin)->post(route('admin.fee-periods.store'), ['memorandum' => '', 'starts_on' => '2028-07-31', 'ends_on' => '2028-07-01'])
            ->assertSessionHasErrors(['memorandum', 'ends_on']);
        $this->actingAs($this->admin)->post(route('admin.fee-periods.store'), [
            'memorandum' => 'DILG MC No. 2028-010', 'starts_on' => '2028-07-01', 'ends_on' => '2028-09-30',
        ])->assertSessionHasNoErrors();

        $period = FeeFreePeriod::sole();
        $this->assertSame(['upcoming', $this->admin->id], [$period->status(), $period->created_by]);
        $this->actingAs($this->admin)->put(route('admin.fee-periods.update', $period), [
            'memorandum' => 'DILG MC No. 2028-010', 'starts_on' => '2028-07-01', 'ends_on' => '2028-10-31',
        ])->assertSessionHasNoErrors();
        $this->assertSame('2028-10-31', $period->fresh()->ends_on->toDateString());
        $this->actingAs($this->admin)->get(route('admin.fee-periods.index'))->assertSee('1 July 2028 to 31 October 2028')->assertSee('Upcoming');

        $this->actingAs($this->rep())->post(route('admin.fee-periods.store'), [
            'memorandum' => 'x', 'starts_on' => '2028-07-01', 'ends_on' => '2028-09-30',
        ])->assertForbidden();

        $this->actingAs($this->admin)->delete(route('admin.fee-periods.destroy', $period))->assertSessionHasNoErrors();
        $this->assertSame(0, FeeFreePeriod::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'fee_period.deleted']);
    }

    public function test_the_public_pages_say_when_filing_is_free(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Filing is free until');

        $period = $this->openPeriod();
        $this->get(route('home'))->assertSee('Filing is free until '.$period->ends_on->format('j F Y'))->assertSee('DILG MC No. 2028-010');
        $this->get(route('accreditation'))->assertSee('Filing is free until');
        $this->get(route('resources'))->assertSee('When is accreditation free?');
    }

    public function test_a_paper_filing_within_a_free_period_needs_no_receipt(): void
    {
        $organization = Organization::factory()->create(['user_id' => null]);
        $documents = collect(['accreditation_form', 'officers_members_list', 'constitution_bylaws'])
            ->mapWithKeys(fn ($key) => [$key => UploadedFile::fake()->create("{$key}.pdf", 50, 'application/pdf')])->all();

        // Paid unless ticked: the receipt is required.
        $this->actingAs($this->admin)->post(route('admin.organizations.applications.store', $organization), ['type' => 'new', 'documents' => $documents])
            ->assertSessionHasErrors('documents.fee_receipt');

        $this->actingAs($this->admin)->post(route('admin.organizations.applications.store', $organization), [
            'type' => 'new', 'fee_waived' => '1', 'documents' => $documents,
        ])->assertSessionHasNoErrors();
        $application = $organization->applications()->sole();
        $this->assertTrue($application->fee_waived);

        // Its form approval finds every requirement in, so it goes straight to validation.
        $this->approveForm($application, $this->admin);
        $this->assertSame('requirements_submitted', $application->fresh()->status);
    }
}
