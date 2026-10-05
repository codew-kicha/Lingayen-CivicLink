<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use App\Models\User;
use App\Services\ReportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/** Milestone 2 Phases 6 and 7: activity source, ReportData analytics, PDF/Excel exports, printables. */
class AdvocacyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function accredited(array $attributes = [], ?string $issuedAt = null): Organization
    {
        $organization = Organization::factory()->create($attributes);
        $application = ApplicationModel::factory()->for($organization)->approved()->create();
        $organization->accreditations()->create([
            'application_id' => $application->id,
            'verification_code' => 'TEST-'.$organization->id,
            'status' => 'active',
            'active_org_marker' => $organization->id,
            'issued_at' => $issuedAt ?? now()->subYear()->toDateString(),
            'expires_at' => now()->addYears(2)->toDateString(),
        ]);

        return $organization;
    }

    private function verified(Organization $organization, string $source, int $daysAgo = 30, int $reach = 50): Activity
    {
        return Activity::factory()->for($organization)->verified()->create([
            'activity_source' => $source,
            'activity_date' => now()->subDays($daysAgo)->toDateString(),
            'participants_estimate' => $reach,
        ]);
    }

    private function report(): ReportData
    {
        return new ReportData(now()->subMonths(11)->startOfMonth(), now()->endOfDay());
    }

    // Capturing the source

    public function test_the_source_is_required_on_the_cso_form_and_in_assisted_encoding(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();
        $payload = ['title' => 'Clean-up', 'description' => 'Shoreline.', 'activity_date' => now()->subDay()->toDateString()];

        $this->actingAs($rep)->post(route('cso.activities.store'), $payload)->assertSessionHasErrors('activity_source');
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.organizations.activities.store', $organization), $payload + ['activity_source' => 'sometimes'])
            ->assertSessionHasErrors('activity_source');

        $this->actingAs($rep)->post(route('cso.activities.store'), $payload + ['activity_source' => 'lgu_organized'])->assertSessionHasNoErrors();
        $this->assertSame('lgu_organized', Activity::firstOrFail()->activity_source);
    }

    public function test_peso_confirms_or_corrects_the_source_at_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $claimed = Activity::factory()->create(['activity_source' => 'independent']);
        $confirmed = Activity::factory()->create(['activity_source' => 'independent']);

        $this->actingAs($admin)->post(route('admin.activities.verify', $claimed))->assertSessionHasErrors('activity_source');
        $this->assertSame('pending', $claimed->fresh()->status);

        $this->actingAs($admin)->post(route('admin.activities.verify', $claimed), ['activity_source' => 'lgu_organized']);
        $this->assertSame('lgu_organized', $claimed->fresh()->activity_source);
        $this->assertSame('verified', $claimed->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity.source_corrected', 'subject_id' => $claimed->id]);

        $this->actingAs($admin)->post(route('admin.activities.verify', $confirmed), ['activity_source' => 'independent']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'activity.source_corrected', 'subject_id' => $confirmed->id]);
    }

    // ReportData

    public function test_the_ratio_needs_a_minimum_sample(): void
    {
        $this->assertNull(ReportData::ratio(2, 0));
        $this->assertSame(0.75, ReportData::ratio(3, 1));
        $this->assertSame(0.0, ReportData::ratio(0, 3));
    }

    public function test_sector_comparison_separates_self_initiated_from_lgu_work_and_credits_partners(): void
    {
        $toda = $this->accredited(['sector' => 'TODA']);
        $this->verified($toda, 'independent');
        foreach (range(1, 4) as $_) {
            $this->verified($toda, 'lgu_organized');
        }

        $fisherfolk = $this->accredited(['sector' => 'Farmers and Fisherfolks']);
        foreach (range(1, 3) as $_) {
            $this->verified($fisherfolk, 'independent');
        }
        // A joint activity credits the TODA group as a partner too.
        $this->verified($fisherfolk, 'independent')->partnerOrganizations()->attach($toda);

        $sectors = $this->report()->sectorComparison()->keyBy('sector');

        $this->assertSame(6, $sectors['TODA']['verified']);
        $this->assertSame(2, $sectors['TODA']['independent']);
        $this->assertEqualsWithDelta(0.333, $sectors['TODA']['ratio'], 0.001);
        $this->assertSame(1.0, $sectors['Farmers and Fisherfolks']['ratio']);
        $this->assertSame(0, $sectors['Health']['accredited']);
        $this->assertNull($sectors['Health']['ratio']);
    }

    public function test_at_risk_lists_each_reason_and_leaves_out_healthy_or_new_organizations(): void
    {
        $silent = $this->accredited(['name' => 'Silent Association']);
        $once = $this->accredited(['name' => 'Once Association']);
        $this->verified($once, 'independent');
        $attendee = $this->accredited(['name' => 'Attendee TODA']);
        foreach (range(1, 4) as $_) {
            $this->verified($attendee, 'lgu_organized');
        }
        $lapsed = $this->accredited(['name' => 'Lapsed Circle']);
        foreach (range(1, 3) as $_) {
            $this->verified($lapsed, 'independent', daysAgo: 250);
        }

        $healthy = $this->accredited(['name' => 'Healthy Cooperative']);
        foreach (range(1, 3) as $_) {
            $this->verified($healthy, 'independent');
        }
        $partnerOnly = $this->accredited(['name' => 'Partner Weavers']);
        foreach (range(1, 2) as $_) {
            $this->verified($healthy, 'independent')->partnerOrganizations()->attach($partnerOnly);
        }
        $this->accredited(['name' => 'New Association'], now()->subMonth()->toDateString());
        Organization::factory()->create(['name' => 'Never Accredited']);

        $atRisk = $this->report()->atRisk()->keyBy('name');

        $this->assertEqualsCanonicalizing(['Silent Association', 'Once Association', 'Attendee TODA', 'Lapsed Circle'], $atRisk->keys()->all());
        $this->assertSame(['No verified activity in this period'], $atRisk['Silent Association']['reasons']);
        $this->assertSame(['Only one verified activity'], $atRisk['Once Association']['reasons']);
        $this->assertSame(['Mostly LGU-organized (0% self-initiated)'], $atRisk['Attendee TODA']['reasons']);
        $this->assertSame(['Nothing verified in 6 months'], $atRisk['Lapsed Circle']['reasons']);
        $this->assertSame('Silent Association', $this->report()->atRisk()->first()['name']);
    }

    public function test_the_period_filter_applies_to_every_figure(): void
    {
        $admin = User::factory()->admin()->create();
        $organization = $this->accredited();
        $this->verified($organization, 'independent', daysAgo: 30, reach: 100);
        $this->verified($organization, 'lgu_organized', daysAgo: 500, reach: 900);

        $this->assertSame(1, $this->report()->summary()['verifiedActivities']);
        $this->assertSame(100, $this->report()->summary()['residentsReached']);

        $wide = new ReportData(now()->subYears(2), now());
        $this->assertSame(2, $wide->summary()['verifiedActivities']);
        $this->assertSame(2, $wide->topContributors()->first()['verified']);

        $this->actingAs($admin)->get(route('admin.analytics', ['from' => now()->subYears(2)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertViewHas('summary', fn ($s) => $s['verifiedActivities'] === 2);
        $this->actingAs($admin)->get(route('admin.analytics', ['from' => now()->toDateString(), 'to' => now()->subYear()->toDateString()]))
            ->assertSessionHasErrors('from');
    }

    public function test_the_awards_ranking_groups_by_sector_and_ranks_by_score(): void
    {
        $low = $this->accredited(['name' => 'Low TODA', 'sector' => 'TODA']);
        $high = $this->accredited(['name' => 'High TODA', 'sector' => 'TODA']);
        $coop = $this->accredited(['name' => 'Only Coop', 'sector' => 'Cooperative']);
        foreach ([[$low, 0.2], [$high, 0.8], [$coop, 0.5]] as [$organization, $score]) {
            $organization->performanceScores()->create([
                'period_start' => now()->subYear(), 'period_end' => now(), 'activity_frequency' => $score,
                'community_reach' => $score, 'compliance_timeliness' => $score, 'document_currency' => $score,
                'total_score' => $score, 'computed_at' => now(),
            ]);
        }

        $ranking = $this->report()->awardsRanking();

        // Sectors follow the office's own order (Cooperative before TODA in config/sectors.php).
        $this->assertSame(['Only Coop', 'High TODA', 'Low TODA'], $ranking->pluck('name')->all());
        $this->assertSame([1, 1, 2], $ranking->pluck('rank')->all());
    }

    // Exports

    public function test_the_excel_workbook_has_a_sheet_per_section(): void
    {
        $organization = $this->accredited(['name' => 'Workbook Weavers']);
        $this->verified($organization, 'independent');

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.analytics.excel'));
        $response->assertOk();

        $workbook = IOFactory::load($response->getFile()->getPathname());
        $this->assertSame(
            ['Summary', 'Sector comparison', 'Monthly trend', 'At-risk organizations', 'Barangays', 'Top contributors', 'Awards ranking'],
            $workbook->getSheetNames(),
        );
        $this->assertSame('Workbook Weavers', $workbook->getSheetByName('Top contributors')->getCell('A2')->getValue());
    }

    public function test_the_awards_export_and_pdf_report_download(): void
    {
        $admin = User::factory()->admin()->create();
        $this->accredited(['name' => 'Ranked Association']);

        $awards = $this->actingAs($admin)->get(route('admin.analytics.awards'));
        $awards->assertOk();
        $sheet = IOFactory::load($awards->getFile()->getPathname())->getActiveSheet();
        $this->assertSame('Sector', $sheet->getCell('A1')->getValue());
        $this->assertSame('Ranked Association', $sheet->getCell('C2')->getValue());

        $pdf = $this->actingAs($admin)->get(route('admin.analytics.export', ['from' => now()->subYear()->toDateString()]));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_reports_and_printables_are_admin_only(): void
    {
        $rep = User::factory()->create();
        $organization = Organization::factory()->for($rep)->create();

        foreach (['admin.analytics', 'admin.analytics.export', 'admin.analytics.excel', 'admin.analytics.awards'] as $route) {
            $this->actingAs($rep)->get(route($route))->assertForbidden();
        }
        $this->actingAs($rep)->get(route('admin.organizations.members', $organization))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.organizations.members', $organization))->assertRedirect(route('login'));
    }

    // Printables and timeline

    public function test_the_member_list_prints_for_admins_and_is_audited(): void
    {
        $organization = Organization::factory()->create();
        $organization->members()->create(['name' => 'Ana Reyes', 'position' => 'President', 'contact_number' => '09170000000']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.organizations.members', $organization));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'organization.members_printed', 'subject_id' => $organization->id]);
    }

    public function test_anyone_can_download_the_blank_paper_form(): void
    {
        $response = $this->get(route('accreditation.form'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->get(route('accreditation'))->assertSee(route('accreditation.form'));
    }

    public function test_the_timeline_names_the_longest_silence(): void
    {
        Carbon::setTestNow('2026-10-15');
        $organization = $this->accredited();
        $this->verified($organization, 'independent', daysAgo: 300);   // Dec 2025
        $this->verified($organization, 'lgu_organized', daysAgo: 10);  // Oct 2026

        $timeline = ReportData::timeline($organization);

        $this->assertCount(24, $timeline['months']);
        $this->assertSame('Jan 2026', $timeline['longestGap']['from']->format('M Y'));
        $this->assertSame('Sep 2026', $timeline['longestGap']['to']->format('M Y'));
        $this->assertSame(9, $timeline['longestGap']['months']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.organizations.edit', $organization))
            ->assertOk()->assertSee('No verified activity from Jan 2026')->assertSee('(9 months)', false);
    }
}
