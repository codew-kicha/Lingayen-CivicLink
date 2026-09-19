<x-layouts.cso header="Applications" subheader="Every accreditation application your organization has filed.">
    @if (! $organization)
        <section class="panel p-8 text-center">
            <p class="font-medium text-ink">Set up your organization profile first.</p>
            <a href="{{ route('cso.profile.edit') }}" class="btn-primary mt-4">Set up profile</a>
        </section>
    @elseif ($applications->isEmpty())
        <section class="panel p-8 text-center">
            <p class="font-medium text-ink">No applications filed yet.</p>
            <p class="mt-1 text-sm text-muted">Apply for accreditation to appear in the public directory.</p>
            <a href="{{ route('cso.applications.create') }}" class="btn-primary mt-4">Apply for accreditation</a>
        </section>
    @else
        <div class="mb-4 flex justify-end">
            <a href="{{ route('cso.applications.create') }}" class="btn-primary">File a new application</a>
        </div>

        <div class="space-y-3">
            @foreach ($applications as $application)
                <a href="{{ route('cso.applications.show', $application) }}"
                   class="panel block p-5 transition-colors duration-fast ease-out-strong hover:border-navy-300">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="font-semibold capitalize text-ink">{{ $application->type }} application</h2>
                        <span class="ml-auto"><x-status-badge :status="$application->status" /></span>
                    </div>
                    <p class="mt-1 text-sm text-muted">
                        Filed
                        <span class="font-mono tabular-nums">
                            {{ $application->submitted_at?->format('d M Y') ?? 'not yet' }}
                        </span>
                    </p>
                    <div class="mt-3"><x-sb-stepper :stage="$application->sb_stage" /></div>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.cso>
