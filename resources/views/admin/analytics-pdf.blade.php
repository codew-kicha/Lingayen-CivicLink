@php $percent = fn (?float $ratio) => $ratio === null ? 'n/a' : round($ratio * 100).'%'; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lingayen CivicLink report</title>
    {{-- dompdf parses a limited CSS subset, so this view carries its own plain styles
         rather than the Tailwind build. Same ReportData as the analytics page. --}}
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #2b2f38; }
        h1 { font-size: 16pt; margin: 0 0 2mm; }
        h2 { font-size: 11.5pt; margin: 7mm 0 2mm; border-bottom: 1px solid #c9ccd4; padding-bottom: 1.5mm; }
        .meta { color: #6b7280; font-size: 8.5pt; margin-bottom: 5mm; }
        .lede { color: #6b7280; font-size: 8.5pt; margin: 0 0 2mm; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 7.5pt; text-transform: uppercase; color: #6b7280;
             border-bottom: 1px solid #9ca3af; padding: 1.5mm 1.2mm; }
        td { padding: 1.5mm 1.2mm; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .num { text-align: right; }
        .summary td { border: none; padding: 1.5mm 0; }
        .summary .val { font-size: 13pt; font-weight: bold; color: #1e2a47; }
        .bar { background: #eef0f4; height: 8px; width: 100%; }
        .bar span { display: inline-block; height: 8px; }
        .self { background: #2f4a7d; }
        .lgu { background: #aebbd6; }
        .key span { display: inline-block; width: 8px; height: 8px; margin: 0 1mm 0 3mm; }
        .page { page-break-before: always; }
        .note { color: #6b7280; font-size: 8pt; margin-top: 6mm; }
    </style>
</head>
<body>
    <h1>Lingayen CivicLink, CSO activity and accreditation report</h1>
    <p class="meta">
        Public Employment Service Office, Lingayen, Pangasinan<br>
        Reporting period: {{ $period->periodLabel() }}<br>
        Generated {{ $generatedAt->format('d F Y, g:i A') }} by {{ $generatedBy }}
    </p>

    <h2>Summary</h2>
    <table class="summary">
        <tr>
            <td><span class="val">{{ number_format($summary['organizations']) }}</span><br>Registered organizations</td>
            <td><span class="val">{{ number_format($summary['accredited']) }}</span><br>Currently accredited</td>
            <td><span class="val">{{ number_format($summary['verifiedActivities']) }}</span><br>Verified activities</td>
            <td><span class="val">{{ number_format($summary['residentsReached']) }}</span><br>Residents reached</td>
            <td><span class="val">{{ $percent($summary['selfInitiatedRatio']) }}</span><br>Self-initiated</td>
        </tr>
    </table>

    <h2>Self-initiated or LGU-organized, by sector</h2>
    <p class="lede key">Accredited organizations' verified activities.<span class="self"></span>Self-initiated<span class="lgu"></span>LGU-organized</p>
    @php $maxSector = max($sectorComparison->max(fn ($r) => $r['independent'] + $r['lgu']), 1); @endphp
    <table>
        <thead>
            <tr>
                <th style="width: 24%">Sector</th><th class="num">Accredited</th><th class="num">Activities</th>
                <th class="num">Per org.</th><th style="width: 26%"></th><th class="num">Self-initiated</th><th class="num">Avg. score</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sectorComparison as $row)
                <tr>
                    <td>{{ $row['sector'] }}</td>
                    <td class="num">{{ $row['accredited'] }}</td>
                    <td class="num">{{ $row['verified'] }}</td>
                    <td class="num">{{ $row['perOrganization'] }}</td>
                    <td><div class="bar"><span class="self" style="width: {{ round($row['independent'] / $maxSector * 100) }}%"></span><span class="lgu" style="width: {{ round($row['lgu'] / $maxSector * 100) }}%"></span></div></td>
                    <td class="num">{{ $percent($row['ratio']) }}</td>
                    <td class="num">{{ $row['averageScore'] === null ? 'n/a' : number_format($row['averageScore'] * 100) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>At-risk organizations</h2>
    <p class="lede">Accredited for at least {{ \App\Models\Organization::INACTIVE_AFTER_MONTHS }} months, with little or no verified activity in the period, or only LGU-organized activity.</p>
    @if ($atRisk->isEmpty())
        <p>No accredited organization is at risk in this period.</p>
    @else
        <table>
            <thead><tr><th>Organization</th><th>Sector</th><th>Reasons</th><th class="num">Verified</th><th>Last verified activity</th></tr></thead>
            <tbody>
                @foreach ($atRisk as $row)
                    <tr>
                        <td>{{ $row['name'] }}<br><span style="color:#6b7280">Brgy. {{ $row['barangay'] }}</span></td>
                        <td>{{ $row['sector'] }}</td>
                        <td>{{ implode('; ', $row['reasons']) }}</td>
                        <td class="num">{{ $row['verified'] }}</td>
                        <td>{{ $row['lastActivity']?->format('d M Y') ?? 'Never' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Monthly trend</h2>
    @php $compliance = $complianceTrend->keyBy('label'); @endphp
    <table>
        <thead>
            <tr><th>Month</th><th class="num">Verified activities</th><th class="num">Self-initiated</th><th class="num">LGU-organized</th><th class="num">Applications filed</th><th class="num">Approved</th></tr>
        </thead>
        <tbody>
            @foreach ($activityTrend as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['total'] }}</td>
                    <td class="num">{{ $row['independent'] }}</td>
                    <td class="num">{{ $row['lgu'] }}</td>
                    <td class="num">{{ $compliance[$row['label']]['filed'] ?? 0 }}</td>
                    <td class="num">{{ $compliance[$row['label']]['approved'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top contributors</h2>
    @if ($topContributors->isEmpty())
        <p>No verified activities in this period.</p>
    @else
        <table>
            <thead><tr><th>Organization</th><th>Sector</th><th class="num">Activities</th><th class="num">Reach</th><th class="num">Self-initiated</th></tr></thead>
            <tbody>
                @foreach ($topContributors as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['sector'] }}</td>
                        <td class="num">{{ $row['verified'] }}</td>
                        <td class="num">{{ number_format($row['reach']) }}</td>
                        <td class="num">{{ $percent($row['ratio']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>By barangay</h2>
    <table>
        <thead><tr><th>Barangay</th><th class="num">Accredited organizations</th><th class="num">Verified activities</th></tr></thead>
        <tbody>
            @foreach ($barangays as $row)
                <tr><td>{{ $row['barangay'] }}</td><td class="num">{{ $row['accredited'] }}</td><td class="num">{{ $row['activities'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="page">
        <h2 style="margin-top: 0">Awards ranking</h2>
        <p class="lede">Accredited organizations ranked by performance score within each sector, for the Outstanding CSO recognition. Scores out of 100.</p>
        <table>
            <thead><tr><th>Sector</th><th class="num">Rank</th><th>Organization</th><th class="num">Score</th><th class="num">Activities</th><th class="num">Reach</th><th class="num">Self-initiated</th></tr></thead>
            <tbody>
                @foreach ($awards as $row)
                    <tr>
                        <td>{{ $row['rank'] === 1 ? $row['sector'] : '' }}</td>
                        <td class="num">{{ $row['rank'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ $row['score'] === null ? 'n/a' : number_format($row['score'] * 100) }}</td>
                        <td class="num">{{ $row['verified'] }}</td>
                        <td class="num">{{ number_format($row['reach']) }}</td>
                        <td class="num">{{ $percent($row['ratio']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="note">
        Figures cover verified activities only. An organization is credited with activities it logged and
        those it was tagged on as a partner. Performance scores are informational only and do not affect
        accreditation renewal decisions. Member details are not included.
    </p>
</body>
</html>
