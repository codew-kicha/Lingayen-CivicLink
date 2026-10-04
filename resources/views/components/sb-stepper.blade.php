@props(['stage'])

@php
    $stages = [
        'not_endorsed' => 'Not endorsed',
        'first_reading' => 'First reading',
        'second_reading' => 'Second reading',
        'third_reading' => 'Third reading',
        'endorsed' => 'Endorsed',
    ];
    $keys = array_keys($stages);
    $currentIndex = array_search($stage, $keys, true);
@endphp

<ol class="flex flex-wrap items-center gap-x-1 gap-y-2 text-sm" aria-label="Sangguniang Bayan reading stage">
    @foreach ($stages as $key => $label)
        @php $index = $loop->index; @endphp
        <li class="flex items-center gap-1">
            <span @if ($index === $currentIndex) aria-current="step" @endif
                  class="rounded-sm px-2 py-1 font-medium
                         @if ($index < $currentIndex) text-success-600
                         @elseif ($index === $currentIndex) text-navy-700 shadow-[inset_0_-2px_0_0_theme(colors.rose.600)]
                         @else text-muted @endif">
                {{ $label }}
            </span>
            @unless ($loop->last)
                <span aria-hidden="true" class="text-line-strong">&rsaquo;</span>
            @endunless
        </li>
    @endforeach
</ol>
