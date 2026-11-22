@props(['steps'])

{{--
    The accreditation path to explore (Accreditation page): pick a step on the left and the right
    shows what happens, who acts, and what you receive, with Previous and Next. A rail fills to the
    chosen step. On phones the same list opens each step in place, one at a time. Server-rendered
    with the first step open, so it reads fully without JavaScript.
--}}
@php $count = count($steps); @endphp

<div x-data="processExplorer({{ $count }})" {{ $attributes->merge(['class' => 'grid gap-8 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:gap-12']) }}>
    {{-- The steps. --}}
    <div class="relative lg:self-start">
        <span class="absolute bottom-6 left-5 top-6 w-px bg-line-strong" aria-hidden="true"></span>
        <span class="absolute left-5 top-6 w-px bg-brand-700 transition-[height] duration-slow ease-out-strong" aria-hidden="true"
              :style="`height: calc((100% - 3rem) * ${shown / {{ max($count - 1, 1) }}})`"></span>
        <ol class="relative space-y-2">
            @foreach ($steps as $i => $step)
                <li>
                    <button type="button" id="step-{{ $i }}-button" @click="choose({{ $i }})" @keydown="keys($event, {{ $i }})"
                            :aria-expanded="(active === {{ $i }}).toString()" aria-controls="step-{{ $i }}-detail"
                            class="group grid w-full grid-cols-[2.5rem_minmax(0,1fr)_auto] items-center gap-x-4 rounded-md py-2 pr-3 text-left transition-colors duration-fast ease-out-strong hover:bg-brand-50 focus-visible:outline-focus"
                            :class="shown === {{ $i }} && 'lg:bg-brand-50'">
                        <span data-on="{{ $i === 0 ? 'true' : 'false' }}" :data-on="(shown >= {{ $i }}).toString()"
                              class="relative flex h-10 w-10 items-center justify-center rounded-full border-2 border-brand-200 bg-paper font-semibold tabular-nums text-brand-700 ring-4 ring-paper transition-colors duration-base
                                     data-[on=true]:border-brand-800 data-[on=true]:bg-brand-800 data-[on=true]:text-paper">{{ $i + 1 }}</span>
                        <span class="min-w-0">
                            <span class="block font-serif text-lg font-bold leading-snug text-brand-900">{{ $step['title'] }}</span>
                            <span class="block text-sm text-muted">{{ $step['who'] ?? '' }}</span>
                        </span>
                        <x-phosphor-caret-down class="h-4 w-4 text-brand-500 transition-transform duration-base lg:hidden" ::class="active === {{ $i }} && 'rotate-180'" aria-hidden="true" />
                        <x-phosphor-caret-right class="hidden h-4 w-4 text-brand-500 transition-transform duration-base group-hover:translate-x-1 lg:block" aria-hidden="true" />
                    </button>
                    {{-- Phones: the step opens in place. --}}
                    <div id="step-{{ $i }}-detail" x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif
                         x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="-translate-y-1 opacity-0"
                         class="pb-4 pl-12 pr-2 lg:hidden">
                        @include('components.partials.process-step', ['step' => $step])
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Wide screens: the chosen step in full. --}}
    <div class="hidden lg:block">
        <div class="sticky top-24 rounded-lg border border-line bg-paper p-8 shadow-panel" aria-live="polite">
            @foreach ($steps as $i => $step)
                <section x-show="shown === {{ $i }}" @if ($i > 0) x-cloak @endif aria-label="Step {{ $i + 1 }}: {{ $step['title'] }}"
                         x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="translate-y-1 opacity-0">
                    <p class="text-sm font-semibold tabular-nums text-brand-600">Phase {{ $i + 1 }} of {{ $count }}</p>
                    <h3 class="mt-1 font-serif text-3xl font-bold leading-tight text-brand-900">{{ $step['title'] }}</h3>
                    <div class="mt-4">@include('components.partials.process-step', ['step' => $step, 'large' => true])</div>
                    <div class="mt-8 flex items-center gap-3 border-t border-line pt-6">
                        @if ($i > 0)
                            <button type="button" @click="choose({{ $i - 1 }})" class="btn-ghost">
                                <x-phosphor-arrow-left class="h-4 w-4" aria-hidden="true" />Previous phase
                            </button>
                        @endif
                        @if ($i < $count - 1)
                            <button type="button" @click="choose({{ $i + 1 }})" class="btn-secondary ml-auto">
                                Next: {{ $steps[$i + 1]['title'] }} <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                            </button>
                        @else
                            <a href="{{ route('register') }}" class="btn-primary ml-auto px-6">Start an application</a>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
