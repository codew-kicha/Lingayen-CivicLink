<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\ApplicationReading;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\SbReadingRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pre-final defense panel: after each Sangguniang Bayan reading (first to third) the CSO is
 * automatically told what happened: which reading, the outcome, and the office's remarks.
 */
class ReadingFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->rep = User::factory()->create();
        Organization::factory()->for($this->rep)->create();
        $this->admin = User::factory()->admin()->create();
    }

    private function application(string $stage = 'not_endorsed'): ApplicationModel
    {
        return ApplicationModel::factory()->for($this->rep->organization)
            ->create(['status' => $stage === 'not_endorsed' ? 'requirements_submitted' : 'under_review', 'sb_stage' => $stage]);
    }

    public function test_a_finished_reading_notifies_the_cso_with_the_reading_the_outcome_and_the_remarks(): void
    {
        $application = $this->application();

        $this->recordReading($application, 'first_reading', 'passed', $this->admin, 'The council asked about your membership size; no changes needed.')
            ->assertSessionHasNoErrors();

        $reading = $application->readings()->sole();
        $this->assertSame(['first_reading', 'passed', $this->admin->id], [$reading->stage, $reading->outcome, $reading->recorded_by]);
        $this->assertSame(['under_review', 'first_reading'], [$application->fresh()->status, $application->fresh()->sb_stage]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.reading_recorded']);

        Notification::assertSentTo($this->rep, SbReadingRecorded::class, function (SbReadingRecorded $n, array $channels) {
            $data = $n->toDatabase($this->rep);
            $mail = $n->toMail($this->rep);

            return $channels === ['database', 'mail']
                && $data['headline'] === 'First reading: Passed'
                && str_contains($data['body'], 'held the first reading of your application on '.now()->format('j F Y'))
                && str_contains($data['body'], 'Remarks: The council asked about your membership size; no changes needed.')
                && str_contains($data['body'], 'Next: the second reading.')
                && $mail->subject === 'Your accreditation application: First reading: Passed'
                && in_array('Remarks from the Desk Office: The council asked about your membership size; no changes needed.', $mail->introLines, true);
        });

        // The CSO sees it on the application, with the remarks.
        $this->actingAs($this->rep)->get(route('cso.applications.show', $application))->assertOk()
            ->assertSee('Readings so far', false)->assertSee('The council asked about your membership size');
    }

    public function test_the_reading_arrives_in_the_csos_notifications_and_badge(): void
    {
        // Delivered for real this time (setUp fakes notifications), so it is stored and listed.
        Notification::swap(new ChannelManager($this->app));
        $application = $this->application();

        $this->recordReading($application, 'first_reading', 'passed', $this->admin, 'The council asked about your membership size.');

        $this->assertSame(1, $this->rep->unreadNotifications()->count());
        $this->actingAs($this->rep)->get(route('cso.notifications.index'))->assertOk()
            ->assertSee('First reading: Passed')->assertSee('The council asked about your membership size.')->assertSee('Next: the second reading.');
    }

    public function test_a_deferred_or_returned_reading_keeps_the_stage_and_says_what_happens_next(): void
    {
        $application = $this->application('first_reading');

        $this->recordReading($application, 'second_reading', 'deferred', $this->admin, 'Not reached on the agenda; moved to the next session.');
        $this->assertSame('first_reading', $application->fresh()->sb_stage);
        Notification::assertSentTo($this->rep, SbReadingRecorded::class, fn ($n) => $n->toDatabase($this->rep)['headline'] === 'Second reading: Deferred'
            && str_contains($n->toDatabase($this->rep)['body'], 'will take it up again at a later session'));

        $this->recordReading($application, 'second_reading', 'returned', $this->admin, 'Attach the board resolution naming your representative.');
        $this->assertSame('first_reading', $application->fresh()->sb_stage);
        Notification::assertSentTo($this->rep, SbReadingRecorded::class, fn ($n) => $n->toDatabase($this->rep)['headline'] === 'Second reading: Returned for changes');

        // Taken up again and passed: now it moves on. The history keeps all three.
        $this->recordReading($application, 'second_reading', 'passed', $this->admin);
        $this->assertSame('second_reading', $application->fresh()->sb_stage);
        $this->assertSame(['passed', 'deferred', 'returned', 'passed'], ['passed', ...$application->readings()->pluck('outcome')->all()]);
    }

    public function test_each_reading_needs_its_date_outcome_and_remarks_but_the_endorsement_only_a_date(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin)->patch(route('admin.applications.stage', $application), ['sb_stage' => 'first_reading'])
            ->assertSessionHasErrors(['outcome', 'remarks', 'held_on']);
        $this->recordReading($application, 'first_reading', 'approved-ish', $this->admin)->assertSessionHasErrors('outcome');
        $this->actingAs($this->admin)->patch(route('admin.applications.stage', $application), [
            'sb_stage' => 'first_reading', 'outcome' => 'passed', 'remarks' => 'x', 'held_on' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('held_on');
        $this->assertSame(0, ApplicationReading::count());
        Notification::assertNothingSent();

        $endorsable = $this->application('third_reading');
        $this->actingAs($this->admin)->patch(route('admin.applications.stage', $endorsable), ['sb_stage' => 'endorsed', 'held_on' => now()->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame('endorsed', $endorsable->fresh()->sb_stage);
        Notification::assertSentTo($this->rep, SbReadingRecorded::class, fn ($n) => $n->toDatabase($this->rep)['headline'] === 'Endorsed by the Sangguniang Bayan');
    }

    public function test_undoing_a_mistake_removes_the_record_without_notifying_again(): void
    {
        $application = $this->application();
        $this->recordReading($application, 'first_reading', 'passed', $this->admin);
        Notification::assertSentToTimes($this->rep, SbReadingRecorded::class, 1);

        $this->actingAs($this->admin)->patch(route('admin.applications.stage', $application), ['sb_stage' => 'not_endorsed'])->assertSessionHasNoErrors();

        $this->assertSame('not_endorsed', $application->fresh()->sb_stage);
        $this->assertSame(0, $application->readings()->count());
        Notification::assertSentToTimes($this->rep, SbReadingRecorded::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.reading_undone']);
    }

    public function test_only_the_office_records_readings_and_remarks_stay_text(): void
    {
        $application = $this->application();
        $this->actingAs($this->rep)->patch(route('admin.applications.stage', $application), [
            'sb_stage' => 'first_reading', 'outcome' => 'passed', 'remarks' => 'x', 'held_on' => now()->toDateString(),
        ])->assertForbidden();
        $this->assertSame(0, ApplicationReading::count());

        $this->recordReading($application, 'first_reading', 'passed', $this->admin, '<script>alert(1)</script>');
        $this->actingAs($this->rep)->get(route('cso.applications.show', $application))->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($this->admin)->get(route('admin.applications.show', $application))->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Record second reading and notify');
    }
}
