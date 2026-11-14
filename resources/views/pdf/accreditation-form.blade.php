{{-- The Sangguniang Bayan's CY 2026 accreditation form, filled in from the answers given on the
     website (App\Services\ApplicationSteps::formPdf). Same fields as the paper form; the signature line
     carries the representative's online certification. Whether the office also wants a handwritten
     signature is an open question (docs/CLIENT_CONFIRMATIONS.md). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Accreditation Form</title>
    @include('pdf.styles')
    <style>
        .center { text-align: center; }
        .band { background: #e8eef2; padding: 3mm; text-align: center; margin: 5mm 0 7mm; }
        .band h1 { font-size: 16pt; letter-spacing: .5pt; }
        .field { margin: 0 0 5mm; font-size: 10.5pt; }
        .field b { display: inline-block; width: 62mm; }
        .fill { border-bottom: 1px solid #2b2f38; display: inline-block; min-width: 100mm; padding: 0 1mm; }
        .action { border-bottom: 1px solid #6b7280; height: 7mm; margin-bottom: 2mm; }
        ol { margin: 0; padding-left: 5mm; }
        ol li { margin-bottom: 1mm; }
        .tick { font-family: DejaVu Sans, sans-serif; }
        .certify { margin-top: 8mm; border: 1px solid #9ca3af; padding: 3mm; font-size: 9pt; }
        .draft { position: fixed; top: 40%; left: 0; right: 0; text-align: center; font-size: 46pt; color: rgba(74, 26, 34, .08); transform: rotate(-24deg); font-weight: bold; }
    </style>
</head>
<body>
    @php
        // The office is in the Philippines; the app keeps time in UTC.
        $certified = ! empty($form['certified_at']) ? \Illuminate\Support\Carbon::parse($form['certified_at'])->timezone('Asia/Manila') : null;
    @endphp
    @unless ($final)
        <div class="draft">PREVIEW</div>
    @endunless

    <div class="center">
        <p class="muted">Republic of the Philippines</p>
        <p><b>MUNICIPALITY OF LINGAYEN</b></p>
        <p class="muted">Province of Pangasinan</p>
        <p>Office of the Sangguniang Bayan</p>
    </div>

    <div class="band">
        <h1>ACCREDITATION FORM</h1>
        <b>{{ $application->type === 'new' ? 'For New Application (CSO)' : 'For Renewal (CSO)' }}</b>
    </div>

    <p class="field"><b>Name of Organization</b> <span class="fill">{{ $form['organization_name'] ?? '' }}</span></p>
    <p class="field"><b>Name of Representative</b> <span class="fill">{{ $form['representative_name'] ?? '' }}</span></p>
    <p class="field"><b>Contact No. of Representative</b> <span class="fill">{{ $form['contact_number'] ?? '' }}</span></p>
    <p class="field"><b>Place of Operation</b> <span class="fill">{{ $form['place_of_operation'] ?? '' }}</span></p>

    <p class="field"><b>Action taken by the Sangguniang Bayan</b></p>
    <div class="action"></div>
    <div class="action"></div>

    <h2>Requirements submitted</h2>
    <ol type="a">
        @foreach ($requirements as $key => $type)
            <li>{{ $type['label'] }}@if ($type['optional']) (if registered)@endif</li>
        @endforeach
    </ol>

    <div class="certify">
        @if ($certified)
            I certify that the information in this form is true and correct.<br>
            <b>{{ $form['certified_name'] ?? '' }}</b>, representative, certified online on
            {{ $certified->format('d F Y, g:i A') }} (Philippine time) through Lingayen CivicLink.
        @else
            <span class="muted">Not yet certified. The representative certifies this form before submitting the application.</span>
        @endif
    </div>

    <table class="sign">
        <tr>
            <td><span>{{ $form['certified_name'] ?? $form['representative_name'] ?? '' }}<br><small class="muted">Signature over printed name of representative</small></span></td>
            <td><span>{{ $certified?->format('d F Y') }}<br><small class="muted">Date</small></span></td>
        </tr>
    </table>
</body>
</html>
