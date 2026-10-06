<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_a_too_short_reason_is_explained_on_that_activity_only(): void
    {
        $admin = User::factory()->admin()->create();
        [$target, $other] = Activity::factory()->count(2)->create();

        $this->actingAs($admin)->from(route('admin.activities.index'))
            ->post(route('admin.activities.reject', $target), ['rejection_reason' => 'No photos', 'rejecting' => $target->id])
            ->assertRedirect(route('admin.activities.index').'#activity-'.$target->id)
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending', $target->fresh()->status);

        $html = $this->actingAs($admin)->get(route('admin.activities.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'Write at least 10 characters'), 'The error shows once, on the submitted activity.');
        $this->assertStringContainsString('id="reason-'.$target->id.'-error"', $html);
        $this->assertStringContainsString('value="No photos"', $html);
        $this->assertSame(1, substr_count($html, 'field-input field-input-error'), 'Only the submitted activity is marked invalid.');
        $this->assertStringContainsString('minlength="10"', $html);
    }

    public function test_a_proper_reason_rejects_the_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($admin)->from(route('admin.activities.index'))
            ->post(route('admin.activities.reject', $activity), ['rejection_reason' => 'No attendance sheet or photographs were attached.', 'rejecting' => $activity->id])
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
