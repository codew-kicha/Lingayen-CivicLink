{{--
    Stand-in for a requirement the demo organizations "uploaded" (DatabaseSeeder::documents), so the
    reviewer's View links open a file during a demo instead of a 404.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $label }}, {{ $organization->name }}</title>
    @include('pdf.styles')
    <style>
        .demo { border: 1px solid #b45309; color: #92400e; background: #fffbeb; padding: 2mm 3mm; font-size: 8pt; margin-bottom: 5mm; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <p class="demo">Sample file generated for the system demo. It stands in for the scanned document an organization would upload.</p>

    <div class="head">
        <p class="muted">{{ $organization->name }} &middot; Barangay {{ $organization->barangay }}, Lingayen</p>
        <h1>{{ $label }}</h1>
    </div>

    <p>{{ $text }}</p>

    <table class="sign">
        <tr>
            <td><span>President<br><small class="muted">{{ $organization->name }}</small></span></td>
            <td><span>Secretary<br><small class="muted">{{ $organization->name }}</small></span></td>
        </tr>
    </table>
</body>
</html>
