<x-layouts.admin :header="$application->organization->name"
                 subheader="{{ Str::headline($application->type) }} application filed {{ $application->submitted_at?->format('d F Y') ?? 'not yet' }}">
    <x-slot:actions>
        <a href="{{ route('admin.applications.index') }}" class="btn-secondary">Back to queue</a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="space-y-6">
            <section class="panel-flat p-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-semibold text-ink">Review status</h2>
                    <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-medium text-muted">Sangguniang Bayan reading stage</p>
                    <div class="mt-2"><x-sb-stepper :stage="$application->sb_stage" /></div>
                </div>

                @if ($application->status === 'rejected' && $application->rejection_reason)
                    <div class="mt-4 rounded-sm border border-danger-600 bg-danger-100 px-4 py-3">
                        <p class="text-sm font-semibold text-danger-600">Reason for rejection</p>
                        <p class="mt-1 text-sm text-danger-600">{{ $application->rejection_reason }}</p>
                    </div>
                @endif

                @if ($application->reviewedBy)
                    <p class="mt-4 text-sm text-muted">
                        Reviewed by {{ $application->reviewedBy->name }}
                        on {{ $application->reviewed_at?->format('d F Y') }}
                    </p>
                @endif
            </section>

            <section class="panel-flat p-5">
                <h2 class="font-semibold text-ink">Organization details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted">Sector</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $application->organization->sector }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Barangay</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $application->organization->barangay }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-muted">Advocacy</dt>
                        <dd class="mt-0.5 max-w-[68ch] text-sm text-ink">
                            {{ $application->organization->advocacy ?: 'Not provided' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-muted">Organizational structure</dt>
                        <dd class="mt-0.5 max-w-[68ch] whitespace-pre-line text-sm text-ink">
                            {{ $application->organization->org_chart ?: 'Not provided' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="panel-flat">
                <div class="border-b border-line px-5 py-3">
                    <h2 class="font-semibold text-ink">Submitted documents</h2>
                    <p class="mt-0.5 text-sm text-muted">
                        {{ $application->documents->whereIn('document_type', \App\Models\Document::requiredTypes())->count() }} of {{ count(\App\Models\Document::requiredTypes()) }} required documents uploaded
                    </p>
                </div>

                @if ($application->documents->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-muted">No documents uploaded yet.</p>
                @else
                    <table class="w-full">
                        <caption class="sr-only">Documents submitted with this application</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head">Requirement</th>
                                <th scope="col" class="table-head">File</th>
                                <th scope="col" class="table-head">Expires</th>
                                <th scope="col" class="table-head">Pre-check</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($application->documents as $document)
                                <tr class="table-row">
                                    <td class="table-cell font-medium">
                                        {{ \App\Models\Document::label($document->document_type) }}
                                    </td>
                                    <td class="table-cell">
                                        <a href="{{ route('documents.download', $document) }}"
                                           class="text-navy-700 hover:underline">{{ $document->original_filename }}</a>
                                    </td>
                                    <td class="table-cell font-mono tabular-nums text-muted">
                                        {{ $document->expires_at?->format('d M Y') ?? '--' }}
                                    </td>
                                    <td class="table-cell"><x-ocr-badge :document="$document" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="panel-flat p-5">
                <h2 class="font-semibold text-ink">Officers and members</h2>
                <p class="mt-0.5 text-sm text-muted">Visible to PESO administrators only.</p>

                @if ($application->organization->members->isEmpty())
                    <p class="mt-4 text-sm text-muted">No members recorded.</p>
                @else
                    <ul class="mt-4 divide-y divide-line border-y border-line">
                        @foreach ($application->organization->members as $member)
                            <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-2.5">
                                <span class="text-sm font-medium text-ink">{{ $member->name }}</span>
                                <span class="text-sm text-muted">{{ $member->position }}</span>
                                @if ($member->contact_number)
                                    <span class="ml-auto font-mono text-sm tabular-nums text-muted">{{ $member->contact_number }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            @can('review', $application)
                @if (in_array($application->status, ['submitted', 'under_review'], true))
                    <section class="panel-flat p-5">
                        <h2 class="font-semibold text-ink">Advance the review</h2>

                        <form method="POST" action="{{ route('admin.applications.stage', $application) }}" class="mt-4">
                            @csrf
                            @method('PATCH')
                            <label for="sb_stage" class="field-label">Sangguniang Bayan stage</label>
                            <select id="sb_stage" name="sb_stage" class="field-input">
                                @foreach (['not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed'] as $stage)
                                    <option value="{{ $stage }}" @selected($application->sb_stage === $stage)>
                                        {{ Str::headline($stage) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-secondary mt-3 w-full">Update stage</button>
                        </form>

                        <hr class="my-5 border-line">

                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                            @csrf
                            <p class="text-sm text-muted">
                                Approving issues an accreditation valid for three years and lists the
                                organization in the public directory.
                            </p>
                            <button type="submit" class="btn-primary mt-3 w-full">Approve application</button>
                        </form>

                        <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="mt-5">
                            @csrf
                            <label for="rejection_reason" class="field-label">Reason for rejection</label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3"
                                      class="field-input @error('rejection_reason') field-input-error @enderror"
                                      @error('rejection_reason') aria-invalid="true" aria-describedby="rejection_reason-error" @enderror
                                      placeholder="Explain what the organization needs to correct"></textarea>
                            @error('rejection_reason')
                                <p id="rejection_reason-error" class="field-error">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="btn-danger mt-3 w-full">Reject application</button>
                        </form>
                    </section>
                @else
                    <section class="panel-flat p-5">
                        <h2 class="font-semibold text-ink">Review closed</h2>
                        <p class="mt-2 text-sm text-muted">
                            This application is {{ Str::lower(Str::headline($application->status)) }} and no longer
                            accepts review actions.
                        </p>
                    </section>
                @endif
            @endcan

            <section class="panel-flat p-5">
                <h2 class="font-semibold text-ink">Filing details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3">
                        <dt class="text-muted">Channel</dt>
                        <dd class="ml-auto capitalize text-ink">{{ $application->submission_channel }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-muted">Filed by</dt>
                        <dd class="ml-auto text-ink">{{ $application->submittedBy?->name ?? 'Not recorded' }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="text-muted">Filed on</dt>
                        <dd class="ml-auto font-mono tabular-nums text-ink">
                            {{ $application->submitted_at?->format('d M Y') ?? '--' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</x-layouts.admin>
