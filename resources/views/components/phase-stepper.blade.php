@props(['application', 'note' => true])

{{--
    Where an application stands in the client's four phases (Nov 2026): Application, Validation,
    Committee hearing, Approval. Done phases are ticked, the current one is marked, and the line
    under it says where it stands inside that phase. Used on the admin and the CSO pages alike.
--}}
@php
    $phases = \App\Models\ApplicationModel::PHASES;
    $keys = array_keys($phases);
    $at = ($phase = $application->phase()) ? array_search($phase, $keys, true) : null;
    $approved = $application->status === 'approved';
@endphp

<div {{ $attributes }}>
    <ol class="grid grid-cols-2 gap-2 sm:grid-cols-4" aria-label="Application phases">
        @foreach ($phases as $key => $label)
            @php
                $i = $loop->index;
                $state = $approved || ($at !== null && $i < $at) ? 'done' : ($i === $at ? 'now' : 'next');
            @endphp
            <li @if ($state === 'now') aria-current="step" @endif
                @class(['flex items-center gap-2 rounded-md border px-3 py-2 text-sm',
                        'border-line bg-paper' => $state === 'done',
                        'border-primary bg-primary-subtle' => $state === 'now',
                        'border-line bg-canvas' => $state === 'next'])>
                @if ($state === 'done')
                    <x-phosphor-check-circle-fill class="h-4 w-4 shrink-0 text-success" aria-hidden="true" /><span class="sr-only">Done:</span>
                @elseif ($state === 'now')
                    <x-phosphor-circle-notch class="h-4 w-4 shrink-0 text-primary" aria-hidden="true" /><span class="sr-only">Now:</span>
                @else
                    <x-phosphor-circle class="h-4 w-4 shrink-0 text-muted" aria-hidden="true" />
                @endif
                <span @class(['font-semibold', 'text-ink' => $state !== 'next', 'text-muted' => $state === 'next'])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
    @if ($note && $application->phaseNote())
        <p class="mt-2 text-sm text-muted">{{ $application->phaseNote() }}</p>
    @endif
</div>
