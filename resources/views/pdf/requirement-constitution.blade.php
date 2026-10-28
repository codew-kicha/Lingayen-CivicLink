{{-- Requirement (c): a sample constitution and by-laws to adapt (new applications only). It is a
     guide to the usual articles, not a legal template; each organization writes its own. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Constitution and By-Laws (sample format)</title>
    @include('pdf.styles')
    <style>
        .center { text-align: center; }
        .guide { border: 1px solid #b45309; color: #92400e; background: #fffbeb; padding: 2mm 3mm; font-size: 8.5pt; margin-bottom: 5mm; }
        h2 { border-bottom: none; margin: 5mm 0 1.5mm; }
        p { line-height: 1.55; margin: 0 0 2mm; }
        .blank { border-bottom: 1px solid #6b7280; display: inline-block; min-width: 55mm; }
    </style>
</head>
<body>
    @php $name = $organization?->name; @endphp

    <p class="guide">Sample format to help you prepare this requirement. Replace the blanks and adapt every article to how your organization actually works, then have it adopted by your members and signed by your officers.</p>

    <div class="head center">
        <h1>Constitution and By-Laws</h1>
        <p>of <strong>{{ $name ?? '____________________________________' }}</strong></p>
        <p class="muted">Barangay {{ $organization?->barangay ?? '____________' }}, Lingayen, Pangasinan</p>
    </div>

    <h2>Article I. Name and Office</h2>
    <p>Section 1. The name of the organization is <span class="blank">{{ $name }}</span>, hereinafter called the "Organization".</p>
    <p>Section 2. Its principal office is in Barangay <span class="blank">{{ $organization?->barangay }}</span>, Lingayen, Pangasinan.</p>

    <h2>Article II. Purpose and Objectives</h2>
    <p>Section 1. The Organization is a non-stock, non-profit civil society organization that aims to:</p>
    <p>a) <span class="blank" style="min-width: 150mm">&nbsp;</span></p>
    <p>b) <span class="blank" style="min-width: 150mm">&nbsp;</span></p>
    <p>c) <span class="blank" style="min-width: 150mm">&nbsp;</span></p>

    <h2>Article III. Membership</h2>
    <p>Section 1. Qualifications. Any resident of <span class="blank">&nbsp;</span> who is at least ____ years old and agrees to these by-laws may become a member.</p>
    <p>Section 2. Rights and duties of members: to vote and be voted upon, to attend meetings, and to pay dues of ₱______ per ______.</p>
    <p>Section 3. Membership ends by resignation, death, or removal for cause by a two-thirds vote of the members present.</p>

    <h2>Article IV. Officers and Board</h2>
    <p>Section 1. The officers are the President, Vice President, Secretary, Treasurer, Auditor and <span class="blank">&nbsp;</span>.</p>
    <p>Section 2. Officers are elected by the members for a term of ____ year(s) and may be re-elected.</p>
    <p>Section 3. Duties: the President presides and represents the Organization; the Secretary keeps the minutes and records; the Treasurer keeps the funds and books of account; the Auditor examines them.</p>

    <h2>Article V. Meetings</h2>
    <p>Section 1. A general assembly is held at least once a year; regular meetings every <span class="blank">&nbsp;</span>.</p>
    <p>Section 2. A majority of the members constitutes a quorum.</p>

    <h2>Article VI. Funds</h2>
    <p>Section 1. Funds come from dues, contributions, donations and projects, and are used only for the Organization's purposes.</p>
    <p>Section 2. The Treasurer reports the financial condition at every general assembly.</p>

    <h2>Article VII. Amendments</h2>
    <p>Section 1. This Constitution and By-Laws may be amended by a two-thirds vote of the members present at a meeting called for that purpose.</p>

    <h2>Article VIII. Adoption</h2>
    <p>Adopted by the members on <span class="blank">&nbsp;</span> at <span class="blank">&nbsp;</span>.</p>

    <table class="sign">
        <tr>
            <td><span>President<br><small class="muted">Signature over printed name</small></span></td>
            <td><span>Secretary<br><small class="muted">Signature over printed name</small></span></td>
        </tr>
    </table>
</body>
</html>
