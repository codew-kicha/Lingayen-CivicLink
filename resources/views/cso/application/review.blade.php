{{-- Step 6: everything at a glance, with a way back to each step, then submit to the Desk Office. --}}
@php
    $form = $draft->form_data ?? [];
    $saved = $draft->documents->keyBy('document_type');
@endphp

<x-application-frame :draft="$draft" :step="$step" :done="$done">
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div class="space-y-6">
            <section class="card" aria-labelledby="form-title">
                <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                    <h2 id="form-title" class="card-title">Accreditation form</h2>
                    <a href="{{ route('cso.application.step', 'form') }}" class="ml-auto text-sm font-semibold text-primary hover:underline">Edit</a>
                </div>
                <dl class="grid gap-x-6 gap-y-4 p-6 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Type' => $draft->type === 'new' ? 'New application' : 'Renewal',
                        'Organization' => $form['organization_name'] ?? '',
                        'Representative' => $form['representative_name'] ?? '',
                        'Contact number' => $form['contact_number'] ?? '',
                        'Place of operation' => $form['place_of_operation'] ?? '',
                        'Certified by' => $form['certified_name'] ?? '',
                    ] as $label => $text)
                        <div>
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="font-semibold text-ink">{{ $text }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="border-t border-line px-6 py-4">
                    <a href="{{ route('cso.application.preview') }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">
                        <x-phosphor-file-pdf class="h-4 w-4" aria-hidden="true" />Preview the completed form
                    </a>
                </div>
            </section>

            <section class="card" aria-labelledby="members-title">
                <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                    <h2 id="members-title" class="card-title">Members <span class="font-normal text-muted">({{ $members->count() }})</span></h2>
                    <a href="{{ route('cso.application.step', 'members') }}" class="ml-auto text-sm font-semibold text-primary hover:underline">Edit</a>
                </div>
                <ul class="divide-y divide-line px-6">
                    @foreach ($members->take(8) as $member)
                        <li class="flex gap-3 py-2 text-sm">
                            <span class="text-ink">{{ $member->name }}</span>
                            <span class="ml-auto text-muted">{{ $member->position }}</span>
                        </li>
                    @endforeach
                    @if ($members->count() > 8)
                        <li class="py-2 text-sm text-muted">and {{ $members->count() - 8 }} more</li>
                    @endif
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card" aria-labelledby="docs-title">
                <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                    <h2 id="docs-title" class="card-title">Requirements</h2>
                    <a href="{{ route('cso.application.step', 'requirements') }}" class="ml-auto text-sm font-semibold text-primary hover:underline">Edit</a>
                </div>
                <ul class="divide-y divide-line px-6">
                    @foreach (config('document_types') as $key => $type)
                        @continue(! empty($type['new_only']) && $draft->type !== 'new')
                        <li class="flex items-start gap-3 py-3 text-sm">
                            @if (! empty($type['made_online']) || $saved->has($key))
                                <x-phosphor-check-circle-fill class="mt-hair h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                            @else
                                <x-phosphor-circle class="mt-hair h-5 w-5 shrink-0 text-muted" aria-hidden="true" />
                            @endif
                            <span class="min-w-0">
                                <span class="block font-semibold text-ink">{{ $type['label'] }}</span>
                                <span class="block text-muted">
                                    @if (! empty($type['made_online']))
                                        Made from this application when you submit
                                    @elseif ($saved->has($key))
                                        {{ $saved[$key]->original_filename }}
                                    @else
                                        Not provided (optional)
                                    @endif
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <form method="POST" action="{{ route('cso.application.submit') }}" class="card space-y-4 p-6">
                @csrf
                <h2 class="card-title">Send to the Desk Office</h2>
                <p class="text-sm text-muted">
                    The Desk Office reviews your application, then it goes through the Sangguniang Bayan's readings. You can follow
                    each stage here and you are notified of every change. You can't edit it once it is sent.
                </p>
                <button type="submit" class="btn-primary w-full">
                    <x-phosphor-paper-plane-tilt class="h-4 w-4" aria-hidden="true" />Submit application
                </button>
            </form>
        </div>
    </div>
</x-application-frame>
