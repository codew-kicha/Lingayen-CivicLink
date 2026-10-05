<?php

namespace App\Exports;

use App\Services\ReportData;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** The analytics report as a workbook, one sheet per section, from the same ReportData as the page. */
class AnalyticsWorkbook implements WithMultipleSheets
{
    public function __construct(private ReportData $report) {}

    public function sheets(): array
    {
        return [
            $this->summary(),
            $this->sectors(),
            $this->trend(),
            $this->atRisk(),
            $this->barangays(),
            $this->contributors(),
            self::awards($this->report),
        ];
    }

    /** Also downloaded on its own for the Outstanding CSO recognition. */
    public static function awards(ReportData $report): ReportSheet
    {
        return new ReportSheet('Awards ranking', [
            'Sector', 'Rank in sector', 'Organization', 'Barangay', 'Performance score',
            'Activity frequency', 'Community reach', 'Compliance timeliness', 'Document currency',
            'Verified activities', 'Residents reached', 'Self-initiated', 'LGU-organized', 'Self-initiated share',
            'Accredited until',
        ], $report->awardsRanking()->map(fn ($row) => [
            $row['sector'], $row['rank'], $row['name'], $row['barangay'], $row['score'],
            $row['components']['activity_frequency'] ?? null, $row['components']['community_reach'] ?? null,
            $row['components']['compliance_timeliness'] ?? null, $row['components']['document_currency'] ?? null,
            $row['verified'], $row['reach'], $row['independent'], $row['lgu'], $row['ratio'],
            $row['accreditedUntil']?->toDateString(),
        ])->all(), ['E' => ReportSheet::DECIMAL, 'F' => ReportSheet::DECIMAL, 'G' => ReportSheet::DECIMAL,
            'H' => ReportSheet::DECIMAL, 'I' => ReportSheet::DECIMAL, 'N' => ReportSheet::PERCENT]);
    }

    private function summary(): ReportSheet
    {
        $s = $this->report->summary();

        return new ReportSheet('Summary', ['Measure', 'Value'], [
            ['Reporting period', $this->report->periodLabel()],
            ['Generated', now()->format('Y-m-d H:i')],
            ['Registered organizations', $s['organizations']],
            ['Currently accredited', $s['accredited']],
            ['Verified activities in period', $s['verifiedActivities']],
            ['Residents reached in period', $s['residentsReached']],
            ['Self-initiated activities', $s['independent']],
            ['LGU-organized activities', $s['lguOrganized']],
            ['Self-initiated share', $s['selfInitiatedRatio']],
        ], ['B' => '#,##0.###']);
    }

    private function sectors(): ReportSheet
    {
        return new ReportSheet('Sector comparison', [
            'Sector', 'Accredited organizations', 'Verified activities', 'Activities per organization',
            'Self-initiated', 'LGU-organized', 'Self-initiated share', 'Average performance score',
        ], $this->report->sectorComparison()->map(fn ($r) => [
            $r['sector'], $r['accredited'], $r['verified'], $r['perOrganization'],
            $r['independent'], $r['lgu'], $r['ratio'], $r['averageScore'],
        ])->all(), ['G' => ReportSheet::PERCENT, 'H' => ReportSheet::DECIMAL]);
    }

    private function trend(): ReportSheet
    {
        $compliance = $this->report->complianceTrend()->keyBy('label');

        return new ReportSheet('Monthly trend', [
            'Month', 'Verified activities', 'Self-initiated', 'LGU-organized', 'Applications filed', 'Applications approved',
        ], $this->report->activityTrend()->map(fn ($r) => [
            $r['label'], $r['total'], $r['independent'], $r['lgu'],
            $compliance[$r['label']]['filed'] ?? 0, $compliance[$r['label']]['approved'] ?? 0,
        ])->all());
    }

    private function atRisk(): ReportSheet
    {
        return new ReportSheet('At-risk organizations', [
            'Organization', 'Sector', 'Barangay', 'Reasons', 'Verified activities', 'Self-initiated',
            'LGU-organized', 'Last verified activity',
        ], $this->report->atRisk()->map(fn ($r) => [
            $r['name'], $r['sector'], $r['barangay'], implode('; ', $r['reasons']), $r['verified'],
            $r['independent'], $r['lgu'], $r['lastActivity']?->toDateString() ?? 'Never',
        ])->all());
    }

    private function barangays(): ReportSheet
    {
        return new ReportSheet('Barangays', ['Barangay', 'Accredited organizations', 'Verified activities'],
            $this->report->barangayBreakdown()->map(fn ($r) => [$r['barangay'], $r['accredited'], $r['activities']])->all());
    }

    private function contributors(): ReportSheet
    {
        return new ReportSheet('Top contributors', [
            'Organization', 'Sector', 'Verified activities', 'Residents reached', 'Self-initiated share',
        ], $this->report->topContributors()->map(fn ($r) => [
            $r['name'], $r['sector'], $r['verified'], $r['reach'], $r['ratio'],
        ])->all(), ['E' => ReportSheet::PERCENT]);
    }
}
