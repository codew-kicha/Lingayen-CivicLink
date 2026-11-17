<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_the_accreditation_and_participation_rates_follow_their_formulas(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00'));
        $report = new ReportData(Carbon::parse('2026-01-01'), Carbon::parse('2026-11-09')->endOfDay());

        // Accreditation rate = approved ÷ decided (approved + rejected) in the period × 100.
        ApplicationModel::factory()->count(2)->approved()->create(['reviewed_at' => '2026-03-01']);
        ApplicationModel::factory()->rejected()->create(['reviewed_at' => '2026-04-01']);
        ApplicationModel::factory()->approved()->create(['reviewed_at' => '2025-06-01']);   // outside the period
        ApplicationModel::factory()->create(['status' => 'under_review']);                // not decided

        $summary = $report->summary();
        $this->assertSame([2, 1, 0.667], [$summary['approvedDecisions'], $summary['rejectedDecisions'], $summary['accreditationRate']]);

        // Participation rate = accredited with ≥ 1 verified activity ÷ accredited.
        $busy = $this->accredited();
        $this->accredited();
        $this->verified($busy, '2026-05-01');
        $summary = (new ReportData(Carbon::parse('2026-01-01'), Carbon::parse('2026-11-09')->endOfDay()))->summary();
        $this->assertSame([1, 0.5], [$summary['activeAccredited'], $summary['participationRate']]);
    }

    public function test_every_chart_says_what_it_shows_in_a_sentence(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 10:00'));
        $organization = $this->accredited(['sector' => 'Youth', 'barangay' => 'Sabangan']);
        foreach (['2026-10-05', '2026-10-15', '2026-09-01'] as $date) {
            $this->verified($organization, $date, ['participants_estimate' => 100]);
        }

        $insights = (new ReportData(Carbon::parse('2025-12-01'), Carbon::parse('2026-11-09')->endOfDay()))->insights();

        $this->assertSame(['volume', 'reach', 'sectors', 'barangays', 'applications', 'at_risk', 'comparison', 'contributors'], array_keys($insights));
        $this->assertStringStartsWith('Busiest month: Oct 2026 with 2', $insights['volume']);
        $this->assertStringContainsString('10 of 12 months had none.', $insights['volume']);
        $this->assertStringStartsWith('Sabangan leads with 3 (100% of all verified activity).', $insights['barangays']);
        $this->assertSame('Most residents reached in Oct 2026: 200, 67% of the period\'s total.', $insights['reach']);
    }

    public function test_each_figure_explains_its_formula_on_the_page_and_in_the_exports(): void
    {
        $admin = User::factory()->admin()->create();
        $html = $this->actingAs($admin)->get(route('admin.analytics'))->assertOk()
            ->assertSee('How these figures are counted')
            ->assertSee('Self-initiated activities ÷ (self-initiated + LGU-organized activities) × 100')
            ->assertSee('Applications approved ÷ applications decided (approved + rejected) in the period × 100')
            ->assertSee('Accreditation rate')
            ->getContent();

        // An ⓘ for every tile figure and every comparison column, each with its own definition.
        foreach (['Accredited now', 'Organizations with verified activity', 'Verified activities', 'Residents reached', 'Self-initiated share', 'Accreditation rate', 'Per organization', 'Average score'] as $figure) {
            $this->assertStringContainsString('How "'.$figure.'" is counted', $html, $figure);
        }
        $this->assertGreaterThanOrEqual(8, substr_count($html, 'What this shows: '));

        // The Excel workbook carries the same definitions.
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $this->actingAs($admin)->get(route('admin.analytics.excel'))->streamedContent());
        $sheet = IOFactory::load($file)->getSheetByName('Definitions');
        $this->assertSame(['Figure', 'Formula', 'Note'], [$sheet->getCell('A1')->getValue(), $sheet->getCell('B1')->getValue(), $sheet->getCell('C1')->getValue()]);
        $this->assertSame(count(config('metrics')) + 1, $sheet->getHighestRow());
        unlink($file);
    }

    public function test_each_part_of_the_split_has_its_own_percentage_adding_up_to_100(): void
    {
        $this->assertSame(['independent' => 83, 'lgu' => 17], ReportData::shares(5, 1));
        $this->assertSame(['independent' => 33, 'lgu' => 67], ReportData::shares(1, 2));   // 33.3 + 66.7 still makes 100
        $this->assertSame(['independent' => 0, 'lgu' => 100], ReportData::shares(0, 4));
        $this->assertSame(['independent' => 0, 'lgu' => 0], ReportData::shares(0, 0));
    }

    public function test_clicking_either_colour_shows_that_parts_percentage(): void
    {
        $organization = $this->accredited(['sector' => 'Health']);
        foreach (range(1, 5) as $i) {
            $this->verified($organization, now()->subDays($i * 3)->toDateString());
        }
        $this->verified($organization, now()->subDays(20)->toDateString(), ['activity_source' => 'lgu_organized']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics'))->assertOk()
            // Each colour of the Health bar is its own button with its own share.
            ->assertSee('aria-label="Health, Self-initiated: 5 of 6 activities, 83%"', false)
            ->assertSee('aria-label="Health, LGU-organized: 1 of 6 activities, 17%"', false)
            // Clicking a part says it in words; the overall split is shown for both parts too.
            ->assertSee('LGU-organized: 1 of 6 activities with a recorded organizer =', false)
            ->assertSee('LGU-organized, accredited organizations in all sectors: 1 of 6 activities with a recorded organizer =', false)
            ->assertSee('Self <span class="font-semibold tabular-nums">83%</span>', false)
            ->assertSee('LGU <span class="font-semibold tabular-nums">17%</span>', false);
    }

    public function test_activity_titles_in_the_month_details_stay_text(): void
    {
        $organization = $this->accredited();
        $this->verified($organization, now()->subDays(3)->toDateString(), ['title' => '<script>alert(1)</script>']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics'))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
