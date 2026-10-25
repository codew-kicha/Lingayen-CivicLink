<?php

namespace Tests\Feature;

use App\Exports\ReportSheet;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\NewsPost;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/** The Applications spreadsheet, and deleting or merging organization records. */
class DirectoryCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function accredit(Organization $organization, string $code): void
    {
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id, 'verification_code' => $code, 'status' => 'active',
            'active_org_marker' => $organization->id, 'issued_at' => now(), 'expires_at' => now()->addYears(3),
        ]);
    }

    public function test_the_applications_list_downloads_with_its_filters(): void
    {
        Excel::fake();
        $waiting = Organization::factory()->create(['name' => 'Waiting Weavers']);
        ApplicationModel::factory()->for($waiting)->create(['status' => 'under_review', 'sb_stage' => 'first_reading', 'submitted_at' => now()]);
        ApplicationModel::factory()->for(Organization::factory()->create(['name' => 'Approved Anglers']))->approved()->create();

        $this->actingAs($this->admin())->get(route('admin.applications.index'))
            ->assertSee(route('admin.applications.export'), false);
        $this->actingAs($this->admin())->get(route('admin.applications.export', ['status' => 'review']))->assertOk();

        Excel::assertDownloaded('applications-'.now()->format('Y-m-d').'.xlsx', function (ReportSheet $sheet) {
            $rows = $sheet->array();

            return count($rows) === 1 && $rows[0][0] === 'Waiting Weavers'
                && $rows[0][5] === 'Under Review' && $rows[0][6] === 'First Reading';
        });

        $this->actingAs(User::factory()->create())->get(route('admin.applications.export'))->assertForbidden();
    }

    public function test_deleting_an_organization_needs_the_admin_password_and_removes_everything_it_owns(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $rep = User::factory()->create(['email' => 'test-group@example.ph']);
        $organization = Organization::factory()->for($rep)->create(['name' => 'Test Group']);
        $application = ApplicationModel::factory()->for($organization)->create();
        Storage::disk('local')->put('documents/x/receipt.pdf', 'pdf');
        $organization->documents()->create([
            'application_id' => $application->id, 'document_type' => 'fee_receipt', 'file_path' => 'documents/x/receipt.pdf',
            'original_filename' => 'receipt.pdf', 'mime_type' => 'application/pdf',
        ]);
        Storage::disk('local')->put('activity-evidence/x/sheet.png', 'png');
        $activity = Activity::factory()->for($organization)->create(['evidence_path' => 'activity-evidence/x/sheet.png']);

        $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))->assertOk()
            ->assertSee('Delete Test Group?')->assertSee('test-group@example.ph');

        $this->actingAs($admin)->delete(route('admin.organizations.destroy', $organization), ['admin_password' => 'wrong'])
            ->assertSessionHasErrors('admin_password');
        $this->assertModelExists($organization);

        $this->actingAs($admin)->delete(route('admin.organizations.destroy', $organization), ['admin_password' => 'password'])
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertModelMissing($organization);
        $this->assertModelMissing($rep);
        $this->assertModelMissing($application);
        $this->assertModelMissing($activity);
        Storage::disk('local')->assertMissing(['documents/x/receipt.pdf', 'activity-evidence/x/sheet.png']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.deleted']);
    }

    public function test_merging_moves_the_history_onto_the_record_that_stays(): void
    {
        $admin = $this->admin();
        $rep = User::factory()->create();
        $duplicate = Organization::factory()->for($rep)->create(['name' => 'Libsong Weavers (dup)', 'advocacy' => 'Weaving livelihood']);
        $keep = Organization::factory()->create(['name' => 'Libsong Weavers', 'user_id' => null, 'advocacy' => null]);
        $this->accredit($duplicate, 'DUP-0001');
        $this->accredit($keep, 'KEEP-0001');

        $ownActivity = Activity::factory()->for($duplicate)->verified()->create();
        $taggedOnKeep = Activity::factory()->for($keep)->create();
        $taggedOnKeep->partnerOrganizations()->attach($duplicate);       // would become a self-tag
        $ownActivity->partnerOrganizations()->attach($keep);             // likewise, once moved
        $elsewhere = Activity::factory()->create();
        $elsewhere->partnerOrganizations()->attach([$duplicate->id, $keep->id]);   // would double up
        $post = NewsPost::factory()->create(['author_id' => $admin->id]);
        $post->organizations()->attach([$duplicate->id, $keep->id]);

        $this->actingAs($admin)->post(route('admin.organizations.merge', $duplicate), ['into' => $duplicate->id, 'admin_password' => 'password'])
            ->assertSessionHasErrors('into');

        $this->actingAs($admin)->post(route('admin.organizations.merge', $duplicate), ['into' => $keep->id, 'admin_password' => 'password'])
            ->assertRedirect(route('admin.organizations.edit', $keep));

        $this->assertModelMissing($duplicate);
        $keep->refresh();
        $this->assertSame(2, $keep->applications()->count());
        $this->assertSame(['KEEP-0001'], $keep->accreditations()->where('status', 'active')->pluck('verification_code')->all());
        $this->assertSame('expired', $keep->accreditations()->where('verification_code', 'DUP-0001')->value('status'));
        $this->assertSame($keep->id, $ownActivity->refresh()->organization_id);
        $this->assertSame(0, $taggedOnKeep->partnerOrganizations()->count(), 'No organization is tagged on its own activity.');
        $this->assertSame(0, $ownActivity->partnerOrganizations()->count());
        $this->assertSame([$keep->id], $elsewhere->partnerOrganizations()->pluck('organizations.id')->all());
        $this->assertSame([$keep->id], $post->organizations()->pluck('organizations.id')->all());
        $this->assertSame($rep->id, $keep->user_id, 'The login moves to a record that had none.');
        $this->assertSame('Weaving livelihood', $keep->advocacy);
        $this->assertNotNull($keep->performanceScores()->first());
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.merged', 'subject_id' => $keep->id]);
    }

    public function test_when_both_have_a_login_the_duplicates_login_is_deactivated(): void
    {
        $duplicateRep = User::factory()->create();
        $keepRep = User::factory()->create();
        $duplicate = Organization::factory()->for($duplicateRep)->create();
        $keep = Organization::factory()->for($keepRep)->create();

        $this->actingAs($this->admin())->post(route('admin.organizations.merge', $duplicate), ['into' => $keep->id, 'admin_password' => 'password'])
            ->assertRedirect();

        $this->assertSame($keepRep->id, $keep->refresh()->user_id);
        $this->assertFalse($duplicateRep->refresh()->is_active);
    }

    public function test_cso_users_cannot_delete_or_merge(): void
    {
        $rep = User::factory()->create();
        [$one, $two] = Organization::factory()->count(2)->create();

        $this->actingAs($rep)->delete(route('admin.organizations.destroy', $one), ['admin_password' => 'password'])->assertForbidden();
        $this->actingAs($rep)->post(route('admin.organizations.merge', $one), ['into' => $two->id, 'admin_password' => 'password'])->assertForbidden();
        $this->assertModelExists($one);
    }
}
