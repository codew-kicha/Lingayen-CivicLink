{{--
    Free filing periods (client, Nov 2026). After each new LGU administration a DILG memorandum opens
    about three months in which accreditation is free. Applications submitted inside a period need no
    official receipt and are accredited for three years; outside every period the fee applies and the
    accreditation lasts one year. A change here affects applications submitted afterwards; an
    application already filed is changed on its review page (Waive the fee / Require the fee).
--}}
@php
    $fees = config('office.fees');
    $years = config('office.validity_years');
@endphp

<x-layouts.app header="Free filing periods" subheader="When accreditation is free, as opened by a DILG memorandum after each new administration.">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <div class="space-y-6">
            <section class="panel-flat p-6" aria-labelledby="rule-title">
                <h2 id="rule-title" class="font-semibold text-ink">How the fee is decided</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3">
                        <x-phosphor-calendar-check class="mt-hair h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                        <div>
                            <dt class="font-semibold text-ink">Submitted inside a free period</dt>
                            <dd class="text-muted">No fee and no official receipt. Accredited for {{ $years['free'] }} years.</dd>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <x-phosphor-receipt class="mt-hair h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />
                        <div>
                            <dt class="font-semibold text-ink">Submitted outside one</dt>
                            <dd class="text-muted">
                                PHP {{ number_format($fees['new']) }} for a new application, PHP {{ number_format($fees['renewal']) }} for a renewal.
                                Accredited for {{ $years['paid'] }} year, renewed yearly.
                            </dd>
                        </div>
                    </div>
                </dl>
                <p class="mt-4 border-t border-line pt-4 text-sm text-muted">
                    Changes apply to applications submitted afterwards. To change one already filed, open it in Applications and use
                    <span class="font-semibold text-ink">Waive the fee</span> or <span class="font-semibold text-ink">Require the fee</span>.
                </p>
            </section>

            <section class="panel-flat p-6" aria-labelledby="add-title">
                <h2 id="add-title" class="font-semibold text-ink">Add a free period</h2>
                <form method="POST" action="{{ route('admin.fee-periods.store') }}" class="mt-6 space-y-6">
                    @csrf
                    @include('admin.fee-periods.fields', ['period' => null, 'prefix' => 'new'])
                    <button type="submit" class="btn-primary w-full">Save period</button>
                </form>
            </section>
        </div>

        <section class="panel-flat h-fit overflow-hidden" aria-labelledby="periods-title">
            <div class="border-b border-line px-4 py-3">
                <h2 id="periods-title" class="font-semibold text-ink">Periods</h2>
            </div>

            @if ($periods->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-muted">
                    No free periods recorded. Every application pays the fee and is accredited for {{ $years['paid'] }} year.
                </p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($periods as $period)
                        @php
                            [$badge, $label] = match ($period->status()) {
                                'open' => ['badge-success', 'Open now'],
                                'upcoming' => ['badge-info', 'Upcoming'],
                                default => ['badge-neutral', 'Ended'],
                            };
                        @endphp
                        <li class="px-4 py-4" x-data="{ editing: @js($errors->any() && (int) old('period_id') === $period->id) }">
                            <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
                                <div class="min-w-0 flex-1 basis-64">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-ink">{{ $period->memorandum }}</span>
                                        <span class="{{ $badge }}">{{ $label }}</span>
                                    </p>
                                    <p class="mt-1 text-sm tabular-nums text-ink">{{ $period->dates() }}</p>
                                    <p class="mt-1 text-xs text-muted">
                                        {{ trans_choice(':count application|:count applications', $period->applicationsCount()) }} submitted within it
                                        · added by {{ $period->createdBy?->name ?? 'the office' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="btn-ghost btn-sm" @click="editing = ! editing" :aria-expanded="editing.toString()"
                                            aria-controls="edit-{{ $period->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.fee-periods.destroy', $period) }}"
                                          onsubmit="return confirm('Remove this free period? Applications already filed keep their fee decision.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm text-danger">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <form id="edit-{{ $period->id }}" method="POST" action="{{ route('admin.fee-periods.update', $period) }}"
                                  x-show="editing" x-cloak class="mt-4 space-y-4 rounded-md border border-line bg-canvas p-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="period_id" value="{{ $period->id }}">
                                @include('admin.fee-periods.fields', ['period' => $period, 'prefix' => 'edit-'.$period->id])
                                <div class="flex gap-3">
                                    <button type="submit" class="btn-primary">Save changes</button>
                                    <button type="button" class="btn-ghost" @click="editing = false">Cancel</button>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
