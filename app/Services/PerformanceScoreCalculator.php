<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PerformanceScore;
use Illuminate\Support\Carbon;

/**
 * Equal-weighted performance score (PRD §9):
 *
 *   score = 0.25 x activity_frequency + 0.25 x community_reach
 *         + 0.25 x compliance_timeliness + 0.25 x document_currency
 *
 * The PRD fixes the weights and names the four components but not how each is derived from the
 * data, so the definitions below are this build's interpretation and are pending sign-off from
 * PESO. Each component normalizes to 0..1 before weighting. The score is informational only and
 * must never gate a renewal decision.
 */
class PerformanceScoreCalculator
{
    /** Verified activities per year for a full activity_frequency score. */
    private const TARGET_ACTIVITIES_PER_YEAR = 12;

    /** Residents reached per year for a full community_reach score. */
    private const TARGET_REACH_PER_YEAR = 1000;

    public function recalculate(Organization $organization): PerformanceScore
    {
        [$periodStart, $periodEnd] = $this->currentPeriod($organization);

        $components = [
            'activity_frequency' => $this->activityFrequency($organization, $periodStart, $periodEnd),
            'community_reach' => $this->communityReach($organization, $periodStart, $periodEnd),
            'compliance_timeliness' => $this->complianceTimeliness($organization),
            'document_currency' => $this->documentCurrency($organization),
        ];

        return $organization->performanceScores()->updateOrCreate(
            ['period_start' => $periodStart, 'period_end' => $periodEnd],
            [...$components, 'total_score' => array_sum($components) / 4, 'computed_at' => now()],
        );
    }

    /**
     * The scoring window is the current accreditation term, or the trailing year when the
     * organization has never been accredited.
     */
    private function currentPeriod(Organization $organization): array
    {
        $accreditation = $organization->accreditations()->where('status', 'active')->first();

        if ($accreditation) {
            return [$accreditation->issued_at, $accreditation->expires_at];
        }

        return [Carbon::now()->subYear()->startOfDay(), Carbon::now()->endOfDay()];
    }

    private function activityFrequency(Organization $organization, $start, $end): float
    {
        $years = max($start->diffInDays($end) / 365, 1);

        $verified = $organization->activities()
            ->where('status', 'verified')
            ->whereBetween('activity_date', [$start, $end])
            ->count();

        return $this->clamp($verified / (self::TARGET_ACTIVITIES_PER_YEAR * $years));
    }

    private function communityReach(Organization $organization, $start, $end): float
    {
        $years = max($start->diffInDays($end) / 365, 1);

        $reached = (int) $organization->activities()
            ->where('status', 'verified')
            ->whereBetween('activity_date', [$start, $end])
            ->sum('participants_estimate');

        return $this->clamp($reached / (self::TARGET_REACH_PER_YEAR * $years));
    }

    /**
     * Rewards filing before the previous accreditation lapsed. An organization that has never
     * renewed sits at the neutral midpoint rather than being penalised for having no history.
     */
    private function complianceTimeliness(Organization $organization): float
    {
        $renewal = $organization->applications()
            ->where('type', 'renewal')
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->first();

        if (! $renewal) {
            return 0.5;
        }

        $previous = $organization->accreditations()
            ->where('id', '!=', $renewal->accreditation?->id)
            ->latest('expires_at')
            ->first();

        if (! $previous) {
            return 0.5;
        }

        $daysLate = $previous->expires_at->diffInDays($renewal->submitted_at, false);

        // Filed 30+ days early scores full marks; 90+ days late scores zero.
        return $this->clamp((30 - $daysLate) / 120);
    }

    private function documentCurrency(Organization $organization): float
    {
        $required = array_keys(config('document_types'));

        $current = $organization->documents()
            ->whereIn('document_type', $required)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString()))
            ->distinct()
            ->count('document_type');

        return $this->clamp($current / count($required));
    }

    private function clamp(float $value): float
    {
        return round(max(0, min(1, $value)), 4);
    }
}
