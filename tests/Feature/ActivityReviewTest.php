<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Verifying and rejecting logged activities from the Verify activities queue. */
class ActivityReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_the_queue_is_a_table_oldest_first_with_decisions_in_a_dialog(): void
    {
        $admin = User::factory()->admin()->create();
        $newer = Activity::factory()->create(['title' => 'Coastal clean-up', 'created_at' => now()->subDays(3)]);
        $older = Activity::factory()->create(['title' => 'Feeding program', 'created_at' => now()->subDays(45)]);
        Activity::factory()->verified()->create(['title' => 'Already verified']);

        $html = $this->actingAs($admin)->get(route('admin.activities.index'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Coastal clean-up</p>'), strpos($html, 'Feeding program</p>'), 'The oldest waits first.');
        $this->assertStringNotContainsString('Already verified', $html);
        $this->assertStringContainsString('45 days', $html);
        $this->assertStringContainsString('font-semibold text-warning', $html, 'Over 30 days is flagged.');
        // No inline decisions or status chips in the list; the dialog asks for a source, not pre-filled.
        $this->assertStringNotContainsString('Pending verification', $html);
        $this->assertSame(1, substr_count($html, 'id="review-source"'));
        $this->assertMatchesRegularExpression('/<option value="" disabled>Choose who organized it<\/option>/', $html);
        $this->assertStringNotContainsString('selected', substr($html, strpos($html, 'id="review-source"'), 600));
    }

    public function test_a_too_short_reason_is_refused_with_a_message_for_the_dialog(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($admin)->postJson(route('admin.activities.reject', $activity), ['rejection_reason' => 'No photos'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.rejection_reason.0', 'Write at least 10 characters so the organization knows what to correct.');
        $this->assertSame('pending', $activity->fresh()->status);

        $this->actingAs($admin)->postJson(route('admin.activities.reject', $activity), ['rejection_reason' => 'No attendance sheet was attached.'])
            ->assertOk()->assertJsonPath('message', 'Activity rejected and the organization has been notified.');
        $this->assertSame('rejected', $activity->fresh()->status);

        $this->actingAs($admin)->postJson(route('admin.activities.verify', $activity), ['activity_source' => 'independent'])
            ->assertUnprocessable()->assertJsonPath('message', 'This activity has already been reviewed.');
    }

    public function test_verifying_from_the_dialog_needs_a_source(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['activity_source' => 'independent']);

        $this->actingAs($admin)->postJson(route('admin.activities.verify', $activity), [])
            ->assertUnprocessable()->assertJsonValidationErrors('activity_source');

        $this->actingAs($admin)->postJson(route('admin.activities.verify', $activity), ['activity_source' => 'lgu_organized'])
            ->assertOk()->assertJsonPath('message', 'Activity verified and the score has been recalculated.');
        $this->assertSame(['verified', 'lgu_organized'], [$activity->fresh()->status, $activity->fresh()->activity_source]);
    }

    public function test_a_sign_in_sheet_is_kept_private_and_previewed_in_the_review(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->accredited()->create();

        $this->actingAs($rep)->post(route('cso.activities.store'), [
            'title' => 'Mangrove planting',
            'description' => 'Planted 200 seedlings along the river.',
            'activity_date' => now()->subDay()->toDateString(),
            'activity_source' => 'independent',
            'evidence' => UploadedFile::fake()->image('sign-in.jpg'),
        ])->assertSessionHasNoErrors();

        $activity = $organization->activities()->firstOrFail();
        $this->assertSame(['sign-in.jpg', 'image/jpeg'], [$activity->evidence_filename, $activity->evidence_mime]);
        Storage::disk('local')->assertExists($activity->evidence_path);

        $this->actingAs($admin)->get(route('admin.activities.index'))
            ->assertSee(str_replace('/', '\\\\\\/', route('activities.evidence', $activity)), false)  // escaped inside the dialog's @js data
            ->assertSee('sign-in.jpg');
        $this->actingAs($admin)->get(route('activities.evidence', $activity))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($rep)->get(route('activities.evidence', $activity))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('activities.evidence', $activity))->assertForbidden();
    }

    public function test_the_sign_in_sheet_must_be_a_pdf_or_image(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->accredited()->create();

        $this->actingAs($rep)->post(route('cso.activities.store'), [
            'title' => 'Seminar', 'description' => 'A seminar.', 'activity_date' => now()->toDateString(),
            'activity_source' => 'independent', 'evidence' => UploadedFile::fake()->create('notes.docx', 10),
        ])->assertSessionHasErrors(['evidence' => 'The sign-in sheet must be a PDF, JPG, or PNG file.']);
    }

    public function test_a_proper_reason_rejects_the_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($admin)->from(route('admin.activities.index'))
            ->post(route('admin.activities.reject', $activity), ['rejection_reason' => 'No attendance sheet or photographs were attached.'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame('rejected', $activity->fresh()->status);
        $this->assertSame('No attendance sheet or photographs were attached.', $activity->fresh()->rejection_reason);
    }

    public function test_verifying_twice_in_a_day_for_an_unaccredited_organization_does_not_fail(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();   // no accreditation: scored on the trailing year
        [$first, $second] = Activity::factory()->count(2)->for($organization)->create(['activity_source' => 'independent']);

        foreach ([$first, $second] as $activity) {
            $this->actingAs($admin)->post(route('admin.activities.verify', $activity), ['activity_source' => 'independent'])
                ->assertSessionHasNoErrors()->assertRedirect();
        }

        $this->assertSame('verified', $second->fresh()->status);
        $this->assertSame(1, $organization->performanceScores()->count());
    }
}
