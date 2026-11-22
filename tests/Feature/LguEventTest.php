<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\LguEvent;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ActivityVerified;
use App\Notifications\OfficeNoticeSent;
use App\Services\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * LGU-organized activities come from the office (pre-final defense panel): the Desk Office records an
 * LGU event, may announce it, and credits the organizations that attended. A CSO no longer says who
 * organized what it logs; one that logs an LGU event anyway is linked to the event instead.
 */
class LguEventTest extends TestCase
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
            'title' => 'Municipal road safety seminar', 'description' => 'Road safety briefing for transport groups.',
            'venue' => 'Municipal Gymnasium', 'held_on' => now()->subDays(2)->toDateString(), 'created_by' => $this->admin->id,
        ]);
    }

    public function test_the_office_creates_and_announces_an_lgu_event(): void
    {
        $toda = Organization::factory()->accredited()->create(['sector' => 'TODA']);
        $other = Organization::factory()->accredited()->create(['sector' => 'Health']);

        $this->actingAs($this->admin)->post(route('admin.lgu-events.store'), [
            'title' => 'Road safety seminar', 'description' => 'A refresher for tricycle drivers.', 'venue' => 'Poblacion terminal',
            'held_on' => now()->addDays(5)->toDateString(), 'announce' => '1', 'audience' => 'sector', 'audience_value' => 'TODA',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'LGU event saved and announced to 1 organization.');

        $event = LguEvent::sole();
        $this->assertSame('LGU event: Road safety seminar', $event->announcement->title);
        $this->assertStringContainsString('you do not need to log this event as an activity', $event->announcement->body);
        Notification::assertSentTo($toda->user, OfficeNoticeSent::class);
        Notification::assertNotSentTo($other->user, OfficeNoticeSent::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'lgu_event.created']);

        // Upcoming on the announced organization's Activities page only.
        $this->actingAs($toda->user)->get(route('cso.activities.index'))->assertSee('Upcoming LGU events')->assertSee('Road safety seminar');
        $this->actingAs($other->user)->get(route('cso.activities.index'))->assertDontSee('Upcoming LGU events');

        // Attendance waits for the day.
        $this->actingAs($this->admin)->get(route('admin.lgu-events.show', $event))->assertSee('Attendance opens on the event day');
        $this->actingAs($this->admin)->post(route('admin.lgu-events.attendance', $event), ['organizations' => [$toda->id]])->assertStatus(422);
    }

    public function test_recorded_attendance_credits_each_organization_as_lgu_organized_with_no_reach(): void
    {
        [$a, $b] = Organization::factory()->accredited()->count(2)->create();
        $unaccredited = Organization::factory()->create();
        $event = $this->event();

        $this->actingAs($this->admin)->post(route('admin.lgu-events.attendance', $event), ['organizations' => [$a->id, $b->id, $unaccredited->id]])
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'Attendance saved: 2 organizations credited (2 added, 0 removed).');

        $credit = $a->activities()->sole();
        $this->assertSame(
            [$event->id, 'lgu_organized', 'verified', null, 'Municipal road safety seminar', 'Municipal Gymnasium'],
            [$credit->lgu_event_id, $credit->activity_source, $credit->status, $credit->participants_estimate, $credit->title, $credit->venue],
        );
        $this->assertSame(0, $unaccredited->activities()->count());
        $this->assertNotNull($a->performanceScores()->first());
        Notification::assertSentTo($a->user, ActivityVerified::class, fn ($n, $channels) => $channels === ['database']
            && $n->toDatabase($a->user)['headline'] === 'LGU event attendance recorded');

        // It counts as LGU-organized in the analytics, and adds no residents reached.
        $summary = (new ReportData(now()->subMonth(), now()))->summary();
        $this->assertSame([0, 2], [$summary['independent'], $summary['lguOrganized']]);

        // Unticking removes the credit; editing the event updates the rest.
        $this->actingAs($this->admin)->post(route('admin.lgu-events.attendance', $event), ['organizations' => [$a->id]]);
        $this->assertSame(0, $b->activities()->count());
        $this->actingAs($this->admin)->put(route('admin.lgu-events.update', $event), [
            'title' => 'Road safety seminar (rescheduled)', 'description' => 'Same briefing.', 'venue' => 'Civic Center', 'held_on' => now()->subDay()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Road safety seminar (rescheduled)', 'Civic Center', now()->subDay()->toDateString()],
            [$credit->fresh()->title, $credit->fresh()->venue, $credit->fresh()->activity_date->toDateString()]);

        // The CSO sees it in its history as an LGU event, not something to edit.
        $this->actingAs($a->user)->get(route('cso.activities.index'))->assertSee('LGU event')->assertSee('your attendance was recorded by the Desk Office');
        $this->actingAs($a->user)->get(route('cso.activities.edit', $credit))->assertForbidden();

        // Deleting the event removes its credits.
        $this->actingAs($this->admin)->delete(route('admin.lgu-events.destroy', $event))->assertRedirect(route('admin.lgu-events.index'));
        $this->assertSame(0, Activity::count());
    }

    public function test_an_lgu_event_logged_by_a_cso_is_linked_to_the_event_instead(): void
    {
        $organization = Organization::factory()->accredited()->create();
        $event = $this->event();
        $mislogged = Activity::factory()->for($organization)->create(['title' => 'Attended road safety seminar', 'activity_date' => $event->held_on, 'logged_by' => $organization->user_id]);

        $this->actingAs($this->admin)->get(route('admin.activities.index'))->assertSee('This was an LGU event')->assertSee('Municipal road safety seminar');

        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $mislogged), ['lgu_event_id' => $event->id])
            ->assertOk()->assertJsonPath('message', "Credited to {$organization->name} as attending the LGU event “Municipal road safety seminar”.");

        $this->assertNull(Activity::find($mislogged->id));
        $credit = $organization->activities()->sole();
        $this->assertSame([$event->id, 'lgu_organized', 'verified'], [$credit->lgu_event_id, $credit->activity_source, $credit->status]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity.linked_to_lgu_event']);

        // Already credited: linking a second log doesn't credit twice.
        $again = Activity::factory()->for($organization)->create(['activity_date' => $event->held_on]);
        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $again), ['lgu_event_id' => $event->id])->assertOk();
        $this->assertSame(1, $organization->activities()->count());

        // An event not yet held can't take it.
        $later = $this->event(['held_on' => now()->addWeek()->toDateString()]);
        $third = Activity::factory()->for($organization)->create();
        $this->actingAs($this->admin)->postJson(route('admin.activities.link-event', $third), ['lgu_event_id' => $later->id])->assertStatus(422);
    }

    public function test_only_the_office_manages_lgu_events(): void
    {
        $rep = Organization::factory()->accredited()->create()->user;
        $event = $this->event();

        $this->actingAs($rep)->get(route('admin.lgu-events.index'))->assertForbidden();
        $this->actingAs($rep)->post(route('admin.lgu-events.attendance', $event), ['organizations' => [$rep->organization->id]])->assertForbidden();
        $this->actingAs($rep)->post(route('admin.activities.link-event', Activity::factory()->for($rep->organization)->create()), ['lgu_event_id' => $event->id])->assertForbidden();
        $this->assertSame(0, $event->attendance()->count());

        $this->actingAs($this->admin)->post(route('admin.lgu-events.store'), ['title' => '', 'description' => '', 'held_on' => ''])
            ->assertSessionHasErrors(['title', 'description', 'held_on']);
        $this->actingAs($this->admin)->get(route('admin.lgu-events.index'))->assertOk()->assertSee('Municipal road safety seminar');
    }
}
