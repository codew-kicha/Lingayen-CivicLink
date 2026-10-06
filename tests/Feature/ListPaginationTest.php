<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every paginated list behaves the same way (docs/DESIGN_SYSTEM.md §4): filters survive page
 * changes, per_page is whitelisted, out-of-range pages redirect, and filtering returns to page 1.
 */
class ListPaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * route, role, list default, filters that match the seeded rows, seeder(User $viewer, int $rows).
     *
     * @return array<string, array{string, string, int, array<string, string>, \Closure}>
     */
    public static function lists(): array
    {
        return [
            'audit log' => ['admin.audit.index', 'admin', 50, ['action' => 'login'], function (User $viewer, int $rows) {
                foreach (range(1, $rows) as $i) {
                    AuditLog::create(['actor_id' => $viewer->id, 'action' => 'login', 'subject_type' => 'User', 'subject_id' => $viewer->id]);
                }
            }],
            'applications queue' => ['admin.applications.index', 'admin', 25, ['status' => 'submitted'], function (User $viewer, int $rows) {
                ApplicationModel::factory()->count($rows)->create(['status' => 'submitted']);
            }],
            'organizations' => ['admin.organizations.index', 'admin', 25, ['q' => 'Coop'], function (User $viewer, int $rows) {
                foreach (range(1, $rows) as $i) {
                    Organization::factory()->create(['name' => sprintf('Coop %03d', $i)]);
                }
            }],
            'verify activities' =>['admin.activities.index', 'admin', 10, ['status' => 'verified'], function (User $viewer, int $rows) {
                Activity::factory()->count($rows)->verified()->create();
            }],
        ];
    }

    private function viewer(string $role): User
    {
        if ($role === 'admin') {
            return User::factory()->admin()->create();
        }

        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        return $rep;
    }

    #[DataProvider('lists')]
    public function test_page_links_keep_the_filters(string $route, string $role, int $default, array $filters, \Closure $seed): void
    {
        $viewer = $this->viewer($role);
        $seed($viewer, $default + 1);

        $html = $this->actingAs($viewer)->get(route($route, $filters))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Pagination"', $html);
        $this->assertStringContainsString(http_build_query($filters, '', '&amp;').'&amp;page=2#page-title', $html);
        $this->assertStringNotContainsString('per_page='.$default, $html);
    }

    #[DataProvider('lists')]
    public function test_per_page_is_whitelisted_and_out_of_range_pages_redirect(string $route, string $role, int $default, array $filters, \Closure $seed): void
    {
        $viewer = $this->viewer($role);
        $other = $default === 10 ? 25 : 10;     // a valid size that isn't this list's default
        $rows = max($default, $other) + 1;
        $seed($viewer, $rows);

        $this->actingAs($viewer)->get(route($route, $filters + ['per_page' => 7]))
            ->assertOk()->assertSee('Showing 1-'.$default.' of ', false);
        $this->actingAs($viewer)->get(route($route, $filters + ['per_page' => $other]))
            ->assertOk()->assertSee('Showing 1-'.$other.' of ', false)->assertSee('per_page='.$other.'&amp;page=2', false);

        $this->actingAs($viewer)->get(route($route, $filters + ['page' => 99]))
            ->assertRedirect(route($route, $filters + ['page' => (int) ceil($rows / $default)]));
    }

    #[DataProvider('lists')]
    public function test_changing_a_filter_returns_to_page_one_and_keeps_the_page_size(string $route, string $role, int $default, array $filters, \Closure $seed): void
    {
        $viewer = $this->viewer($role);
        $seed($viewer, 3);

        $html = $this->actingAs($viewer)->get(route($route, $filters + ['page' => 1, 'per_page' => 25]))->assertOk()->getContent();

        // Every GET form on the page (filters, page size) drops `page`.
        preg_match_all('/<form method="GET".*?<\/form>/s', $html, $forms);
        $this->assertNotEmpty($forms[0]);
        foreach ($forms[0] as $form) {
            $this->assertStringNotContainsString('name="page"', $form);
        }
        // The filter control keeps the page size: a hidden field in a filter form, or tab links.
        $this->assertMatchesRegularExpression('/name="per_page" value="25"|href="[^"]*per_page=25[^"]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*[?&]page=1\b/', $html);
    }

    #[DataProvider('lists')]
    public function test_empty_lists_say_whether_the_filters_or_the_data_are_empty(string $route, string $role, int $default, array $filters, \Closure $seed): void
    {
        $viewer = $this->viewer($role);

        $this->actingAs($viewer)->get(route($route, $filters))->assertOk()
            ->assertSee('No results for these filters')->assertSee('Clear filters');
        $this->actingAs($viewer)->get(route($route))->assertOk()
            ->assertDontSee('No results for these filters');
    }

    public function test_the_at_risk_filter_matches_the_analytics_definition(): void
    {
        $admin = User::factory()->admin()->create();
        $quiet = Organization::factory()->create(['name' => 'Quiet Association']);
        $busy = Organization::factory()->create(['name' => 'Busy Cooperative']);
        foreach ([$quiet, $busy] as $organization) {
            $application = ApplicationModel::factory()->for($organization)->approved()->create();
            $organization->accreditations()->create([
                'application_id' => $application->id, 'verification_code' => 'RISK-'.$organization->id, 'status' => 'active',
                'active_org_marker' => $organization->id, 'issued_at' => now()->subYear(), 'expires_at' => now()->addYear(),
            ]);
        }
        Activity::factory()->count(3)->for($busy)->verified()->create(['activity_source' => 'independent', 'activity_date' => now()->subMonth()]);

        $expected = \App\Services\ReportData::fromRequest(request())->atRisk()->pluck('name')->all();
        $this->assertSame(['Quiet Association'], $expected);

        $this->actingAs($admin)->get(route('admin.organizations.index', ['status' => 'at-risk']))
            ->assertOk()->assertSee('Quiet Association')->assertDontSee('Busy Cooperative');
    }

    public function test_the_audit_log_can_show_one_organizations_history(): void
    {
        $admin = User::factory()->admin()->create();
        $mine = Organization::factory()->create(['name' => 'Pangapisan Fisherfolk Association']);
        $other = Organization::factory()->create();
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'organization.updated', 'subject_type' => 'Organization', 'subject_id' => $mine->id]);
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'organization.hidden', 'subject_type' => 'Organization', 'subject_id' => $other->id]);

        $this->actingAs($admin)->get(route('admin.audit.index', ['organization' => $mine->id]))
            ->assertOk()->assertSee('Full history for')->assertSee('Pangapisan Fisherfolk Association')
            ->assertSee('organization.updated')->assertDontSee('organization.hidden');
    }
}
