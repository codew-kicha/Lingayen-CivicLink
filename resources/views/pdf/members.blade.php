<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Officers and members, {{ $organization->name }}</title>
    @include('pdf.styles')
</head>
<body>
    <div class="head">
        <p class="muted">{{ config('office.office.parent') }}, {{ config('office.office.name') }}</p>
        <h1>Officers and members</h1>
        <p>{{ $organization->name }}<br>
            <span class="muted">{{ $organization->sector }} &middot; Barangay {{ $organization->barangay }}, Lingayen</span></p>
    </div>

    @if ($organization->members->isEmpty())
        <p>No officers or members are on file.</p>
    @else
        <table>
            <thead><tr><th style="width: 6%">#</th><th style="width: 34%">Name</th><th style="width: 22%">Position</th><th>Contact number</th><th>Email</th></tr></thead>
            <tbody>
                @foreach ($organization->members as $member)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->position }}</td>
                        <td>{{ $member->contact_number }}</td>
                        <td>{{ $member->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top: 8mm">
        Confidential: contains personal information under the Data Privacy Act of 2012 (RA 10173). For PESO office use only.<br>
        Printed {{ $printedAt->format('d F Y, g:i A') }} by {{ $printedBy }}.
    </p>
</body>
</html>
