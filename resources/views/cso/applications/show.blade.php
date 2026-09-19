<x-layouts.cso :header="Str::headline($application->type) . ' application'"
               subheader="Filed {{ $application->submitted_at?->format('d F Y') ?? 'not yet' }}">
    <div class="space-y-6">
        <section class="panel p-5">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-semibold text-ink">Current status</h2>
                <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
            </div>

            <div class="mt-4">
                <p class="text-sm font-medium text-muted">Sangguniang Bayan reading stage</p>
                <div class="mt-2"><x-sb-stepper :stage="$application->sb_stage" /></div>
            </div>

            @if ($application->status === 'rejected' && $application->rejection_reason)
                <div class="mt-4 rounded-sm border border-danger-600 bg-danger-100 px-4 py-3">
                    <p class="text-sm font-semibold text-danger-600">What needs correcting</p>
                    <p class="mt-1 text-sm text-danger-600">{{ $application->rejection_reason }}</p>
                </div>
                <a href="{{ route('cso.applications.create') }}" class="btn-primary mt-4">File a corrected application</a>
            @endif

            @if ($application->accreditation)
                <div class="mt-4 rounded-sm border border-success-600 bg-success-100 px-4 py-3">
                    <p class="text-sm font-semibold text-success-600">Accreditation issued</p>
                    <p class="mt-1 text-sm text-success-600">
                        Valid until {{ $application->accreditation->expires_at->format('d F Y') }}. Reference code
                        <span class="font-mono">{{ $application->accreditation->verification_code }}</span>.
                    </p>
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="border-b border-line px-5 py-3">
                <h2 class="font-semibold text-ink">Documents submitted</h2>
            </div>

            @if ($application->documents->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-muted">No documents attached.</p>
            @else
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
                                <td class="table-cell font-medium">
                                    {{ config("document_types.{$document->document_type}", Str::headline($document->document_type)) }}
                                </td>
                                <td class="table-cell">
                                    <a href="{{ route('documents.download', $document) }}"
                                       class="text-navy-700 hover:underline">{{ $document->original_filename }}</a>
                                </td>
                                <td class="table-cell font-mono tabular-nums text-muted">
                                    {{ $document->expires_at?->format('d M Y') ?? '--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.cso>
