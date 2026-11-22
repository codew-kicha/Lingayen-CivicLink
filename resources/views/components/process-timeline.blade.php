@props(['steps'])

{{--
    The accreditation path (config('office.process')) as one vertical timeline (Home). The numbers carry
    meaning (the order is fixed), the rail ties the steps, a step's `note` is the one fact not to miss,
    and a step's `outcomes` show as chips. Steps light up as the reader scrolls past them
    (Alpine timelineProgress); everything starts lit, so it reads finished without JavaScript.
--}}
@php $count = count($steps); @endphp

<ol x-data="timelineProgress({{ $count }})" {{ $attributes->merge(['class' => 'relative']) }}>
    @foreach ($steps as $step)
        @php $i = $loop->index; @endphp
        <li class="relative grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-4 pb-8 last:pb-0">
            @unless ($loop->last)
                <span data-on="true" :data-on="(reached > {{ $i + 1 }}).toString()" aria-hidden="true"
                      class="absolute bottom-0 left-5 top-10 w-px bg-line-strong transition-colors duration-slow data-[on=true]:bg-brand-700"></span>
            @endunless
            <span data-on="true" :data-on="(reached > {{ $i }}).toString()"
                  class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 border-brand-200 bg-paper font-semibold tabular-nums text-brand-700 ring-4 ring-paper transition duration-slow ease-out-strong
                         data-[on=true]:border-brand-800 data-[on=true]:bg-brand-800 data-[on=true]:text-paper motion-safe:data-[on=true]:scale-100 motion-safe:scale-90">{{ $i + 1 }}</span>
            <div class="pt-2">
                <h3 class="font-serif text-lg font-bold leading-snug text-brand-900">{{ $step['title'] }}</h3>
                <p class="mt-1 max-w-[56ch] leading-relaxed text-muted">{{ $step['detail'] }}</p>
                @if (! empty($step['outcomes']))
                    <ul class="mt-3 flex flex-wrap items-center gap-2 text-sm font-semibold text-brand-800" aria-label="Possible outcomes">
                        @foreach ($step['outcomes'] as $outcome)
                            <li class="rounded-sm border border-brand-200 bg-brand-50 px-2 py-1">{{ $outcome }}</li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($step['note']))
                    <p class="mt-3 inline-flex items-center gap-2 rounded-sm bg-accent-100 px-2 py-1 text-sm font-semibold text-brand-900">
                        <x-phosphor-info class="h-4 w-4 shrink-0" aria-hidden="true" />{{ $step['note'] }}
                    </p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
