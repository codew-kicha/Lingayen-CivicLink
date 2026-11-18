{{--
    Step 5: everything at a glance, with a way back to each step, then submit to the Desk Office. The
    other requirements are shown locked: they are asked for only after the form is approved.
--}}
@php
    $form = $draft->form_data ?? [];
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
                <div class="border-b border-line px-6 py-4">
                    <h2 id="docs-title" class="card-title">Sent now</h2>
                </div>
                <ul class="divide-y divide-line px-6">
                    @foreach (config('document_types') as $key => $type)
                        @continue(empty($type['made_online']))
                        <li class="flex items-start gap-3 py-3 text-sm">
                            <x-phosphor-check-circle-fill class="mt-hair h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block font-semibold text-ink">{{ $type['label'] }}</span>
                                <span class="block text-muted">Made from this application when you submit</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card" aria-labelledby="later-title">
                <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                    <x-phosphor-lock-simple class="h-5 w-5 shrink-0 text-muted" aria-hidden="true" />
                    <h2 id="later-title" class="card-title">After your form is approved</h2>
                </div>
                <p class="px-6 pt-4 text-sm text-muted">
                    Don't pay or upload these yet. Once the Desk Office approves your form you will be notified, and this step opens.
                </p>
                <ul class="divide-y divide-line px-6 pb-2">
                    @foreach ($uploads as $key => $type)
                        <li class="flex items-start gap-3 py-3 text-sm">
                            <x-phosphor-circle class="mt-hair h-5 w-5 shrink-0 text-muted" aria-hidden="true" />
                            <span class="min-w-0">
                                <span class="block font-semibold text-ink">{{ $type['label'] }}</span>
                                @if ($type['optional'])<span class="block text-muted">Optional</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <form method="POST" action="{{ route('cso.application.submit') }}" class="card space-y-4 p-6">
                @csrf
                <h2 class="card-title">Send to the Desk Office</h2>
                <p class="text-sm text-muted">
                    The Desk Office reviews your form and member list first. Once it is approved you upload the other requirements,
                    the office validates them, and the Sangguniang Bayan committee hears the application. You are notified of every change. You can't edit it once it is sent.
                </p>
                <button type="submit" class="btn-primary w-full">
                    <x-phosphor-paper-plane-tilt class="h-4 w-4" aria-hidden="true" />Submit application
                </button>
            </form>
        </div>
    </div>
</x-application-frame>
