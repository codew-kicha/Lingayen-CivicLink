<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CSO accreditation application form</title>
    @include('pdf.styles')
</head>
<body>
    {{-- Built from config('document_types'), config('sectors') and config('office'), so the paper
         form always matches what the online form asks for. --}}
    <div class="head">
        <p class="muted">{{ config('office.office.parent') }}<br>{{ config('office.office.name') }}, {{ config('office.office.address') }}</p>
        <h1>Application for CSO Accreditation</h1>
        <p class="muted">Fill in by hand in block letters. Bring this form and the requirements below to the office. Staff will encode it, and you can track it online afterwards.</p>
    </div>

    <p><span class="box"></span>New accreditation &nbsp;&nbsp;&nbsp; <span class="box"></span>Renewal</p>

    <h2>Organization</h2>
    <table>
        <tr><td style="width: 30%">Name of organization</td><td class="line"></td></tr>
        <tr><td>Barangay</td><td class="line"></td></tr>
        <tr><td>Advocacy or main purpose</td><td class="line"></td></tr>
        <tr><td></td><td class="line"></td></tr>
    </table>
    <p style="margin-top: 3mm">Sector (check one):</p>
    <table>
        @foreach (collect(config('sectors'))->chunk(2) as $pair)
            <tr>
                @foreach ($pair as $sector)
                    <td style="border: none; width: 50%"><span class="box"></span>{{ $sector }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <h2>Representative</h2>
    <table>
        <tr><td style="width: 30%">Full name</td><td class="line"></td></tr>
        <tr><td>Position</td><td class="line"></td></tr>
        <tr><td>Mobile number</td><td class="line"></td></tr>
        <tr><td>Email (for online tracking)</td><td class="line"></td></tr>
    </table>

    <h2>Officers</h2>
    <table>
        <thead><tr><th style="width: 34%">Position</th><th style="width: 40%">Full name</th><th>Contact number</th></tr></thead>
        @foreach (['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', '', ''] as $position)
            <tr><td class="line">{{ $position }}</td><td class="line"></td><td class="line"></td></tr>
        @endforeach
    </table>

    <h2>Requirements checklist (attach one copy of each)</h2>
    <table>
        @foreach (config('document_types') as $key => $type)
            <tr>
                <td style="width: 6mm"><span class="box"></span></td>
                <td>
                    {{ $type['label'] }}{{ $type['optional'] ? ' (only if you have one)' : '' }}
                    <br><span class="muted">{{ config("office.requirement_notes.{$key}") }}</span>
                </td>
            </tr>
        @endforeach
    </table>

    <table class="sign">
        <tr>
            <td><span>Signature over printed name of president</span></td>
            <td><span>Date</span></td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 8mm">
        For office use: received by ____________________ on ____________ &nbsp; Encoded online: <span class="box"></span>
    </p>
</body>
</html>
