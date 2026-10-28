<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccreditationExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Expiry without a scheduler, the privacy notice and consent, and updates that get read. */
class DefenseGapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accreditation_past_its_expiry_date_lapses_on_the_next_visit(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->accredited()->create(['name' => 'Lapsed Weavers']);
        $organization->accreditations()->update(['expires_at' => now()->subDay()->toDateString()]);

        $this->get(route('directory'))->assertOk()->assertDontSee('Lapsed Weavers');

        $accreditation = Accreditation::firstOrFail();
        $this->assertSame('expired', $accreditation->status);
        $this->assertNull($accreditation->active_org_marker);
        $this->assertDatabaseHas('audit_logs', ['action' => 'accreditation.expired', 'subject_id' => $organization->id, 'actor_id' => null]);

        // ...so it can no longer log activities until it renews.
        $this->actingAs($rep)->get(route('cso.activities.index'))->assertSee('Activities are logged once you are accredited');
    }

    public function test_the_expiry_check_runs_once_a_day_and_leaves_current_accreditations_alone(): void
    {
        $current = Organization::factory()->accredited()->create(['name' => 'Current Farmers']);
        $this->get(route('directory'))->assertSee('Current Farmers');

        // Already checked today: a lapse right now waits for tomorrow's first visit.
        $current->accreditations()->update(['expires_at' => now()->subDay()->toDateString()]);
        $this->get(route('directory'))->assertSee('Current Farmers');

        Cache::flush();
        $this->artisan('accreditations:expire')->expectsOutputToContain('Expired 1')->assertExitCode(0);
        $this->assertSame('expired', $current->accreditations()->value('status'));
    }

    public function test_registration_needs_consent_to_the_privacy_notice(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('Data Privacy Act')->assertSee('Member names and contact details are never shown publicly');
        $this->get(route('home'))->assertSee(route('privacy'), false);
        $this->get(route('register'))->assertSee('name="privacy_consent"', false);

        $details = [
            'organization_name' => 'Malimpuec Youth Alliance', 'barangay' => 'Malimpuec', 'sector' => config('sectors')[0],
            'name' => 'Carla Mendoza', 'email' => 'carla@example.ph', 'phone' => '0917 845 2210', 'password' => 'Malimpuec-Youth-2026!',
        ];
        $this->post(route('register'), $details)->assertSessionHasErrors(['privacy_consent' => 'Agree to the privacy notice to register.']);
        $this->assertDatabaseMissing('users', ['email' => 'carla@example.ph']);

        $this->post(route('register'), $details + ['privacy_consent' => '1'])->assertRedirect(route('verification.notice'));
        $this->assertNotNull(User::where('email', 'carla@example.ph')->value('privacy_consented_at'));
    }

    public function test_dashboard_updates_are_new_once_then_read(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->accredited()->create();
        $rep->notify(new AccreditationExpiring($organization->accreditations()->first()));

        $this->actingAs($rep)->get(route('cso.dashboard'))->assertSee('Accreditation expiring')->assertSee('>New<', false);
        $this->assertSame(0, $rep->unreadNotifications()->count());
        $this->actingAs($rep)->get(route('cso.dashboard'))->assertSee('Accreditation expiring')->assertDontSee('>New<', false);
    }
}
