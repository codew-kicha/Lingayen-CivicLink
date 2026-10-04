<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the areas PRD §8 names for extra scrutiny: file uploads, role-based access, and the
 * privacy rule that member details never reach the public site.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_rejects_a_disallowed_file_type(): void
    {
        Storage::fake('local');

        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        $this->actingAs($rep)
            ->post(route('cso.applications.store'), [
                'type' => 'new',
                'documents' => ['accreditation_form' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php')],
            ])
            ->assertSessionHasErrors('documents.accreditation_form');

        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_rejects_a_file_over_the_size_limit(): void
    {
        Storage::fake('local');

        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        $this->actingAs($rep)
            ->post(route('cso.applications.store'), [
                'type' => 'new',
                'documents' => ['accreditation_form' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')],
            ])
            ->assertSessionHasErrors('documents.accreditation_form');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_a_cso_cannot_download_another_organisations_document(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $ownerOrg = Organization::factory()->for($owner)->create();
        $document = $ownerOrg->documents()->create([
            'document_type' => 'accreditation_form',
            'file_path' => 'documents/private.pdf',
            'original_filename' => 'private.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $intruder = User::factory()->create();
        Organization::factory()->for($intruder)->create();

        $this->actingAs($intruder)->get(route('documents.download', $document))->assertForbidden();
    }

    public function test_a_guest_cannot_download_a_document(): void
    {
        Storage::fake('local');

        $document = Organization::factory()->create()->documents()->create([
            'document_type' => 'accreditation_form',
            'file_path' => 'documents/private.pdf',
            'original_filename' => 'private.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_a_cso_cannot_reach_another_organisations_application(): void
    {
        $intruder = User::factory()->create();
        Organization::factory()->for($intruder)->create();
        $application = ApplicationModel::factory()->create();

        $this->actingAs($intruder)->get(route('cso.applications.show', $application))->assertForbidden();
    }

    public function test_a_cso_cannot_use_admin_review_or_verification_actions(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();
        $application = ApplicationModel::factory()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($rep)->post(route('admin.applications.approve', $application))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.activities.verify', $activity))->assertForbidden();
        $this->actingAs($rep)->patch(route('admin.organizations.visibility', $activity->organization))->assertForbidden();

        $this->assertDatabaseCount('accreditations', 0);
        $this->assertSame('pending', $activity->refresh()->status);
    }

    public function test_member_contact_details_never_appear_on_the_public_profile(): void
    {
        $organization = Organization::factory()->create();
        $organization->accreditations()->create([
            'application_id' => ApplicationModel::factory()->for($organization)->create()->id,
            'verification_code' => 'TEST-CODE-0001',
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(3)->toDateString(),
        ]);

        $organization->members()->create([
            'name' => 'Maria Dela Peña',
            'position' => 'President',
            'contact_number' => '09171234567',
            'email' => 'maria@example.ph',
        ]);

        $this->get(route('directory.show', $organization))
            ->assertOk()
            ->assertDontSee('Maria Dela Peña')
            ->assertDontSee('09171234567')
            ->assertDontSee('maria@example.ph');
    }

    public function test_a_hidden_organization_is_not_reachable_publicly(): void
    {
        $organization = Organization::factory()->hidden()->create();
        $organization->accreditations()->create([
            'application_id' => ApplicationModel::factory()->for($organization)->create()->id,
            'verification_code' => 'TEST-CODE-0002',
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addYears(3)->toDateString(),
        ]);

        $this->get(route('directory'))->assertOk()->assertDontSee($organization->name);
        $this->get(route('directory.show', $organization))->assertNotFound();
    }

    public function test_an_unaccredited_organization_is_not_listed_publicly(): void
    {
        $organization = Organization::factory()->create();

        $this->get(route('directory'))->assertOk()->assertDontSee($organization->name);
        $this->get(route('directory.show', $organization))->assertNotFound();
    }

    public function test_home_ticker_only_shows_activities_from_publicly_listed_organizations(): void
    {
        $accredit = function (Organization $organization, string $code) {
            $organization->accreditations()->create([
                'application_id' => ApplicationModel::factory()->for($organization)->create()->id,
                'verification_code' => $code,
                'status' => 'active',
                'active_org_marker' => $organization->id,
                'issued_at' => now()->toDateString(),
                'expires_at' => now()->addYears(3)->toDateString(),
            ]);
        };

        $listed = Organization::factory()->create();
        $accredit($listed, 'TEST-CODE-0010');
        $hidden = Organization::factory()->hidden()->create();
        $accredit($hidden, 'TEST-CODE-0011');
        $unaccredited = Organization::factory()->create();

        Activity::factory()->for($listed)->verified()->create(['title' => 'Listed org seminar']);
        Activity::factory()->for($hidden)->verified()->create(['title' => 'Hidden org seminar']);
        Activity::factory()->for($unaccredited)->verified()->create(['title' => 'Unaccredited org seminar']);
        Activity::factory()->for($listed)->create(['title' => 'Pending listed seminar']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Listed org seminar')
            ->assertDontSee('Hidden org seminar')
            ->assertDontSee('Unaccredited org seminar')
            ->assertDontSee('Pending listed seminar');
    }
}
