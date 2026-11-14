<x-layouts.app header="Applications" subheader="Every accreditation application your organization has filed.">
    @if (! $organization)
        <section class="card">
            <x-empty-state title="Set up your organization profile first." icon="user-circle">
                <x-slot:action><a href="{{ route('cso.profile.edit') }}" class="btn-primary">Set up profile</a></x-slot:action>
            </x-empty-state>
        </section>
    @else
        <x-slot:actions>
            @php $inProgress = $applications->firstWhere('status', 'draft'); @endphp
            <a href="{{ route('cso.application') }}" class="btn-primary">
                <x-phosphor-clipboard-text class="h-4 w-4" aria-hidden="true" />
                {{ $inProgress ? 'Continue your application' : ($applications->isEmpty() ? 'Apply for accreditation' : 'File a new application') }}
            </a>
        </x-slot:actions>

        <section class="card">
            @if ($applications->isEmpty())
                <x-empty-state title="No applications filed yet." icon="clipboard-text">
                    Apply for accreditation to appear in the public directory.
                </x-empty-state>
            @else
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Your organization's applications</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head pl-6">Application</th>
                                <th scope="col" class="table-head">Filed</th>
                                <th scope="col" class="table-head">Status</th>
                                <th scope="col" class="table-head pr-6">Sangguniang Bayan stage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr class="table-row">
                                    <td class="table-cell pl-6">
                                        @if ($application->status === 'draft')
                                            <a href="{{ route('cso.application') }}" class="entity-link capitalize">{{ $application->type }} application</a>
                                            <span class="block text-xs text-muted">Being prepared · continue where you left off</span>
                                        @else
                                            <a href="{{ route('cso.applications.show', $application) }}" class="entity-link capitalize">{{ $application->type }} application</a>
                                        @endif
                                    </td>
                                    <td class="table-date" data-label="Filed">{{ $application->submitted_at?->format('d M Y') ?? 'Not yet' }}</td>
                                    <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                    <td class="table-cell pr-6"><x-sb-stepper :stage="$application->sb_stage" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    @endif
</x-layouts.app>
