<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\LguEvent;
use App\Models\OfficeNotice;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ActivityVerified;
use App\Notifications\OfficeNoticeSent;
use App\Rules\PersonName;
use App\Services\PerformanceScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The fixes from the code review of the LGU events, date and name changes. */
class ReviewFindingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->admin = User::factory()->admin()->create();
    }

    private function event(array $attributes = []): LguEvent
    {
        return LguEvent::create($attributes + [
            'title' => 'Coastal clean-up drive', 'description' => 'Led by the MENRO.', 'venue' => 'Baywalk',
            'held_on' => now()->subDays(3)->toDateString(), 'created_by' => $this->admin->id,
        ]);
    }

    private function lapse(Organization $organization): void
    {
        $organization->accreditations()->update(['status' => 'expired', 'active_org_marker' => null]);
    }

    public function test_a_credit_stays_when_the_organizations_accreditation_lapses_later(): void
    {
        [$a, $b] = Organization::factory()->accredited()->count(2)->create();
        $event = $this->event();
        $event->recordAttendance([$a->id], $this->admin);
        $this->lapse($a);

        // Saving the checklist again (A is no longer on it) and linking B's log both keep A's credit.
        $this->actingAs($this->admin)->post(route('admin.lgu-events.attendance', $event), ['organizations' => []])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get(route('admin.lgu-events.show', $event))->assertSee('accredited at the time: '.$a->name, false);
        $mislogged = Activity::factory()->for($b)->create(['activity_date' => $event->held_on]);
        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $mislogged), ['lgu_event_id' => $event->id])->assertOk();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $event->attendance()->pluck('organization_id')->all());

        // Deleting the event removes every credit, the lapsed one too.
        $this->actingAs($this->admin)->delete(route('admin.lgu-events.destroy', $event));
        $this->assertSame(0, Activity::count());
    }

    public function test_an_unaccredited_organizations_log_is_not_linked_or_lost(): void
    {
        $organization = Organization::factory()->accredited()->create();
        $event = $this->event();
        $activity = Activity::factory()->for($organization)->create(['activity_date' => $event->held_on]);
        $this->lapse($organization);

        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $activity), ['lgu_event_id' => $event->id])
            ->assertStatus(422)->assertJsonPath('message', "{$organization->name} is no longer accredited, so it can't be credited for an LGU event. Verify or reject the activity instead.");

        $this->assertNotNull(Activity::find($activity->id));
        $this->assertSame(0, $event->attendance()->count());
    }

    public function test_an_activity_on_the_first_day_of_the_score_period_counts(): void
    {
        $organization = Organization::factory()->accredited()->create();
        $issued = $organization->accreditations()->first()->issued_at;
        Activity::factory()->for($organization)->verified()->create(['activity_date' => $issued->toDateString(), 'participants_estimate' => 100]);

        $score = app(PerformanceScoreCalculator::class)->recalculate($organization);

        $this->assertGreaterThan(0, $score->activity_frequency);
    }

    public function test_editing_an_event_rescores_and_keeps_a_held_event_in_the_past(): void
    {
        $organization = Organization::factory()->accredited()->create();
        $event = $this->event();
        $event->recordAttendance([$organization->id], $this->admin);
        $before = $organization->performanceScores()->latest('computed_at')->value('computed_at');
        $this->travel(5)->minutes();
        $details = ['title' => 'Coastal clean-up drive', 'description' => 'Led by the MENRO.', 'venue' => 'Baywalk'];

        $this->actingAs($this->admin)->put(route('admin.lgu-events.update', $event), $details + ['held_on' => now()->addWeek()->toDateString()])
            ->assertSessionHasErrors(['held_on' => 'Attendance is already recorded for this event, so its date can\'t be in the future.']);

        $this->actingAs($this->admin)->put(route('admin.lgu-events.update', $event), $details + ['held_on' => now()->subDays(10)->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertTrue($organization->performanceScores()->latest('computed_at')->first()->computed_at->gt($before), 'The score was recalculated.');
        $this->assertSame(now()->subDays(10)->toDateString(), $event->attendance()->sole()->activity_date->toDateString());
    }

    public function test_pending_activities_marked_lgu_organized_become_self_initiated(): void
    {
        $pending = Activity::factory()->create(['activity_source' => 'lgu_organized']);
        $decided = Activity::factory()->verified()->create(['activity_source' => 'lgu_organized']);

        (include database_path('migrations/2026_11_22_130000_pending_activities_are_self_initiated.php'))->up();

        $this->assertSame(['independent', 'lgu_organized'], [$pending->fresh()->activity_source, $decided->fresh()->activity_source]);
    }

    public function test_linking_tells_the_organization_once_in_the_app_and_once_by_email(): void
    {
        $organization = Organization::factory()->accredited()->create();
        $event = $this->event();
        $activity = Activity::factory()->for($organization)->create(['activity_date' => $event->held_on]);

        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $activity), ['lgu_event_id' => $event->id])->assertOk();

        $channels = Notification::sent($organization->user, ActivityVerified::class)->map(fn ($n) => $n->channels)->all();
        $this->assertEqualsCanonicalizing([['database'], ['mail']], $channels);

        // Already credited: a second log is removed without telling them again.
        $again = Activity::factory()->for($organization)->create(['activity_date' => $event->held_on]);
        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $again), ['lgu_event_id' => $event->id])
            ->assertOk()->assertJsonPath('message', "{$organization->name} was already credited for “Coastal clean-up drive”; its duplicate log was removed.");
        $this->assertCount(2, Notification::sent($organization->user, ActivityVerified::class));
    }

    public function test_upcoming_events_are_filtered_by_audience_before_the_first_ten(): void
    {
        $toda = Organization::factory()->accredited()->create(['sector' => 'TODA']);
        $elsewhere = OfficeNotice::create(['title' => 'x', 'body' => 'x', 'audience' => 'barangay', 'audience_value' => 'Baay', 'recipients' => 1]);
        $forToda = OfficeNotice::create(['title' => 'x', 'body' => 'x', 'audience' => 'sector', 'audience_value' => 'TODA', 'recipients' => 1]);
        foreach (range(1, 11) as $day) {
            $this->event(['title' => "Baay event {$day}", 'held_on' => now()->addDays($day)->toDateString(), 'notice_id' => $elsewhere->id]);
        }
        $this->event(['title' => 'TODA road safety seminar', 'held_on' => now()->addDays(20)->toDateString(), 'notice_id' => $forToda->id]);

        $this->actingAs($toda->user)->get(route('cso.activities.index'))
            ->assertSee('TODA road safety seminar')->assertDontSee('Baay event 1');
    }

    public function test_changing_an_announced_events_date_announces_the_change(): void
    {
        $toda = Organization::factory()->accredited()->create(['sector' => 'TODA']);
        $this->actingAs($this->admin)->post(route('admin.lgu-events.store'), [
            'title' => 'Road safety seminar', 'description' => 'For drivers.', 'venue' => 'Terminal', 'held_on' => now()->addDays(5)->toDateString(),
            'announce' => '1', 'audience' => 'sector', 'audience_value' => 'TODA',
        ]);
        $event = LguEvent::sole();

        $this->actingAs($this->admin)->put(route('admin.lgu-events.update', $event), [
            'title' => 'Road safety seminar', 'description' => 'For drivers.', 'venue' => 'Terminal', 'held_on' => now()->addDays(12)->toDateString(),
        ])->assertSessionHas('status', 'LGU event updated. The change was announced to 1 organization.');

        $this->assertSame('LGU event changed: Road safety seminar', $event->fresh()->announcement->title);
        $this->assertStringContainsString(now()->addDays(12)->format('j F Y'), $event->fresh()->announcement->body);
        Notification::assertSentToTimes($toda->user, OfficeNoticeSent::class, 2);

        // Only the description changed: nothing to announce.
        $this->actingAs($this->admin)->put(route('admin.lgu-events.update', $event), [
            'title' => 'Road safety seminar', 'description' => 'For drivers and operators.', 'venue' => 'Terminal', 'held_on' => now()->addDays(12)->toDateString(),
        ])->assertSessionHas('status', 'LGU event updated.');
        Notification::assertSentToTimes($toda->user, OfficeNoticeSent::class, 2);
    }

    public function test_a_name_pasted_with_spaces_around_it_passes_the_browser_check(): void
    {
        $pattern = '/^(?:'.PersonName::PATTERN.')$/u';

        $this->assertSame(1, preg_match($pattern, ' Maria '));
        $this->assertSame(0, preg_match($pattern, ' Maria2'));
        // The inputs carry the server's message, so the browser shows the same words.
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertSee('data-person-name="'.e(PersonName::MESSAGE).'"', false);
    }
}
