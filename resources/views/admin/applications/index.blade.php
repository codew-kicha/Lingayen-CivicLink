<x-layouts.admin header="Applications" subheader="Accreditation applications filed by CSOs, newest first.">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input w-48">
                <option value="">All statuses</option>
                @foreach (['submitted', 'under_review', 'approved', 'rejected', 'draft'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ Str::headline($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="stage" class="field-label">SB stage</label>
            <select id="stage" name="stage" class="field-input w-48">
                <option value="">All stages</option>
                @foreach (['not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed'] as $stage)
                    <option value="{{ $stage }}" @selected(request('stage') === $stage)>
                        {{ Str::headline($stage) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        @if (request()->hasAny(['status', 'stage']))
            <a href="{{ route('admin.applications.index') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <section class="panel-flat overflow-hidden">
        @if ($applications->isEmpty())
            <p class="px-4 py-14 text-center text-sm text-muted">
                No applications match these filters.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">Accreditation applications</caption>
                    <thead class="sticky top-0 bg-paper">
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Type</th>
                            <th scope="col" class="table-head">Channel</th>
                            <th scope="col" class="table-head">Status</th>
                            <th scope="col" class="table-head">SB stage</th>
                            <th scope="col" class="table-head">Filed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.applications.show', $application) }}"
                                       class="font-medium text-navy-700 hover:underline">
                                        {{ $application->organization->name }}
                                    </a>
                                    <p class="text-xs text-muted">Brgy. {{ $application->organization->barangay }}</p>
                                    @if ($application->flagged_documents_count)
                                        <span class="badge-warning mt-1">{{ $application->flagged_documents_count }} {{ Str::plural('document', $application->flagged_documents_count) }} to check</span>
                                    @endif
                                </td>
                                <td class="table-cell capitalize">{{ $application->type }}</td>
                                <td class="table-cell capitalize text-muted">{{ $application->submission_channel }}</td>
                                <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                <td class="table-cell text-muted">{{ Str::headline($application->sb_stage) }}</td>
                                <td class="table-cell font-mono tabular-nums text-muted">
                                    {{ $application->submitted_at?->format('d M Y') ?? '--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin>
