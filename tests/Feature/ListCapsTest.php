<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lists that are capped rather than paginated (docs/DESIGN_SYSTEM.md §4). */
class ListCapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_show_five_rows_and_name_the_total(): void
    {
        ApplicationModel::factory()->count(7)->create(['status' => 'submitted', 'submitted_at' => now()]);
        Activity::factory()->count(6)->create();

        $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('View all 7')->assertSee('View all 6')->getContent();

        $this->assertSame(10, substr_count($html, '<tr class="table-row">'), 'Expected 5 rows in each of the two widgets.');
    }

    public function test_organization_history_shows_five_entries_and_links_the_full_log(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = Organization::factory()->create();
        foreach (range(1, 7) as $i) {
            AuditLog::create(['actor_id' => $admin->id, 'action' => "organization.step{$i}", 'subject_type' => 'Organization', 'subject_id' => $organization->id]);
        }

        $html = $this->actingAs($admin)->get(route('admin.organizations.edit', $organization))->assertOk()
            ->assertSee(route('admin.audit.index', ['organization' => $organization->id]))->assertSee('Full history')
            ->getContent();

        $this->assertSame(5, preg_match_all('/Organization step\d/', $html));
    }

    public function test_analytics_shows_ten_at_risk_with_the_total_and_both_links(): void
    {
        foreach (range(1, 12) as $i) {
            $organization = Organization::factory()->create(['name' => sprintf('Quiet %02d', $i)]);
            $application = ApplicationModel::factory()->for($organization)->approved()->create();
            $organization->accreditations()->create([
                'application_id' => $application->id, 'verification_code' => "CAP-$i", 'status' => 'active',
                'active_org_marker' => $organization->id, 'issued_at' => now()->subYear(), 'expires_at' => now()->addYear(),
            ]);
        }

        $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics'))->assertOk()
            ->assertSee('Showing 10 of 12 at-risk')
            ->assertSee(route('admin.organizations.index', ['status' => 'at-risk']), false)
            ->assertSee(route('admin.analytics.excel'), false)
            ->getContent();

        $this->assertSame(10, preg_match_all('/>Quiet \d\d</', $html));
    }

    public function test_cso_partner_tags_show_the_latest_ten_of_the_total(): void
    {
        $rep = User::factory()->create();
        $mine = Organization::factory()->for($rep)->create();
        Activity::factory()->count(11)->create()->each(fn ($activity) => $activity->partnerOrganizations()->attach($mine));

        $this->actingAs($rep)->get(route('cso.activities.index'))->assertOk()
            ->assertSee('Showing the latest 10 of 11.');
    }
}
