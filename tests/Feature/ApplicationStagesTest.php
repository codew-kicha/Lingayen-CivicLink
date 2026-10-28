<?php

namespace Tests\Feature;

use App\Models\ApplicationModel;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Sangguniang Bayan readings are recorded in order, and approval waits for the endorsement. */
class ApplicationStagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stage_can_be_stepped_back_once_to_correct_a_mistake(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->create(['status' => 'under_review', 'sb_stage' => 'second_reading']);

        $this->actingAs($admin)->get(route('admin.applications.show', $application))
            ->assertSee('Record: Third reading')->assertSee('Back to first reading');

        $this->actingAs($admin)->patch(route('admin.applications.stage', $application), ['sb_stage' => 'first_reading'])->assertSessionHasNoErrors();
        $this->assertSame('first_reading', $application->fresh()->sb_stage);

        $this->actingAs($admin)->patch(route('admin.applications.stage', $application), ['sb_stage' => 'endorsed'])->assertSessionHasErrors('sb_stage');
        $this->assertSame('first_reading', $application->fresh()->sb_stage);
    }

    public function test_approve_stays_locked_until_endorsed_and_the_email_gives_the_reference_code(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->create(['status' => 'under_review', 'sb_stage' => 'third_reading']);

        $this->actingAs($admin)->get(route('admin.applications.show', $application))
            ->assertSee('Opens once the Sangguniang Bayan has endorsed the application');
        $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertSessionHas('error');
        $this->assertDatabaseCount('accreditations', 0);

        $application->update(['sb_stage' => 'endorsed']);
        $this->actingAs($admin)->post(route('admin.applications.approve', $application))->assertSessionHas('status');

        $code = $application->fresh()->accreditation->verification_code;
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        Notification::assertSentTo($application->organization->user, ApplicationStatusChanged::class,
            fn ($n) => str_contains($n->toMail($application->organization->user)->introLines[0], $code));
    }

    public function test_a_decided_application_keeps_its_stage(): void
    {
        $admin = User::factory()->admin()->create();
        $application = ApplicationModel::factory()->rejected()->create(['sb_stage' => 'first_reading']);

        $this->actingAs($admin)->patch(route('admin.applications.stage', $application), ['sb_stage' => 'second_reading'])->assertStatus(422);
        $this->assertSame('first_reading', $application->fresh()->sb_stage);
    }

    public function test_the_public_footer_names_the_office_not_a_demo(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Municipality of Lingayen, Pangasinan · Civil Society Desk Office')
            ->assertDontSee('Demonstration build');
    }
}
