{{-- Requirement (b): updated list of officers and members with contact numbers. Pre-filled from the
     organization profile, with blank rows to complete by hand (RequirementFormatController). --}}
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
        td { height: 6mm; }
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
        Name of organization: <span class="fill">{{ $organization?->name }}</span><br>
        Barangay: <span class="fill">{{ $organization?->barangay }}</span>
        &nbsp; Sector: <span class="fill">{{ $organization?->sector }}</span><br>
        As of (date): <span class="fill">&nbsp;</span>
    </p>

    <table>
        <thead>
            <tr><th>No.</th><th style="width: 30%">Full name</th><th style="width: 18%">Position</th><th style="width: 18%">Contact number</th><th>Signature</th></tr>
        </thead>
        <tbody>
            @foreach ($members as $member)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->position }}</td>
                    <td>{{ $member->contact_number }}</td>
                    <td></td>
                </tr>
            @endforeach
            @for ($i = 1; $i <= $blankRows; $i++)
                <tr><td class="num">{{ $members->count() + $i }}</td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    <p class="note">List every current officer and member with a contact number. Add pages as needed. Submit two copies with the application.</p>

    <table class="sign">
        <tr>
            <td><span>Prepared by: Secretary<br><small class="muted">Signature over printed name</small></span></td>
            <td><span>Certified correct: President<br><small class="muted">Signature over printed name</small></span></td>
        </tr>
    </table>
</body>
</html>
