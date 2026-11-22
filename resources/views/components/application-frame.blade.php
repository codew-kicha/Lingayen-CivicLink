@props(['draft', 'step', 'done'])

{{--
    Frame for the application steps (App\Services\ApplicationSteps): the seven steps across the top,
    then the page's content. Steps 1-2 were done at sign-up; 3-5 are the draft's pages; 6, the other
    requirements, stays locked until the Desk Office approves the application form (pre-final
    defense panel); 7 is the Sangguniang Bayan committee hearing, followed on the application page.
    Finished steps and the current one are links; locked ones say why.
--}}
@php
    use App\Services\ApplicationSteps;

    $titles = [
        'members' => 'List your members',
        'form' => 'Fill in the accreditation form',
        'review' => 'Review and submit',
        'requirements' => 'Upload the other requirements',
    ];
    $isDraft = $draft->status === 'draft';
    // Once sent, the draft's pages are done whatever was recorded (paper or older applications).
    $complete = ['account' => true, 'email' => true] + array_merge($done, $isDraft ? [] : ['members' => true, 'form' => true], [
        // Review is "ready", not done, until the application is sent.
        'review' => ! $isDraft,
        'requirements' => in_array($draft->status, ['requirements_submitted', 'under_review', 'approved'], true),
        'hearing' => $draft->status === 'approved',
    ]);
    $reach = $isDraft ? array_search(ApplicationSteps::current($draft), ApplicationSteps::PAGES, true) : -1;
    $total = count(ApplicationSteps::STEPS);
    $locked = [
        'requirements' => $draft->status === 'requirements_pending' ? null : 'Opens once the Desk Office approves your form',
        'hearing' => 'After the requirements are validated',
    ];
@endphp

<x-layouts.app :header="$draft->type === 'renewal' ? 'Renew your accreditation' : 'Apply for accreditation'"
               :subheader="'Step '.ApplicationSteps::number($step).' of '.$total.': '.$titles[$step].'. Your progress is saved as you go.'">

    <nav aria-label="Application steps" class="mb-6">
        <ol class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
            @foreach (ApplicationSteps::STEPS as $key => $label)
                @php
                    $isHere = $key === $step;
                    $page = array_search($key, ApplicationSteps::PAGES, true);
                    $url = match (true) {
                        $isHere => null,
                        $page !== false && $page <= $reach => route('cso.application.step', $key),
                        $key === 'requirements' && $draft->status === 'requirements_pending' => route('cso.application.requirements.show'),
                        default => null,
                    };
                    $lockedWhy = ! ($complete[$key] ?? false) && ! $isHere ? ($locked[$key] ?? null) : null;
                    $classes = Arr::toCssClasses([
                        'flex h-full flex-col gap-1 rounded-md border px-3 py-2 text-xs transition-colors duration-fast ease-out-strong',
                        'border-primary bg-primary-subtle' => $isHere,
                        'border-line bg-paper hover:border-primary/40' => $url,
                        'border-line bg-canvas' => ! $isHere && ! $url,
                    ]);
                @endphp
                <li>
                    @if ($url)
                        <a href="{{ $url }}" class="{{ $classes }}">
                    @else
                        <div class="{{ $classes }}" @if ($isHere) aria-current="step" @endif>
                    @endif
                        <span @class(['flex items-center gap-tight font-semibold tabular-nums', 'text-primary' => $isHere, 'text-muted' => ! $isHere])>
                            @if ($complete[$key] ?? false)
                                <x-phosphor-check-circle-fill class="h-4 w-4 text-success" aria-hidden="true" /><span class="sr-only">Done:</span>
                            @elseif ($lockedWhy)
                                <x-phosphor-lock-simple class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Locked:</span>
                            @endif
                            Step {{ $loop->iteration }}
                        </span>
                        <span class="font-semibold text-ink">{{ $label }}</span>
                        @if ($lockedWhy)
                            <span class="text-muted">{{ $lockedWhy }}</span>
                        @endif
                    @if ($url)
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
