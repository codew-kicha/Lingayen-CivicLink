<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The committee hearing comes before approval, and approval waits for a favorable one. */
class ApplicationStagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hearing_recorded_by_mistake_can_be_undone(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->forHearing()->create();

        $this->actingAs($admin)->get(route('admin.applications.show', $application))
            ->assertSee('Record hearing and notify')->assertDontSee('Undo the last hearing record');

        $this->recordHearing($application, 'deferred', $admin);
        $this->recordHearing($application, 'favorable', $admin);
        $this->actingAs($admin)->get(route('admin.applications.show', $application))->assertSee('Undo the last hearing record');

        $this->actingAs($admin)->delete(route('admin.applications.hearing.undo', $application))->assertSessionHasNoErrors();
        $this->assertSame(['deferred', 1], [$application->fresh()->hearing_outcome, $application->hearings()->count()]);
    }

    public function test_approve_stays_locked_until_the_hearing_is_favorable_and_the_email_gives_the_reference_code(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->heard('deferred')->create();

        $this->actingAs($admin)->get(route('admin.applications.show', $application))
            ->assertSee('Opens once the committee hearing is favorable');
        $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertSessionHas('error');
        $this->assertDatabaseCount('accreditations', 0);

        $application->update(['hearing_outcome' => 'favorable']);
        $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertSessionHas('status');

        $code = $application->fresh()->accreditation->verification_code;
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        Notification::assertSentTo($application->organization->user, ApplicationStatusChanged::class,
            fn ($n) => str_contains($n->toMail($application->organization->user)->introLines[0], $code));
    }

    public function test_a_decided_application_takes_no_more_hearings(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->rejected()->create();

        $this->recordHearing($application, admin: $admin)->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.applications.forward', $application))->assertStatus(422);
        $this->assertSame(0, $application->hearings()->count());
    }

    public function test_the_public_footer_names_the_office_not_a_demo(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Municipality of Lingayen, Pangasinan · Civil Society Desk Office')
            ->assertDontSee('Demonstration build');
    }
}
