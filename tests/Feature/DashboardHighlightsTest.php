<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Organization;
use App\Models\PerformanceScore;
use App\Models\User;
use Database\Seeders\ExpiredAccreditationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin's top-10 ranking, the woven band and sidebar toggle, and the lapsed-accreditation renewal path. */
class DashboardHighlightsTest extends TestCase
{
    use RefreshDatabase;

    private function score(Organization $organization, float $total, int $yearsAgo = 0): void
    {
        PerformanceScore::create([
            'organization_id' => $organization->id,
            'period_start' => now()->subYears($yearsAgo + 1)->toDateString(),
            'period_end' => now()->subYears($yearsAgo)->toDateString(),
            'activity_frequency' => $total, 'community_reach' => $total,
            'compliance_timeliness' => $total, 'document_currency' => $total,
            'total_score' => $total, 'computed_at' => now(),
        ]);
    }

    public function test_the_admin_dashboard_ranks_accredited_organizations_by_their_latest_score(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Organization::factory()->accredited()->create(['name' => 'Highest Scoring Circle']);
        $second = Organization::factory()->accredited()->create(['name' => 'Runner Up Association']);
        $notAccredited = Organization::factory()->create(['name' => 'Unaccredited Group']);
        $this->score($first, 0.40, yearsAgo: 1);
        $this->score($first, 0.91); // the later period is the one that counts
        $this->score($second, 0.75);
        $this->score($notAccredited, 0.99);

        foreach (range(1, 10) as $n) {
            $this->score(Organization::factory()->accredited()->create(['name' => "Filler {$n}"]), 0.10);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $response->assertSee('Top 10 performing CSOs')
            ->assertSeeInOrder(['Highest Scoring Circle', '91%', 'Runner Up Association', '75%'])
            ->assertDontSee('Unaccredited Group');
        $this->assertCount(10, $response->viewData('topPerformers'));
    }

    public function test_dashboards_carry_the_woven_band_and_the_sidebar_toggle(): void
    {
        $admin = User::factory()->admin()->create();
        $rep = Organization::factory()->accredited()->create()->user;

        foreach ([[$admin, 'admin.dashboard'], [$rep, 'cso.dashboard']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route))->assertOk()
                ->assertSee('class="woven-band"', false)
                ->assertSee('Hide menu')
                ->assertSee('Show menu');
        }

        // Other screens keep the plain ground.
        $this->actingAs($admin)->get(route('admin.applications.index'))->assertDontSee('class="woven-band"', false);
    }

    public function test_a_lapsed_organization_is_pointed_to_a_renewal(): void
    {
        $this->seed(ExpiredAccreditationSeeder::class);
        $rep = User::where('email', 'expired@example.ph')->firstOrFail();
        $accreditation = Accreditation::where('organization_id', $rep->organization->id)->sole();

        $this->assertSame('expired', $accreditation->status);
        $this->assertTrue($accreditation->expires_at->isPast());

        $this->actingAs($rep)->get(route('cso.dashboard'))->assertOk()
            ->assertSee('Expired')
            ->assertSee('File a renewal')
            ->assertDontSee('View application');

        // The Apply dialog starts on Renewal for an organization that was accredited before.
        $this->actingAs($rep)->get(route('cso.applications.index', ['apply' => 1]))->assertOk()
            ->assertSee("type: 'renewal'", false);
    }

    public function test_the_expired_account_seeder_runs_once(): void
    {
        $this->seed(ExpiredAccreditationSeeder::class);
        $this->seed(ExpiredAccreditationSeeder::class);

        $this->assertSame(1, User::where('email', 'expired@example.ph')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'accreditation.expired', 'actor_id' => null]);
    }
}
