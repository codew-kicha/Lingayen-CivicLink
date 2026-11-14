<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Analytics & Reports after the pre-final defense: each figure is compared with the period before,
 * says what it counts, and the charts open to the records behind them.
 */
class AnalyticsDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function accredited(array $attributes = []): Organization
    {
        $organization = Organization::factory()->create($attributes);
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id, 'verification_code' => 'DET-'.$organization->id, 'status' => 'active',
            'active_org_marker' => $organization->id, 'issued_at' => now()->subYears(2), 'expires_at' => now()->addYear(),
        ]);

        return $organization;
    }

    private function verified(Organization $organization, string $date, array $attributes = []): Activity
    {
        return Activity::factory()->for($organization)->verified()->create([
            'activity_source' => 'independent', 'activity_date' => $date, 'participants_estimate' => 40, ...$attributes,
        ]);
    }

    public function test_figures_are_compared_with_the_same_dates_a_year_earlier(): void
    {
        $march = (new ReportData(Carbon::parse('2026-03-01')->startOfDay(), Carbon::parse('2026-03-31')->endOfDay()))->previous();
        $this->assertSame(['2025-03-01', '2025-03-31'], [$march->from->toDateString(), $march->to->toDateString()]);

        // A whole leap year is still one year; two years go back two so the periods never overlap.
        $leap = new ReportData(Carbon::parse('2028-01-01'), Carbon::parse('2028-12-31')->endOfDay());
        $this->assertSame([1, '2027-01-01'], [$leap->yearsBack(), $leap->previous()->from->toDateString()]);
        $two = (new ReportData(Carbon::parse('2024-06-01'), Carbon::parse('2026-05-31')->endOfDay()))->previous();
        $this->assertSame(['2022-06-01', '2024-05-31'], [$two->from->toDateString(), $two->to->toDateString()]);
    }

    public function test_the_summary_counts_active_organizations_and_unrecorded_organizers(): void
    {
        $busy = $this->accredited();
        $this->accredited();                                    // accredited, nothing this period
        $this->verified($busy, '2026-03-10');
        $this->verified($busy, '2026-03-12', ['activity_source' => null]);

        $summary = (new ReportData(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31')->endOfDay()))->summary();

        $this->assertSame(1, $summary['activeOrganizations']);
        $this->assertSame(1, $summary['unknownSource']);
        $this->assertSame(2, $summary['verifiedActivities']);
    }

    public function test_months_carry_residents_reached_and_their_largest_activities(): void
    {
        $organization = $this->accredited();
        $this->verified($organization, '2026-03-10', ['title' => 'Coastal clean-up', 'participants_estimate' => 120]);
        $this->verified($organization, '2026-03-20', ['title' => 'Feeding program', 'participants_estimate' => 30]);

        $report = new ReportData(Carbon::parse('2026-03-01'), Carbon::parse('2026-04-30')->endOfDay());

        $march = $report->activityTrend()->firstWhere('key', '2026-03');
        $this->assertSame([2, 150, 0], [$march['total'], $march['reach'], $march['unknown']]);
        $this->assertSame(['Coastal clean-up', 'Feeding program'], $report->activitiesByMonth()['2026-03']['items']->pluck('title')->all());
        $this->assertArrayNotHasKey('2026-04', $report->activitiesByMonth());
    }

    public function test_the_page_shows_the_change_from_the_previous_period_and_the_details(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00'));
        $organization = $this->accredited(['name' => 'Sabangan Youth Council', 'sector' => 'Youth']);
        foreach (['2026-10-05', '2026-10-15', '2026-09-01'] as $date) {
            $this->verified($organization, $date, ['title' => 'Mangrove planting']);
        }
        $this->verified($organization, '2025-05-01');              // the twelve months before

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('+2</span> vs 1 a year earlier', false)            // 3 verified now, 1 before
            ->assertDontSee('+2 (+200%)')                                     // no percentage on a base of 1
            ->assertSee('+80 (+200%)')                                        // residents: 120 against 40
            ->assertSee('Organizations with verified activity')
            ->assertSee('Mangrove planting')                      // the October drill-down
            ->assertSee('Sabangan Youth Council')
            ->assertSee('read it as attendance, not as distinct people');
    }

    public function test_activity_titles_in_the_month_details_stay_text(): void
    {
        $organization = $this->accredited();
        $this->verified($organization, now()->subDays(3)->toDateString(), ['title' => '<script>alert(1)</script>']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics'))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
