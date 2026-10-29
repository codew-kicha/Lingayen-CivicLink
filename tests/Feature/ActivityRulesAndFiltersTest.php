<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Only accredited organizations log activities; the Verify Activities list sorts and filters by
 * date; the audit log does not display IP addresses.
 */
class ActivityRulesAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Coastal clean-up',
            'description' => 'Members cleared the shoreline.',
            'activity_date' => now()->subDays(3)->toDateString(),
            'activity_source' => 'independent',
        ];
    }

    public function test_an_organization_without_an_active_accreditation_cannot_log_activities(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();

        $this->actingAs($rep)->get(route('cso.activities.index'))->assertOk()
            ->assertSee('Activities are logged once you are accredited')
            ->assertDontSee("dialog('log-activity'", false);
        $this->actingAs($rep)->post(route('cso.activities.store'), $this->payload())->assertForbidden();

        // An accreditation that has ended counts the same as none.
        $accredited = Organization::factory()->for($other = User::factory()->create())->accredited()->create();
        $accredited->accreditations()->update(['status' => 'expired', 'active_org_marker' => null]);
        $this->actingAs($other)->post(route('cso.activities.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseCount('activities', 0);
        $this->assertFalse($organization->isAccredited());
    }

    public function test_an_accredited_organization_logs_and_may_tag_only_accredited_partners(): void
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->accredited()->create();
        $accreditedPartner = Organization::factory()->accredited()->create(['name' => 'Accredited Partner']);
        $unaccredited = Organization::factory()->create(['name' => 'Not Yet Accredited']);

        $this->actingAs($rep)->get(route('cso.activities.index'))->assertOk()
            ->assertSee("dialog('log-activity'", false)
            ->assertSee('Accredited Partner')->assertDontSee('Not Yet Accredited');

        $this->actingAs($rep)->post(route('cso.activities.store'), $this->payload(['partners' => [$unaccredited->id]]))
            ->assertSessionHasErrors(['partners.0' => 'Only accredited organizations can be tagged as partners.']);
        $this->actingAs($rep)->post(route('cso.activities.store'), $this->payload(['partners' => [$accreditedPartner->id]]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_office_staff_cannot_encode_activities_for_an_unaccredited_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create(['user_id' => null]);

        $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))
            ->assertSee('title="Only accredited organizations can log activities"', false);
        $this->actingAs($admin)->post(route('admin.organizations.activities.store', $organization), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_office_staff_log_an_activity_from_a_dialog_that_reopens_after_an_error(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->accredited()->create(['user_id' => null]);
        $page = route('admin.organizations.edit', $organization);

        $this->actingAs($admin)->from($page)
            ->post(route('admin.organizations.activities.store', $organization), $this->payload(['title' => '', '_dialog' => 'log-activity']))
            ->assertRedirect($page)->assertSessionHasErrors('title');
        $this->actingAs($admin)->withSession(['_old_input' => ['_dialog' => 'log-activity'], 'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['title' => 'Required']))])
            ->get($page)->assertSee("dialog('log-activity', true)", false)->assertSee('Attach the sign-in sheet again');

        $this->actingAs($admin)->from($page)
            ->post(route('admin.organizations.activities.store', $organization), $this->payload(['_dialog' => 'log-activity']))
            ->assertRedirect(route('admin.activities.index'));
        $this->assertDatabaseHas('activities', ['organization_id' => $organization->id, 'status' => 'pending']);
    }

    public function test_verify_activities_sorts_and_filters_by_the_date_held(): void
    {
        $admin = User::factory()->admin()->create();
        Activity::factory()->create(['title' => 'Held in January', 'activity_date' => '2026-01-15', 'created_at' => now()->subDays(1)]);
        Activity::factory()->create(['title' => 'Held in May', 'activity_date' => '2026-05-10', 'created_at' => now()->subDays(9)]);
        Activity::factory()->create(['title' => 'Held in September', 'activity_date' => '2026-09-01', 'created_at' => now()->subDays(5)]);
        // Titles in on-page order (each appears once as rendered text, >Title<).
        $positions = function (array $query) use ($admin) {
            $html = $this->actingAs($admin)->get(route('admin.activities.index', $query))->assertOk()->getContent();
            $found = [];
            foreach (['Held in January', 'Held in May', 'Held in September'] as $title) {
                if (($pos = strpos($html, '>'.$title.'<')) !== false) {
                    $found[$title] = $pos;
                }
            }
            asort($found);

            return array_keys($found);
        };

        // Default: the queue waiting longest first (logged 9, 5, 1 days ago).
        $this->assertSame(['Held in May', 'Held in September', 'Held in January'], $positions([]));
        $this->assertSame(['Held in September', 'Held in May', 'Held in January'], $positions(['sort' => 'held_new']));
        $this->assertSame(['Held in January', 'Held in May', 'Held in September'], $positions(['sort' => 'held_old']));
        $this->assertSame(['Held in January', 'Held in September', 'Held in May'], $positions(['sort' => 'logged']));

        // Held between April and August: only May.
        $this->assertSame(['Held in May'], $positions(['from' => '2026-04-01', 'to' => '2026-08-31']));
        // A nonsense date is ignored rather than breaking the page.
        $this->assertCount(3, $positions(['from' => 'not-a-date']));
    }

    public function test_each_rate_limit_keeps_its_own_count(): void
    {
        // Ten form downloads from one network (e.g. an office) must not use up the contact form.
        foreach (range(1, 10) as $_) {
            $this->get(route('accreditation.form', 'new'))->assertOk();
        }
        $message = ['name' => 'Juan', 'email' => 'juan@example.ph', 'message' => 'How do we renew our accreditation?'];
        $this->post(route('contact.send'), $message)->assertSessionHas('status');

        // The contact form's own limit (5 a minute) still applies.
        foreach (range(2, 5) as $_) {
            $this->post(route('contact.send'), $message);
        }
        $this->post(route('contact.send'), $message)->assertStatus(429);

        // ...and that doesn't block registering an organization from the same network.
        $this->get(route('register'))->assertOk();
        $this->post(route('register'), ['organization_name' => ''])->assertSessionHasErrors('organization_name');
    }

    public function test_the_audit_log_does_not_display_ip_addresses(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'login', 'ip_address' => '203.0.113.77']);

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()
            ->assertSee('login')->assertDontSee('203.0.113.77')->assertDontSee('IP address');
    }
}
