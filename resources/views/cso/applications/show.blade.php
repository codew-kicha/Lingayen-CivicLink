<x-layouts.app :header="Str::headline($application->type) . ' application'"
               subheader="Filed {{ $application->submitted_at?->format('d F Y') ?? 'not yet' }}">
    <div class="space-y-6">
        <section class="panel p-6">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-semibold text-ink">Current status</h2>
                <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
            </div>

            {{-- Where it stands in the flow: form first, requirements after approval, then the SB. --}}
            @if ($application->status !== 'rejected')
                @php
                    $flow = \App\Models\ApplicationModel::FLOW;
                    $at = array_search($application->status, array_keys($flow), true);
                @endphp
                <ol class="mt-4 grid gap-2 sm:grid-cols-5" aria-label="Application progress">
                    @foreach ($flow as $status => $label)
                        @php $i = $loop->index; @endphp
                        <li @class(['flex items-start gap-2 rounded-md border px-3 py-2 text-xs',
                                    'border-primary bg-primary-subtle' => $i === $at,
                                    'border-line bg-paper' => $i !== $at])
                            @if ($i === $at) aria-current="step" @endif>
                            @if ($at !== false && ($i < $at || $application->status === 'approved'))
                                <x-phosphor-check-circle-fill class="h-4 w-4 shrink-0 text-success" aria-hidden="true" /><span class="sr-only">Done:</span>
                            @elseif ($i === $at)
                                <x-phosphor-circle-notch class="h-4 w-4 shrink-0 text-primary" aria-hidden="true" /><span class="sr-only">Now:</span>
                            @else
                                <x-phosphor-circle class="h-4 w-4 shrink-0 text-muted" aria-hidden="true" />
                            @endif
                            <span class="font-semibold text-ink">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($application->status === 'requirements_pending')
                <div class="mt-4 flex flex-wrap items-center gap-3 rounded-md border border-warning/40 bg-warning-subtle px-4 py-3">
                    <div class="min-w-0 flex-1 basis-64 text-sm">
                        <p class="font-semibold text-warning">
                            {{ $application->requirements_note ? 'The Desk Office returned your requirements' : 'Your application form was approved. Upload your requirements' }}
                        </p>
                        <p class="mt-1 text-ink">
                            {{ $application->requirements_note ?: 'The official receipt and the other requirements can now be submitted. The Sangguniang Bayan takes up your application once they are in.' }}
                        </p>
                    </div>
                    <a href="{{ route('cso.application.requirements.show') }}" class="btn-primary">Upload requirements</a>
                </div>
            @elseif ($application->status === 'submitted')
                <p class="mt-4 flex items-start gap-2 rounded-md border border-line bg-canvas px-4 py-3 text-sm text-muted">
                    <x-phosphor-lock-simple class="mt-hair h-4 w-4 shrink-0" aria-hidden="true" />
                    The Desk Office is reviewing your application form and member list. The other requirements, such as the official
                    receipt, open once it is approved; you will be notified.
                </p>
            @endif

            <div class="mt-4">
                <p class="text-sm font-semibold text-muted">Sangguniang Bayan reading stage</p>
                <div class="mt-2"><x-sb-stepper :stage="$application->sb_stage" /></div>
                <x-reading-history :readings="$application->readings" class="mt-4" />
            </div>

            @if ($application->status === 'rejected' && $application->rejection_reason)
                <div class="mt-4 rounded-sm border border-danger bg-danger-subtle px-4 py-3">
                    <p class="text-sm font-semibold text-danger">What needs correcting</p>
                    <p class="mt-1 text-sm text-danger">{{ $application->rejection_reason }}</p>
                </div>
                <a href="{{ route('cso.application') }}" class="btn-primary mt-4">File a corrected application</a>
            @endif

            @if ($application->accreditation)
                <div class="mt-4 rounded-sm border border-success bg-success-subtle px-4 py-3">
                    <p class="text-sm font-semibold text-success">Accreditation issued</p>
                    <p class="mt-1 text-sm text-success">
                        Valid until {{ $application->accreditation->expires_at->format('d F Y') }}. Reference code
                        <span class="font-mono">{{ $application->accreditation->verification_code }}</span>.
                    </p>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="border-b border-line px-4 py-3">
                <h2 class="font-semibold text-ink">Documents submitted</h2>
            </div>

            @if ($application->documents->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-muted">No documents attached.</p>
            @else
                <div class="table-wrap">
                <table class="w-full">
                    <caption class="sr-only">Documents submitted with this application</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Requirement</th>
                            <th scope="col" class="table-head">File</th>
                            <th scope="col" class="table-head">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($application->documents as $document)
                            <tr class="table-row">
                                <td class="table-cell font-semibold">
                                    {{ \App\Models\Document::label($document->document_type) }}
                                </td>
                                <td class="table-cell">
                                    <a href="{{ route('documents.download', $document) }}" target="_blank" rel="noopener"
                                       class="entity-link">{{ $document->original_filename }}</a>
                                </td>
                                <td class="table-date">
                                    {{ $document->expires_at?->format('d M Y') ?? '--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
