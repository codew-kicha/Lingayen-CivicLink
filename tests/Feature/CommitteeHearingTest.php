<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\ApplicationHearing;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\CommitteeHearingRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The client's process (Nov 2026): Application, Validation, Committee hearing, Approval. The
 * Sangguniang Bayan holds one committee hearing; the office records its outcome afterwards and the
 * organization is told what happened, with the remarks.
 */
class CommitteeHearingTest extends TestCase
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

    private function application(array $attributes = []): ApplicationModel
    {
        return ApplicationModel::factory()->for($this->rep->organization)->forHearing()->create($attributes);
    }

    public function test_every_status_falls_in_one_of_the_four_phases(): void
    {
        $phase = fn (array $attributes) => ApplicationModel::factory()->make($attributes)->phase();

        $this->assertSame('application', $phase(['status' => 'draft']));
        $this->assertSame('validation', $phase(['status' => 'submitted']));
        $this->assertSame('validation', $phase(['status' => 'requirements_pending']));
        $this->assertSame('validation', $phase(['status' => 'requirements_submitted']));
        $this->assertSame('hearing', $phase(['status' => 'under_review']));
        $this->assertSame('approval', $phase(['status' => 'approved']));
        $this->assertNull($phase(['status' => 'rejected']));
        $this->assertSame('Hearing deferred; to be heard again', ApplicationModel::factory()->make(['status' => 'under_review', 'hearing_outcome' => 'deferred'])->phaseNote());
    }

    public function test_validated_requirements_are_forwarded_to_the_committee_and_the_cso_is_told(): void
    {
        $application = ApplicationModel::factory()->for($this->rep->organization)->create(['status' => 'requirements_submitted']);

        $this->forwardToCommittee($application, $this->admin)->assertSessionHasNoErrors();

        $this->assertSame(['under_review', 'hearing'], [$application->fresh()->status, $application->fresh()->phase()]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.forwarded_to_committee']);
        Notification::assertSentTo($this->rep, ApplicationStatusChanged::class,
            fn ($n) => $n->toDatabase($this->rep)['headline'] === 'Forwarded to the committee for hearing');
        $this->actingAs($this->rep)->get(route('cso.applications.show', $application))->assertOk()
            ->assertSee('Committee hearing')->assertSee('forwarded to the Sangguniang Bayan committee');
    }

    public function test_a_recorded_hearing_tells_the_cso_the_outcome_and_the_remarks(): void
    {
        $application = $this->application();

        $this->recordHearing($application, 'favorable', $this->admin, 'The committee recommends approval; no changes needed.')
            ->assertSessionHasNoErrors();

        $hearing = $application->hearings()->sole();
        $this->assertSame(['favorable', 'Committee on Social Services', $this->admin->id], [$hearing->outcome, $hearing->committee, $hearing->recorded_by]);
        $this->assertTrue($application->fresh()->readyToApprove());
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.hearing_recorded']);

        Notification::assertSentTo($this->rep, CommitteeHearingRecorded::class, function (CommitteeHearingRecorded $n, array $channels) {
            $data = $n->toDatabase($this->rep);
            $mail = $n->toMail($this->rep);

            return $channels === ['database', 'mail']
                && $data['headline'] === 'Committee hearing: Favorable'
                && str_contains($data['body'], 'The Committee on Social Services heard your application on '.now()->format('j F Y'))
                && str_contains($data['body'], 'Remarks: The committee recommends approval; no changes needed.')
                && str_contains($data['body'], 'the Desk Office issues your accreditation')
                && $mail->subject === 'Your accreditation application: Committee hearing: Favorable';
        });

        $this->actingAs($this->rep)->get(route('cso.applications.show', $application))->assertOk()
            ->assertSee('Committee hearings')->assertSee('The committee recommends approval; no changes needed.');
    }

    public function test_deferred_or_returned_keeps_it_at_the_hearing_and_approve_locked(): void
    {
        $application = $this->application();

        foreach (['deferred' => 'Hearing deferred; to be heard again', 'returned' => 'Returned by the committee; compliance needed'] as $outcome => $note) {
            $this->recordHearing($application, $outcome, $this->admin, 'Bring the board resolution naming your representative.');
            $this->assertSame(['under_review', $outcome, $note], [$application->fresh()->status, $application->fresh()->hearing_outcome, $application->fresh()->phaseNote()]);
            $this->actingAs($this->admin)->post(route('admin.applications.approve', $application))->assertSessionHas('error');
        }
        $this->assertSame(0, Accreditation::count());

        $this->recordHearing($application, 'favorable', $this->admin);
        $this->actingAs($this->admin)->post(route('admin.applications.approve', $application))->assertSessionHas('status');
        $this->assertSame(['approved', 'approval'], [$application->fresh()->status, $application->fresh()->phase()]);
        $this->assertSame(['deferred', 'returned', 'favorable'], $application->hearings()->pluck('outcome')->all());
    }

    public function test_a_hearing_needs_its_date_outcome_and_remarks(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin)->post(route('admin.applications.hearing', $application), [])
            ->assertSessionHasErrors(['held_on', 'outcome', 'remarks']);
        $this->recordHearing($application, 'approved-ish', $this->admin)->assertSessionHasErrors('outcome');
        $this->actingAs($this->admin)->post(route('admin.applications.hearing', $application), [
            'held_on' => now()->addDay()->toDateString(), 'outcome' => 'favorable', 'remarks' => 'x',
        ])->assertSessionHasErrors('held_on');

        $this->assertSame(0, ApplicationHearing::count());
        Notification::assertNothingSent();
    }

    public function test_undoing_a_mistake_does_not_notify_again(): void
    {
        $application = $this->application();
        $this->recordHearing($application, 'favorable', $this->admin);
        Notification::assertSentToTimes($this->rep, CommitteeHearingRecorded::class, 1);

        $this->actingAs($this->admin)->delete(route('admin.applications.hearing.undo', $application))->assertSessionHasNoErrors();

        $this->assertSame([null, 0], [$application->fresh()->hearing_outcome, $application->hearings()->count()]);
        Notification::assertSentToTimes($this->rep, CommitteeHearingRecorded::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'application.hearing_undone']);
    }

    public function test_only_the_office_records_hearings_and_remarks_stay_text(): void
    {
        $application = $this->application();
        $this->actingAs($this->rep)->post(route('admin.applications.hearing', $application), [
            'held_on' => now()->toDateString(), 'outcome' => 'favorable', 'remarks' => 'x',
        ])->assertForbidden();
        $this->actingAs($this->rep)->post(route('admin.applications.forward', $application))->assertForbidden();
        $this->assertSame(0, ApplicationHearing::count());

        $this->recordHearing($application, 'favorable', $this->admin, '<script>alert(1)</script>');
        $this->actingAs($this->rep)->get(route('cso.applications.show', $application))->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($this->admin)->get(route('admin.applications.show', $application))->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Record another hearing');
    }

    public function test_the_hearing_arrives_in_the_csos_notifications(): void
    {
        // Delivered for real this time (setUp fakes notifications), so it is stored and listed.
        Notification::swap(new ChannelManager($this->app));
        $application = $this->application();

        $this->recordHearing($application, 'deferred', $this->admin, 'Not reached on the agenda; heard next sitting.');

        $this->assertSame(1, $this->rep->unreadNotifications()->count());
        $this->actingAs($this->rep)->get(route('cso.notifications.index'))->assertOk()
            ->assertSee('Committee hearing: Deferred')->assertSee('Not reached on the agenda; heard next sitting.');
    }
}
