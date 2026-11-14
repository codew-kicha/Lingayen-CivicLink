@props(['draft', 'step', 'done'])

{{--
    Frame for the application steps (App\Services\ApplicationSteps): the six steps across the top (the
    first two were done at sign-up), then the page's content. Finished steps and the current one are
    links; later ones wait until the steps before them are done.
--}}
@php
    use App\Services\ApplicationSteps;

    $titles = [
        'members' => 'List your members',
        'form' => 'Fill in the accreditation form',
        'requirements' => 'Upload the other requirements',
        'review' => 'Review and submit',
    ];
    // Review is "ready", not done, until the application is sent.
    $complete = ['account' => true, 'email' => true] + array_merge($done, ['review' => false]);
    $current = ApplicationSteps::current($draft);
    $reach = array_search($current, ApplicationSteps::PAGES, true);
@endphp

<x-layouts.app :header="$draft->type === 'renewal' ? 'Renew your accreditation' : 'Apply for accreditation'"
               :subheader="'Step '.ApplicationSteps::number($step).' of 6: '.$titles[$step].'. Your progress is saved as you go.'">

    <nav aria-label="Application steps" class="mb-6">
        <ol class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
            @foreach (ApplicationSteps::STEPS as $key => $label)
                @php
                    $isHere = $key === $step;
                    $page = array_search($key, ApplicationSteps::PAGES, true);
                    $link = $page !== false && $page <= $reach && ! $isHere;
                    $classes = Arr::toCssClasses([
                        'flex h-full flex-col gap-1 rounded-md border px-3 py-2 text-xs transition-colors duration-fast ease-out-strong',
                        'border-primary bg-primary-subtle' => $isHere,
                        'border-line bg-paper hover:border-primary/40' => $link,
                        'border-line bg-canvas' => ! $isHere && ! $link,
                    ]);
                @endphp
                <li>
                    @if ($link)
                        <a href="{{ route('cso.application.step', $key) }}" class="{{ $classes }}">
                    @else
                        <div class="{{ $classes }}" @if ($isHere) aria-current="step" @endif>
                    @endif
                        <span @class(['flex items-center gap-tight font-semibold tabular-nums', 'text-primary' => $isHere, 'text-muted' => ! $isHere])>
                            @if ($complete[$key] ?? false)
                                <x-phosphor-check-circle-fill class="h-4 w-4 text-success" aria-hidden="true" /><span class="sr-only">Done:</span>
                            @endif
                            Step {{ $loop->iteration }}
                        </span>
                        <span class="font-semibold text-ink">{{ $label }}</span>
                    @if ($link)
                        </a>
                    @else
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    @error('step')
        <p role="alert" class="mb-6 rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">{{ $message }}</p>
    @enderror

    {{ $slot }}
</x-layouts.app>
