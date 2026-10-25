{{--
    Sample annual report for the demo data (DatabaseSeeder::newsAndReports). Real reports are
    uploaded by the office through Admin > Annual reports; this one exists so the public
    Resources page has something to open in a demo. Every figure in it is invented.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    @include('pdf.styles')
    <style>
        .demo { border: 1px solid #b45309; color: #92400e; background: #fffbeb; padding: 2mm 3mm; font-size: 8pt; margin-bottom: 5mm; }
        .stats td { border: none; width: 20%; text-align: center; padding: 3mm 1mm; }
        .stats strong { display: block; font-size: 16pt; color: #4a1a22; }
        .num { text-align: right; }
        p { line-height: 1.5; margin: 0 0 2.5mm; }
        ul { margin: 0 0 2mm 5mm; padding: 0; }
        li { margin-bottom: 1.5mm; line-height: 1.45; }
    </style>
</head>
<body>
    <p class="demo">Demonstration copy generated with sample data for the system demo. The figures and events below are invented.</p>

    <div class="head">
        <p class="muted">{{ config('office.office.parent') }}, {{ config('office.office.name') }}</p>
        <h1>{{ $title }}</h1>
        <p class="muted">Covering January to December {{ $year }}</p>
    </div>

    <h2>Message from the Desk Office</h2>
    <p>
        {{ $year }} was a year of {{ $year >= now()->year - 1 ? 'growth' : 'groundwork' }} for Lingayen's civil society organizations.
        The Civil Society Desk Office accredited {{ $totals['new'] }} new organizations and renewed {{ $totals['renewed'] }},
        bringing the number of accredited CSOs to {{ $totals['accredited'] }}. Together they reported
        {{ number_format($totals['activities']) }} verified activities that reached an estimated
        {{ number_format($totals['residents']) }} residents across {{ $totals['barangays'] }} barangays.
    </p>
    <p>
        This report summarizes accreditation, the work organizations carried out, and the office's plans for
        {{ $year + 1 }}. Figures come from the accreditation records and the activity logs verified by the office.
    </p>

    <h2>At a glance</h2>
    <table class="stats">
        <tr>
            <td><strong>{{ $totals['accredited'] }}</strong><span class="muted">Accredited CSOs</span></td>
            <td><strong>{{ $totals['new'] }}</strong><span class="muted">New accreditations</span></td>
            <td><strong>{{ $totals['renewed'] }}</strong><span class="muted">Renewals</span></td>
            <td><strong>{{ number_format($totals['activities']) }}</strong><span class="muted">Verified activities</span></td>
            <td><strong>{{ number_format($totals['residents']) }}</strong><span class="muted">Residents reached</span></td>
        </tr>
    </table>

    <h2>Accredited organizations by sector</h2>
    <table>
        <thead><tr><th>Sector</th><th class="num">Organizations</th><th class="num">Verified activities</th><th class="num">Residents reached</th><th class="num">Self-initiated</th></tr></thead>
        <tbody>
            @foreach ($sectors as $row)
                <tr>
                    <td>{{ $row['sector'] }}</td>
                    <td class="num">{{ $row['organizations'] }}</td>
                    <td class="num">{{ $row['activities'] }}</td>
                    <td class="num">{{ number_format($row['residents']) }}</td>
                    <td class="num">{{ $row['independent'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Most active organizations</h2>
    <table>
        <thead><tr><th style="width: 6%">#</th><th>Organization</th><th>Sector</th><th>Barangay</th><th class="num">Activities</th></tr></thead>
        <tbody>
            @foreach ($top as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['sector'] }}</td>
                    <td>{{ $row['barangay'] }}</td>
                    <td class="num">{{ $row['activities'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Highlights</h2>
    <ul>
        @foreach ($highlights as $highlight)
            <li>{{ $highlight }}</li>
        @endforeach
    </ul>

    <h2>Plans for {{ $year + 1 }}</h2>
    <ul>
        <li>Hold accreditation orientations in coastal and upland barangays before the filing window opens.</li>
        <li>Help organizations without internet access file through assisted encoding at the office.</li>
        <li>Remind organizations ahead of accreditation and document expiry so renewals are filed on time.</li>
        <li>Recognize the most active organizations at the CSO Assembly.</li>
    </ul>

    <table class="sign">
        <tr>
            <td><span>{{ config('office.officials.3.name') }}<br><small class="muted">{{ config('office.officials.3.position') }}</small></span></td>
            <td><span>{{ config('office.officials.2.name') }}<br><small class="muted">{{ config('office.officials.2.position') }}</small></span></td>
        </tr>
    </table>
</body>
</html>
