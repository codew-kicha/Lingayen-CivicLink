{{-- Requirement (b), produced from the member list the representative entered on the website
     (App\Services\ApplicationSteps::submit). The representative is listed first. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Updated List of Officers and Members</title>
    @include('pdf.styles')
    <style>
        .center { text-align: center; }
        .fill { border-bottom: 1px solid #6b7280; display: inline-block; min-width: 70mm; }
        td.num { width: 7%; text-align: center; color: #6b7280; }
        p.note { font-size: 8.5pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="head center">
        <p class="muted">Republic of the Philippines · Municipality of Lingayen, Pangasinan</p>
        <h1>Updated List of Officers and Members</h1>
        <p class="muted">Requirement for CSO accreditation · Civil Society Desk Office</p>
    </div>

    <p>
        Name of organization: <span class="fill">{{ $organization->name }}</span><br>
        Barangay: <span class="fill">{{ $organization->barangay }}</span>
        &nbsp; Sector: <span class="fill">{{ $organization->sector }}</span><br>
        As of: <span class="fill">{{ $asOf->format('d F Y') }}</span>
    </p>

    <table>
        <thead>
            <tr><th>No.</th><th style="width: 22%">Last name</th><th style="width: 22%">First name</th><th>Middle name</th><th>Position</th><th>Contact number</th></tr>
        </thead>
        <tbody>
            @foreach ($members as $member)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $member->last_name ?? $member->name }}</td>
                    <td>{{ $member->first_name }}</td>
                    <td>{{ $member->middle_name }}</td>
                    <td>{{ $member->position }}</td>
                    <td>{{ $member->contact_number }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="note">{{ $members->count() }} {{ Str::plural('member', $members->count()) }}. Entered on Lingayen CivicLink by the organization's representative.</p>
</body>
</html>
