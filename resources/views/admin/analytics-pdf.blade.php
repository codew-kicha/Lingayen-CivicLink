<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lingayen CivicLink report</title>
    {{-- dompdf parses a limited CSS subset, so this view carries its own plain styles
         rather than the Tailwind build. --}}
    <style>
        @page { margin: 20mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #2b2f38; }
        h1 { font-size: 17pt; margin: 0 0 2mm; }
        h2 { font-size: 12pt; margin: 8mm 0 2mm; border-bottom: 1px solid #c9ccd4; padding-bottom: 1.5mm; }
        .meta { color: #6b7280; font-size: 9pt; margin-bottom: 6mm; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 8.5pt; text-transform: uppercase; color: #6b7280;
             border-bottom: 1px solid #9ca3af; padding: 2mm 1.5mm; }
        td { padding: 2mm 1.5mm; border-bottom: 1px solid #e5e7eb; }
        .num { text-align: right; }
        .summary td { border: none; padding: 1.5mm 0; }
        .summary .val { font-size: 14pt; font-weight: bold; color: #1e2a47; }
        .bar { background: #dfe3ea; height: 8px; }
        .bar span { display: block; background: #3f5a8a; height: 8px; }
        .note { color: #6b7280; font-size: 8.5pt; margin-top: 8mm; }
    </style>
</head>
<body>
    <h1>Lingayen CivicLink, accreditation and activity report</h1>
    <p class="meta">
        Public Employment Service Office, Lingayen, Pangasinan<br>
        Generated {{ $generatedAt->format('d F Y, g:i A') }} by {{ $generatedBy }}
    </p>

    <h2>Summary</h2>
    <table class="summary">
        <tr>
            <td><span class="val">{{ number_format($summary['organizations']) }}</span><br>Registered organizations</td>
            <td><span class="val">{{ number_format($summary['accredited']) }}</span><br>Currently accredited</td>
            <td><span class="val">{{ number_format($summary['verifiedActivities']) }}</span><br>Verified activities</td>
            <td><span class="val">{{ number_format($summary['residentsReached']) }}</span><br>Residents reached</td>
        </tr>
    </table>

    <h2>Sector distribution</h2>
    @if ($sectorDistribution->isEmpty())
        <p>No accredited organizations yet.</p>
    @else
        @php $maxSector = max($sectorDistribution->max(), 1); @endphp
        <table>
            @foreach ($sectorDistribution as $sector => $total)
                <tr>
                    <td style="width: 45%">{{ $sector }}</td>
                    <td><div class="bar"><span style="width: {{ round(($total / $maxSector) * 100) }}%"></span></div></td>
                    <td class="num" style="width: 12%">{{ $total }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Verified activity volume, last 12 months</h2>
    <table>
        <thead>
            <tr><th>Month</th><th class="num">Verified activities</th></tr>
        </thead>
        <tbody>
            @foreach ($activityTrend as $row)
                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['total'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Compliance trend, last 12 months</h2>
    <table>
        <thead>
            <tr><th>Month</th><th class="num">Applications filed</th><th class="num">Approved</th></tr>
        </thead>
        <tbody>
            @foreach ($complianceTrend as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['filed'] }}</td>
                    <td class="num">{{ $row['approved'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top contributors</h2>
    @if ($topContributors->isEmpty())
        <p>No verified activities have been logged yet.</p>
    @else
        <table>
            <thead>
                <tr><th>Organization</th><th>Sector</th><th class="num">Activities</th><th class="num">Reach</th></tr>
            </thead>
            <tbody>
                @foreach ($topContributors as $organization)
                    <tr>
                        <td>{{ $organization->name }}</td>
                        <td>{{ $organization->sector }}</td>
                        <td class="num">{{ $organization->verified_count }}</td>
                        <td class="num">{{ number_format((int) $organization->reach) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="note">
        Figures are descriptive and aggregate. Performance scores are informational only and do not
        affect accreditation renewal decisions.
    </p>
</body>
</html>
