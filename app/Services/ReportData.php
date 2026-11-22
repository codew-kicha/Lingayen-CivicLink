<?php

namespace App\Services;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\ApplicationModel;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The one pipeline behind the analytics page, its PDF, and its Excel workbook, so the three can
 * never disagree. Every figure covers verified activities only, inside one reporting period.
 *
 * An organization is credited with activities it logged and activities it was tagged on as a
 * partner (the same rule as PerformanceScoreCalculator).
 */
class ReportData
{
    /** Below this many activities with a known source, a self-initiated ratio is not shown. */
    public const MIN_FOR_RATIO = 3;

    /** A self-initiated share under this flags an organization as mostly attending LGU events. */
    public const MOSTLY_LGU_BELOW = 0.25;

    private ?Collection $stats = null;

    public function __construct(public readonly Carbon $from, public readonly Carbon $to) {}

    /** ?from=YYYY-MM-DD&to=YYYY-MM-DD, defaulting to the last 12 months including this one. */
    public static function fromRequest(Request $request): self
    {
        $valid = $request->validate([
            'from' => ['nullable', 'date', ...($request->filled('to') ? ['before_or_equal:to'] : [])],
            'to' => ['nullable', 'date'],
        ]);

        $to = isset($valid['to']) ? Carbon::parse($valid['to'])->endOfDay() : now()->endOfDay();
        $from = isset($valid['from']) ? Carbon::parse($valid['from'])->startOfDay() : $to->copy()->subMonths(11)->startOfMonth();

        // Five years keeps the monthly trend readable and the queries cheap.
        return new self(max($from, $to->copy()->subYears(5)->startOfMonth()), $to);
    }

    /**
     * The same dates a year earlier, for the "compared with" figures on the page: this year so far
     * against the same months last year, March against last March. Community work is seasonal
     * (fiestas, typhoon season, school breaks), so this is fairer than the days just before.
     * Periods longer than a year go back by whole years so the two never overlap.
     */
    public function previous(): self
    {
        return new self($this->from->copy()->subYearsNoOverflow($this->yearsBack()), $this->to->copy()->subYearsNoOverflow($this->yearsBack()));
    }

    /** How many years back previous() looks: one, or more for periods longer than a year. */
    public function yearsBack(): int
    {
        return max(1, (int) ceil($this->from->diffInDays($this->to) / 366));
    }

    public function periodLabel(): string
    {
        return $this->from->format('j M Y').' to '.$this->to->format('j M Y');
    }

    /** Every section, for the page and the PDF. */
    public function all(): array
    {
        return [
            'period' => $this,
            'summary' => $this->summary(),
            'sectorDistribution' => $this->sectorDistribution(),
            'activityTrend' => $this->activityTrend(),
            'complianceTrend' => $this->complianceTrend(),
            'topContributors' => $this->topContributors(),
            'sectorComparison' => $this->sectorComparison(),
            'atRisk' => $this->atRisk(),
            'barangays' => $this->barangayBreakdown(),
        ];
    }

    public function summary(): array
    {
        $verified = $this->verifiedInPeriod();
        $sources = (clone $verified)->selectRaw('activity_source, count(*) as total')->groupBy('activity_source')->pluck('total', 'activity_source');

        return [
            'organizations' => Organization::count(),
            'accredited' => Accreditation::where('status', 'active')->count(),
            'verifiedActivities' => (clone $verified)->count(),
            'residentsReached' => (int) (clone $verified)->sum('participants_estimate'),
            'independent' => (int) ($sources['independent'] ?? 0),
            'lguOrganized' => (int) ($sources['lgu_organized'] ?? 0),
            'selfInitiatedRatio' => self::ratio((int) ($sources['independent'] ?? 0), (int) ($sources['lgu_organized'] ?? 0)),
            'unknownSource' => (int) $sources->sum() - (int) ($sources['independent'] ?? 0) - (int) ($sources['lgu_organized'] ?? 0),
            // Organizations credited (as lead or partner) with at least one verified activity in the period.
            'activeOrganizations' => $this->organizationRows()->where('verified', '>', 0)->count(),
            // config('metrics.participation_rate'): today's accredited organizations that did verified work.
            'activeAccredited' => $active = $this->organizationRows()->where('accredited', true)->where('verified', '>', 0)->count(),
            'participationRate' => ($accredited = $this->organizationRows()->where('accredited', true)->count())
                ? round($active / $accredited, 3) : null,
            ...$this->decisions(),
        ];
    }

    /** config('metrics.accreditation_rate'): the office's final decisions dated in the period. */
    private function decisions(): array
    {
        $decided = ApplicationModel::whereIn('status', ['approved', 'rejected'])
            ->whereBetween('reviewed_at', [$this->from, $this->to])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $approved = (int) ($decided['approved'] ?? 0);
        $rejected = (int) ($decided['rejected'] ?? 0);

        return [
            'approvedDecisions' => $approved,
            'rejectedDecisions' => $rejected,
            'accreditationRate' => $approved + $rejected ? round($approved / ($approved + $rejected), 3) : null,
        ];
    }

    /**
     * One plain-language sentence per chart (pre-final defense panel: say what the numbers mean, not
     * just show them). Computed from the same figures the charts draw, so they always agree.
     *
     * @return array<string, string>
     */
    public function insights(): array
    {
        $trend = $this->activityTrend();
        $months = max($trend->count(), 1);
        $total = $trend->sum('total');
        $busiest = $trend->sortByDesc('total')->first();
        $quietMonths = $trend->where('total', 0)->count();
        $reachTop = $trend->sortByDesc('reach')->first();
        $percent = fn (float $share) => round($share * 100).'%';

        $insights['volume'] = $total === 0
            ? 'No verified activity in this period.'
            : sprintf('Busiest month: %s with %d, %s the monthly average of %s.%s',
                $busiest['label'], $busiest['total'],
                $busiest['total'] >= 2 * ($total / $months) ? round($busiest['total'] / ($total / $months), 1).'×' : 'above',
                round($total / $months, 1),
                $quietMonths ? " {$quietMonths} of {$months} months had none." : ' Every month had at least one.');
        $insights['reach'] = $reachTop && $reachTop['reach'] > 0
            ? sprintf('Most residents reached in %s: %s, %s of the period\'s total.', $reachTop['label'], number_format($reachTop['reach']),
                $percent($reachTop['reach'] / max($trend->sum('reach'), 1)))
            : 'No residents reached in this period.';

        $sectors = $this->sectorComparison();
        $rated = $sectors->whereNotNull('ratio')->sortByDesc('ratio');
        $unrated = $sectors->whereNull('ratio')->count();
        $insights['sectors'] = $rated->count() >= 2
            ? sprintf('%s runs most of its own work (%s self-initiated); %s relies most on LGU events (%s).%s',
                $rated->first()['sector'], $percent($rated->first()['ratio']), $rated->last()['sector'], $percent($rated->last()['ratio']),
                $unrated ? " {$unrated} ".($unrated === 1 ? 'sector has' : 'sectors have').' too few activities to compare.' : '')
            : 'Too few activities with a known organizer to compare sectors yet.';

        $barangays = $this->barangayBreakdown();
        $none = $barangays->where('activities', 0);
        $top = $barangays->first();
        $insights['barangays'] = ($top['activities'] ?? 0) > 0
            ? sprintf('%s leads with %d (%s of all verified activity). %d of %d barangays had none%s.',
                $top['barangay'], $top['activities'], $percent($top['activities'] / max($barangays->sum('activities'), 1)),
                $none->count(), $barangays->count(),
                $none->isNotEmpty() ? ', for example '.$none->take(3)->pluck('barangay')->join(', ', ' and ') : '')
            : 'No verified activity in any barangay in this period.';

        $summary = $this->summary();
        $compliance = $this->complianceTrend();
        $insights['applications'] = sprintf('%d filed and %d approved in this period. %s',
            $compliance->sum('filed'), $compliance->sum('approved'),
            $summary['accreditationRate'] === null
                ? 'No application was decided, so there is no accreditation rate yet.'
                : sprintf('Accreditation rate %s: %d approved of %d decided.', $percent($summary['accreditationRate']),
                    $summary['approvedDecisions'], $summary['approvedDecisions'] + $summary['rejectedDecisions']));

        $atRisk = $this->atRisk();
        $accredited = $this->organizationRows()->where('accredited', true)->count();
        $reason = $atRisk->flatMap(fn ($row) => array_map(fn ($r) => preg_replace('/ \(.*\)$/', '', $r), $row['reasons']))->countBy()->sortDesc();
        $insights['at_risk'] = $atRisk->isEmpty()
            ? 'No accredited organization is at risk in this period.'
            : sprintf('%d of %d accredited organizations (%s) need a follow-up. Most common reason: %s (%d).',
                $atRisk->count(), $accredited, $percent($atRisk->count() / max($accredited, 1)), lcfirst($reason->keys()->first()), $reason->first());

        $busy = $sectors->where('accredited', '>', 0)->sortByDesc('perOrganization');
        $insights['comparison'] = $busy->isNotEmpty() && $busy->first()['perOrganization'] > 0
            ? sprintf('%s is the most active per organization (%s activities each)%s.', $busy->first()['sector'], $busy->first()['perOrganization'],
                $busy->count() > 1 ? sprintf('; %s the least (%s)', $busy->last()['sector'], $busy->last()['perOrganization']) : '')
            : 'No sector has verified activity in this period yet.';

        $leaders = $this->topContributors();
        $insights['contributors'] = $leaders->isNotEmpty() && $total > 0
            ? sprintf('These %d organizations were credited with %s of the period\'s verified activities (partner credits count for each partner).',
                $leaders->count(), $percent(min($leaders->sum('verified') / $total, 1)))
            : 'No verified activities in this period.';

        return $insights;
    }

    /**
     * The details behind the summary figures on the page: what is waiting, what is expiring, the
     * busiest month and the largest activity, so a number never stands alone.
     */
    public function highlights(): array
    {
        $rows = $this->organizationRows();
        $largest = $this->verifiedInPeriod()->with('organization:id,name')
            ->orderByDesc('participants_estimate')->orderByDesc('activity_date')->first();
        $busiest = $this->activityTrend()->sortByDesc('total')->first();

        return [
            'pendingActivities' => Activity::where('status', 'pending')->count(),
            'expiring' => $rows->filter(fn ($r) => $r['accredited'] && $r['accreditedUntil']?->lte(now()->addDays(90)))
                ->sortBy(fn ($r) => $r['accreditedUntil'])->values(),
            'notAccredited' => $rows->where('accredited', false)->count(),
            'quiet' => $rows->filter(fn ($r) => $r['accredited'] && $r['verified'] === 0)->values(),
            'busiestMonth' => ($busiest['total'] ?? 0) > 0 ? $busiest : null,
            'largestActivity' => $largest,
            'topReach' => $rows->where('reach', '>', 0)->sortByDesc('reach')->take(3)->values(),
        ];
    }

    /**
     * Verified activities per month for the month drill-down: the largest first, a few per month.
     *
     * @return Collection<string, array{count: int, items: Collection}> keyed Y-m
     */
    public function activitiesByMonth(int $per = 8): Collection
    {
        return $this->verifiedInPeriod()->with('organization:id,name')
            ->orderByDesc('participants_estimate')->orderByDesc('activity_date')
            ->get(['id', 'organization_id', 'title', 'activity_date', 'activity_source', 'participants_estimate'])
            ->groupBy(fn (Activity $a) => $a->activity_date->format('Y-m'))
            ->map(fn (Collection $items) => ['count' => $items->count(), 'items' => $items->take($per)->values()]);
    }

    /** Accredited organizations by sector, as of today (not period-bound). */
    public function sectorDistribution(): Collection
    {
        return Organization::query()
            ->whereHas('accreditations', fn ($q) => $q->where('status', 'active'))
            ->selectRaw('sector, count(*) as total')
            ->groupBy('sector')
            ->orderByDesc('total')
            ->pluck('total', 'sector');
    }

    /** @return Collection<int, array{key: string, label: string, total: int, independent: int, lgu: int, unknown: int, reach: int}> */
    public function activityTrend(): Collection
    {
        $rows = $this->verifiedInPeriod()->get(['activity_date', 'activity_source', 'participants_estimate']);

        return $this->months()->map(function (Carbon $month) use ($rows) {
            $inMonth = $rows->filter(fn ($a) => $a->activity_date->isSameMonth($month));

            $independent = $inMonth->where('activity_source', 'independent')->count();
            $lgu = $inMonth->where('activity_source', 'lgu_organized')->count();

            return [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'total' => $inMonth->count(),
                'independent' => $independent,
                'lgu' => $lgu,
                'unknown' => $inMonth->count() - $independent - $lgu,
                'reach' => (int) $inMonth->sum('participants_estimate'),
            ];
        });
    }

    public function complianceTrend(): Collection
    {
        return $this->months()->map(fn (Carbon $month) => [
            'key' => $month->format('Y-m'),
            'label' => $month->format('M Y'),
            'approved' => ApplicationModel::where('status', 'approved')
                ->whereBetween('reviewed_at', [$month, $month->copy()->endOfMonth()])->count(),
            'filed' => ApplicationModel::whereBetween('submitted_at', [$month, $month->copy()->endOfMonth()])->count(),
        ]);
    }

    public function topContributors(int $limit = 10): Collection
    {
        return $this->organizationRows()
            ->filter(fn ($row) => $row['verified'] > 0)
            ->sortByDesc(fn ($row) => [$row['verified'], $row['reach']])
            ->take($limit)
            ->values();
    }

    /** Per sector, in the office's own order: participation against self-initiated work. */
    public function sectorComparison(): Collection
    {
        $accredited = $this->organizationRows()->where('accredited', true);

        return collect(config('sectors'))->map(function (string $sector) use ($accredited) {
            $rows = $accredited->where('sector', $sector);
            $scores = $rows->pluck('score')->filter(fn ($s) => $s !== null);
            $independent = $rows->sum('independent');
            $lgu = $rows->sum('lgu');

            return [
                'sector' => $sector,
                'accredited' => $rows->count(),
                'verified' => $rows->sum('verified'),
                'perOrganization' => $rows->count() ? round($rows->sum('verified') / $rows->count(), 1) : 0,
                'independent' => $independent,
                'lgu' => $lgu,
                'ratio' => self::ratio($independent, $lgu),
                'averageScore' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
            ];
        });
    }

    /**
     * Accredited organizations PESO should look at, worst first. Organizations accredited for less
     * than Organization::INACTIVE_AFTER_MONTHS are left out: an empty record there is not a warning sign.
     */
    public function atRisk(): Collection
    {
        $settled = now()->subMonths(Organization::INACTIVE_AFTER_MONTHS);

        return $this->organizationRows()
            ->filter(fn ($row) => $row['accredited'] && $row['accreditedSince']?->lte($settled))
            ->map(function ($row) use ($settled) {
                $reasons = match (true) {
                    $row['verified'] === 0 => ['No verified activity in this period'],
                    $row['verified'] === 1 => ['Only one verified activity'],
                    default => [],
                };

                // Same rule as Organization::inactive and the dashboard count, whatever the period.
                if ($row['verified'] > 0 && (! $row['lastActivity'] || $row['lastActivity']->lt($settled))) {
                    $reasons[] = 'Nothing verified in '.Organization::INACTIVE_AFTER_MONTHS.' months';
                }

                if ($row['ratio'] !== null && $row['ratio'] < self::MOSTLY_LGU_BELOW) {
                    $reasons[] = 'Mostly LGU-organized ('.round($row['ratio'] * 100).'% self-initiated)';
                }

                return $row + ['reasons' => $reasons];
            })
            ->filter(fn ($row) => $row['reasons'])
            ->sortBy(fn ($row) => [$row['verified'], $row['lastActivity']?->timestamp ?? 0])
            ->values();
    }

    /** All 32 barangays, including those with nothing, since a gap is itself a finding. */
    public function barangayBreakdown(): Collection
    {
        $rows = $this->organizationRows();
        $activities = $this->verifiedInPeriod()
            ->join('organizations', 'organizations.id', '=', 'activities.organization_id')
            ->selectRaw('organizations.barangay, count(*) as total')
            ->groupBy('organizations.barangay')
            ->pluck('total', 'organizations.barangay');

        return collect(config('barangays'))->values()->map(fn (string $barangay) => [
            'barangay' => $barangay,
            'accredited' => $rows->where('barangay', $barangay)->where('accredited', true)->count(),
            'activities' => (int) ($activities[$barangay] ?? 0),
        ])->sortByDesc(fn ($row) => [$row['activities'], $row['accredited']])->values();
    }

    /**
     * For the Outstanding CSO recognition: accredited organizations ranked by performance score
     * within each sector. Every component is included so PESO applies its own criteria.
     */
    public function awardsRanking(): Collection
    {
        return $this->organizationRows()
            ->where('accredited', true)
            ->groupBy('sector')
            ->sortBy(fn ($rows, $sector) => array_search($sector, config('sectors')))
            ->flatMap(fn (Collection $rows) => $rows
                ->sortByDesc(fn ($row) => [$row['score'] ?? -1, $row['verified']])
                ->values()
                ->map(fn ($row, $i) => $row + ['rank' => $i + 1]))
            ->values();
    }

    /**
     * One row per organization with its credited activity figures for the period, its active
     * accreditation, and its latest performance score.
     */
    public function organizationRows(): Collection
    {
        return $this->stats ??= $this->buildOrganizationRows();
    }

    private function buildOrganizationRows(): Collection
    {
        // (activity, organization) pairs: the lead organization plus every tagged partner.
        $credits = DB::table('activities')->select('id as activity_id', 'organization_id')
            ->unionAll(DB::table('activity_organization')->select('activity_id', 'organization_id'));

        $figures = DB::query()->fromSub($credits, 'credits')
            ->join('activities', 'activities.id', '=', 'credits.activity_id')
            ->where('activities.status', 'verified')
            ->whereBetween('activities.activity_date', [$this->from->toDateString(), $this->to->toDateString()])
            ->groupBy('credits.organization_id')
            ->selectRaw("credits.organization_id,
                count(*) as verified,
                coalesce(sum(activities.participants_estimate), 0) as reach,
                sum(case when activities.activity_source = 'independent' then 1 else 0 end) as independent,
                sum(case when activities.activity_source = 'lgu_organized' then 1 else 0 end) as lgu,
                max(activities.activity_date) as last_activity")
            ->get()
            ->keyBy('organization_id');

        // Most recent verified activity ever, so a long silence shows even when it predates the period.
        $lastEver = DB::query()->fromSub($credits, 'credits')
            ->join('activities', 'activities.id', '=', 'credits.activity_id')
            ->where('activities.status', 'verified')
            ->groupBy('credits.organization_id')
            ->selectRaw('credits.organization_id, max(activities.activity_date) as last_activity')
            ->pluck('last_activity', 'organization_id');

        return Organization::query()
            ->with([
                'accreditations' => fn ($q) => $q->where('status', 'active'),
                'performanceScores' => fn ($q) => $q->latest('computed_at')->limit(1),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Organization $organization) use ($figures, $lastEver) {
                $f = $figures[$organization->id] ?? null;
                $accreditation = $organization->accreditations->first();
                $score = $organization->performanceScores->first();
                $independent = (int) ($f->independent ?? 0);
                $lgu = (int) ($f->lgu ?? 0);

                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'sector' => $organization->sector,
                    'barangay' => $organization->barangay,
                    'accredited' => $accreditation !== null,
                    'accreditedSince' => $accreditation?->issued_at,
                    'accreditedUntil' => $accreditation?->expires_at,
                    'verified' => (int) ($f->verified ?? 0),
                    'reach' => (int) ($f->reach ?? 0),
                    'independent' => $independent,
                    'lgu' => $lgu,
                    'ratio' => self::ratio($independent, $lgu),
                    'lastActivity' => isset($lastEver[$organization->id]) ? Carbon::parse($lastEver[$organization->id]) : null,
                    'score' => $score ? (float) $score->total_score : null,
                    'components' => $score ? [
                        'activity_frequency' => (float) $score->activity_frequency,
                        'community_reach' => (float) $score->community_reach,
                        'compliance_timeliness' => (float) $score->compliance_timeliness,
                        'document_currency' => (float) $score->document_currency,
                    ] : null,
                ];
            });
    }

    /**
     * Each part's percentage of the activities with a known organizer, in whole numbers that add up
     * to 100 (LGU-organized is the remainder), for the split bars on the analytics page.
     *
     * @return array{independent: int, lgu: int}
     */
    public static function shares(int $independent, int $lgu): array
    {
        $known = $independent + $lgu;
        if ($known === 0) {
            return ['independent' => 0, 'lgu' => 0];
        }
        $self = (int) round($independent / $known * 100);

        return ['independent' => $self, 'lgu' => 100 - $self];
    }

    /** Self-initiated share of activities with a known source, or null when the sample is too small. */
    public static function ratio(int $independent, int $lgu): ?float
    {
        $known = $independent + $lgu;

        return $known >= self::MIN_FOR_RATIO ? round($independent / $known, 3) : null;
    }

    /**
     * The last 24 months for one organization's admin timeline, with the longest run of empty
     * months so a long silence is named rather than left for someone to notice.
     *
     * @return array{months: Collection, longestGap: ?array{from: Carbon, to: Carbon, months: int}}
     */
    public static function timeline(Organization $organization, int $months = 24): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $activities = Activity::crediting($organization)
            ->where('status', 'verified')
            ->where('activity_date', '>=', $start->toDateString())
            ->get(['activity_date', 'activity_source']);

        $series = collect(range(0, $months - 1))->map(function (int $i) use ($start, $activities) {
            $month = $start->copy()->addMonths($i);
            $inMonth = $activities->filter(fn ($a) => $a->activity_date->isSameMonth($month));

            return [
                'month' => $month,
                'label' => $month->format('M Y'),
                'independent' => $inMonth->where('activity_source', 'independent')->count(),
                'lgu' => $inMonth->where('activity_source', 'lgu_organized')->count(),
                'unknown' => $inMonth->whereNull('activity_source')->count(),
                'total' => $inMonth->count(),
            ];
        });

        // Months before the first verified activity are not a silence (the organization may be newly
        // onboarded); runs are counted from then on.
        $longest = null;
        $run = [];
        $started = false;
        foreach ($series->push(['total' => 1]) as $row) {   // sentinel closes a trailing run
            $started = $started || $row['total'] > 0;
            if ($started && $row['total'] === 0) {
                $run[] = $row['month'];

                continue;
            }
            if (count($run) > ($longest['months'] ?? 0)) {
                $longest = ['from' => $run[0], 'to' => end($run), 'months' => count($run)];
            }
            $run = [];
        }
        $series->pop();

        return ['months' => $series, 'longestGap' => ($longest['months'] ?? 0) >= 3 ? $longest : null];
    }

    private function verifiedInPeriod()
    {
        return Activity::query()
            ->where('activities.status', 'verified')
            ->whereBetween('activities.activity_date', [$this->from->toDateString(), $this->to->toDateString()]);
    }

    /** @return Collection<int, Carbon> */
    private function months(): Collection
    {
        $months = collect();
        for ($month = $this->from->copy()->startOfMonth(); $month->lte($this->to); $month->addMonth()) {
            $months->push($month->copy());
        }

        return $months;
    }
}
