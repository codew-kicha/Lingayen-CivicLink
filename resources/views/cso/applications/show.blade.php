<x-layouts.app :header="Str::headline($application->type) . ' application'"
               subheader="Filed {{ $application->submitted_at?->format('d F Y') ?? 'not yet' }}">
    <div class="space-y-6">
        <section class="panel p-6">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-semibold text-ink">Current status</h2>
                <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
            </div>

            {{-- Where it stands: Application, Validation, Committee hearing, Approval (client's process). --}}
            @if ($application->status !== 'rejected')
                <x-phase-stepper :application="$application" class="mt-4" />
            @endif

            @if ($application->status === 'requirements_pending')
                <div class="mt-4 flex flex-wrap items-center gap-3 rounded-md border border-warning/40 bg-warning-subtle px-4 py-3">
                    <div class="min-w-0 flex-1 basis-64 text-sm">
                        <p class="font-semibold text-warning">
                            {{ $application->requirements_note ? 'The Desk Office returned your requirements' : 'Your application form was approved. Upload your requirements' }}
                        </p>
                        <p class="mt-1 text-ink">
                            {{ $application->requirements_note ?: ($application->fee_waived ? 'Your requirements can now be submitted; there is no fee for your application.' : 'The official receipt and the other requirements can now be submitted.').' Once they are validated, your application goes to the Sangguniang Bayan committee for its hearing.' }}
                        </p>
                    </div>
                    <a href="{{ route('cso.application.requirements.show') }}" class="btn-primary">Upload requirements</a>
                </div>
            @elseif ($application->status === 'submitted')
                <p class="mt-4 flex items-start gap-2 rounded-md border border-line bg-canvas px-4 py-3 text-sm text-muted">
                    <x-phosphor-lock-simple class="mt-hair h-4 w-4 shrink-0" aria-hidden="true" />
                    The Desk Office is reviewing your application form and member list. The other requirements{{ $application->fee_waived ? '' : ', such as the official receipt,' }}
                    open once it is approved; you will be notified.
                </p>
            @endif

            @if ($application->status !== 'draft')
                <p class="mt-4 flex items-center gap-2 text-sm text-muted">
                    <x-phosphor-receipt class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>Accreditation fee: <span class="text-ink">{{ $application->feeNote() }}</span></span>
                </p>
            @endif

            @if ($application->hearings->isNotEmpty())
                <div class="mt-4">
                    <p class="text-sm font-semibold text-muted">Committee hearings</p>
                    <x-hearing-history :hearings="$application->hearings" class="mt-2" />
                </div>
            @elseif ($application->status === 'under_review')
                <p class="mt-4 rounded-md border border-line bg-canvas px-4 py-3 text-sm text-muted">
                    Your application was validated and forwarded to the Sangguniang Bayan committee. You will be told the outcome of its hearing.
                </p>
            @endif

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
