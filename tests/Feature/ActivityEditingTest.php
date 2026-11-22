<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A CSO correcting or withdrawing its own activity while PESO hasn't reviewed it yet. */
class ActivityEditingTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->rep = User::factory()->create();
        $this->organization = Organization::factory()->for($this->rep)->accredited()->create();
    }

    private function fields(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Mangrove planting',
            'description' => 'Planted 200 seedlings along the river.',
            'activity_date' => now()->subDays(2)->toDateString(),
            'activity_source' => 'independent',
        ];
    }

    public function test_a_pending_activity_can_be_corrected_and_given_a_sign_in_sheet(): void
    {
        $partner = Organization::factory()->accredited()->create();
        $activity = Activity::factory()->for($this->organization)->create(['title' => 'Mangrove plantng']);

        $this->actingAs($this->rep)->get(route('cso.activities.index'))
            ->assertSee(route('cso.activities.edit', $activity))->assertSee('No sign-in sheet yet');
        $this->actingAs($this->rep)->get(route('cso.activities.edit', $activity))->assertOk()->assertSee('value="Mangrove plantng"', false);

        $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields([
            'participants_estimate' => 40,
            'partners' => [$partner->id],
            'evidence' => UploadedFile::fake()->image('sheet.jpg'),
        ]))->assertRedirect(route('cso.activities.index'))->assertSessionHasNoErrors();

        $activity->refresh();
        $this->assertSame(['Mangrove planting', 40, 'sheet.jpg', 'pending'], [$activity->title, $activity->participants_estimate, $activity->evidence_filename, $activity->status]);
        $this->assertSame([$partner->id], $activity->partnerOrganizations->pluck('id')->all());
        Storage::disk('local')->assertExists($activity->evidence_path);
    }

    public function test_a_new_sign_in_sheet_replaces_the_old_file_and_one_can_be_removed(): void
    {
        $activity = Activity::factory()->for($this->organization)->create();
        $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields(['evidence' => UploadedFile::fake()->image('first.jpg')]));
        $first = $activity->refresh()->evidence_path;

        $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields(['evidence' => UploadedFile::fake()->image('second.jpg')]));
        Storage::disk('local')->assertMissing($first);
        $second = $activity->refresh()->evidence_path;
        $this->assertSame('second.jpg', $activity->evidence_filename);

        $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields(['remove_evidence' => '1']));
        Storage::disk('local')->assertMissing($second);
        $this->assertNull($activity->refresh()->evidence_path);
    }

    public function test_a_pending_activity_can_be_withdrawn_with_its_file(): void
    {
        $activity = Activity::factory()->for($this->organization)->create();
        $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields(['evidence' => UploadedFile::fake()->image('sheet.jpg')]));
        $path = $activity->refresh()->evidence_path;

        $this->actingAs($this->rep)->delete(route('cso.activities.destroy', $activity))->assertRedirect(route('cso.activities.index'));

        $this->assertModelMissing($activity);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_reviewed_activities_and_other_organizations_activities_are_locked(): void
    {
        $verified = Activity::factory()->for($this->organization)->verified()->create();
        $someoneElses = Activity::factory()->create();

        foreach ([$verified, $someoneElses] as $activity) {
            $this->actingAs($this->rep)->get(route('cso.activities.edit', $activity))->assertForbidden();
            $this->actingAs($this->rep)->put(route('cso.activities.update', $activity), $this->fields())->assertForbidden();
            $this->actingAs($this->rep)->delete(route('cso.activities.destroy', $activity))->assertForbidden();
            $this->assertModelExists($activity);
        }

        $this->actingAs($this->rep)->get(route('cso.activities.index'))->assertDontSee(route('cso.activities.edit', $verified));
    }
}
