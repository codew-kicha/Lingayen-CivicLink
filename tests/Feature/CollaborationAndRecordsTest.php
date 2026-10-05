<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Milestone 2 Phase 3: PSGC barangays, cross-CSO tagging, assisted encoding, import, inactivity. */
class CollaborationAndRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function accredit(Organization $organization, string $issuedAt): void
    {
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id,
            'verification_code' => 'TEST-'.$organization->id,
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => $issuedAt,
            'expires_at' => now()->addYears(2)->toDateString(),
        ]);
    }

    private function activityPayload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Joint coastal clean-up',
            'description' => 'Collected shoreline waste with partner groups.',
            'activity_date' => now()->subWeek()->toDateString(),
            'participants_estimate' => 120,
        ];
    }

    // PSGC

    public function test_barangays_are_lingayens_32_psgc_entries(): void
    {
        $barangays = config('barangays');

        $this->assertCount(32, $barangays);
        $this->assertSame('Aliwekwek', $barangays['0105522001']);
        $this->assertNotContains('Bacsay', $barangays);
    }

    public function test_the_psgc_code_follows_the_barangay_name(): void
    {
        $organization = Organization::factory()->create(['barangay' => 'Baay']);
        $this->assertSame('0105522002', $organization->barangay_psgc_code);

        $organization->update(['barangay' => 'Wawa']);
        $this->assertSame('0105522035', $organization->fresh()->barangay_psgc_code);
    }

    // Cross-CSO tagging

    public function test_a_cso_can_tag_partners_and_they_are_credited_only_after_verification(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();
        $partner = Organization::factory()->create();

        $this->actingAs($rep)
            ->post(route('cso.activities.store'), $this->activityPayload(['partners' => [$partner->id]]))
            ->assertSessionHasNoErrors();

        $activity = Activity::firstOrFail();
        $this->assertTrue($activity->partnerOrganizations->contains($partner));

        $scores = app(PerformanceScoreCalculator::class);
        $this->assertEquals(0, $scores->recalculate($partner)->activity_frequency);

        Notification::fake();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.activities.verify', $activity));

        $this->assertGreaterThan(0, $partner->performanceScores()->first()->fresh()->activity_frequency);
    }

    public function test_an_organization_cannot_tag_itself_or_an_unknown_organization(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();

        $this->actingAs($rep)
            ->post(route('cso.activities.store'), $this->activityPayload(['partners' => [$organization->id, 99999]]))
            ->assertSessionHasErrors(['partners.0', 'partners.1']);

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_a_verified_partner_activity_shows_on_both_public_profiles(): void
    {
        $lead = Organization::factory()->create(['name' => 'Lead Fisherfolk']);
        $partner = Organization::factory()->create(['name' => 'Partner Weavers']);
        $this->accredit($lead, now()->subYear()->toDateString());
        $this->accredit($partner, now()->subYear()->toDateString());

        $activity = Activity::factory()->for($lead)->verified()->create(['title' => 'Shared mangrove planting']);
        $activity->partnerOrganizations()->attach($partner);

        $this->get(route('directory.show', $partner))->assertSee('Shared mangrove planting')->assertSee('With Lead Fisherfolk');
        $this->get(route('directory.show', $lead))->assertSee('Shared mangrove planting')->assertSee('With Partner Weavers');
    }

    // Assisted encoding

    public function test_admin_can_file_an_application_for_an_organization_without_a_login(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create(['user_id' => null]);

        $documents = collect(config('document_types'))->map(fn ($type, $key) => UploadedFile::fake()->create("{$key}.pdf", 50, 'application/pdf'))->all();

        $this->actingAs($admin)
            ->post(route('admin.organizations.applications.store', $organization), ['type' => 'new', 'documents' => $documents])
            ->assertSessionHasNoErrors();

        $application = $organization->applications()->firstOrFail();
        $this->assertSame('assisted', $application->submission_channel);
        $this->assertSame($admin->id, $application->submitted_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.assisted', 'subject_id' => $organization->id]);

        // Reviewing it must not fail just because nobody can be notified.
        $this->actingAs($admin)
            ->post(route('admin.applications.reject', $application), ['rejection_reason' => 'The by-laws page is unreadable.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $application->fresh()->status);
    }

    public function test_admin_can_log_an_activity_with_partners_for_an_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create(['user_id' => null]);
        $partner = Organization::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.activities.store', $organization), $this->activityPayload(['partners' => [$partner->id]]))
            ->assertRedirect(route('admin.activities.index'));

        $activity = $organization->activities()->firstOrFail();
        $this->assertSame('pending', $activity->status);
        $this->assertSame($admin->id, $activity->logged_by);
        $this->assertTrue($activity->partnerOrganizations->contains($partner));

        // Verifying it doesn't trip over the missing representative either.
        $this->actingAs($admin)->post(route('admin.activities.verify', $activity))->assertSessionHasNoErrors();
    }

    public function test_a_cso_cannot_use_assisted_encoding(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();
        $other = Organization::factory()->create();

        $this->actingAs($rep)->get(route('admin.organizations.applications.create', $other))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.organizations.activities.store', $other), $this->activityPayload())->assertForbidden();
        $this->assertDatabaseCount('activities', 0);
    }

    // Import

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('organizations.csv', $content);
    }

    public function test_admin_can_import_organizations_and_rerunning_is_safe(): void
    {
        $admin = User::factory()->admin()->create();
        Organization::factory()->create(['name' => 'Already Here Association']);

        $file = "\xEF\xBB\xBFName,Sector,Barangay,Advocacy,Status\n"
            ."Wawa Fisherfolk Association,farmers and fisherfolks,Brgy. Wawa,Coastal livelihood,Active\n"
            ."already here association,TODA,Poblacion,,Inactive\n"
            ."Tumbar Senior Circle,Senior Citizen,TUMBAR,,Active\n"
            .",,,,\n";

        $this->actingAs($admin)->post(route('admin.organizations.import.store'), ['file' => $this->csv($file)])
            ->assertRedirect(route('admin.organizations.index', ['account' => 'none']));

        $imported = Organization::where('name', 'Wawa Fisherfolk Association')->firstOrFail();
        $this->assertNull($imported->user_id);
        $this->assertSame('Farmers and Fisherfolks', $imported->sector);
        $this->assertSame('Wawa', $imported->barangay);
        $this->assertSame('0105522035', $imported->barangay_psgc_code);
        $this->assertDatabaseHas('organizations', ['name' => 'Tumbar Senior Circle', 'barangay' => 'Tumbar']);
        $this->assertDatabaseCount('organizations', 3);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organizations.imported']);

        $this->actingAs($admin)->post(route('admin.organizations.import.store'), ['file' => $this->csv($file)]);
        $this->assertDatabaseCount('organizations', 3);
    }

    public function test_one_bad_row_imports_nothing_and_names_the_line(): void
    {
        $admin = User::factory()->admin()->create();

        $file = "name,sector,barangay\nGood Association,TODA,Poblacion\nBad Association,Basketball,Poblacion\nWrong Place,TODA,Bacsay\n";

        $this->actingAs($admin)->post(route('admin.organizations.import.store'), ['file' => $this->csv($file)])
            ->assertSessionHas('importErrors', fn ($errors) => isset($errors[3], $errors[4]) && ! isset($errors[2]));

        $this->assertDatabaseCount('organizations', 0);
    }

    public function test_the_import_command_supports_a_dry_run(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'orgs');
        file_put_contents($path, "name,sector,barangay\nDry Run Association,TODA,Poblacion\n");

        $this->artisan('organizations:import', ['file' => $path, '--dry-run' => true])
            ->expectsOutput('Would import 1 organizations.')->assertSuccessful();
        $this->assertDatabaseCount('organizations', 0);

        $this->artisan('organizations:import', ['file' => $path])->assertSuccessful();
        $this->assertDatabaseHas('organizations', ['name' => 'Dry Run Association']);
    }

    public function test_a_cso_cannot_import(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.organizations.import.store'), ['file' => $this->csv("name,sector,barangay\nX,TODA,Poblacion\n")])
            ->assertForbidden();
        $this->assertDatabaseCount('organizations', 0);
    }

    // Inactive flag

    public function test_inactive_means_accredited_but_no_recent_verified_activity(): void
    {
        $longQuiet = Organization::factory()->create();
        $this->accredit($longQuiet, now()->subYear()->toDateString());
        Activity::factory()->for($longQuiet)->verified()->create(['activity_date' => now()->subMonths(8)]);
        Activity::factory()->for($longQuiet)->create(['activity_date' => now()->subWeek()]); // pending, doesn't count

        $active = Organization::factory()->create();
        $this->accredit($active, now()->subYear()->toDateString());
        Activity::factory()->for($active)->verified()->create(['activity_date' => now()->subMonth()]);

        $partnerOnly = Organization::factory()->create();
        $this->accredit($partnerOnly, now()->subYear()->toDateString());
        Activity::factory()->for($active)->verified()->create(['activity_date' => now()->subMonth()])
            ->partnerOrganizations()->attach($partnerOnly);

        $newlyAccredited = Organization::factory()->create();
        $this->accredit($newlyAccredited, now()->subMonth()->toDateString());

        $neverAccredited = Organization::factory()->create();

        $this->assertSame([$longQuiet->id], Organization::inactive()->pluck('id')->all());

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.organizations.index', ['status' => 'inactive']))
            ->assertOk()->assertSee($longQuiet->name)->assertDontSee($active->name)->assertDontSee($neverAccredited->name);
    }
}
