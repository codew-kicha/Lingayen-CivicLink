<x-layouts.public title="Resources">
    <x-page-banner title="Resources" photo="events/cso6.jpg" alt="Members of a senior citizens association at a CSO assembly in Lingayen"
                   lede="News from the PESO office, annual accreditation reports, and answers to the questions organizations ask most often." />

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:px-8">
        <div>
            <section id="news" class="scroll-mt-24">
                <h2 class="font-serif text-xl font-bold text-brand-900">News and community updates</h2>

                @if ($posts->isEmpty())
                    <div class="panel mt-6 p-8 text-center">
                        <p class="font-medium text-ink">No updates published yet.</p>
                        <p class="mt-1 text-sm text-muted">PESO announcements will appear here.</p>
                    </div>
                @else
                    <ul class="mt-6 divide-y divide-line border-y border-line">
                        @foreach ($posts as $post)
                            <li class="py-6">
                                <p class="text-sm tabular-nums text-muted">
                                    {{ $post->published_at?->format('d M Y') }}
                                </p>
                                <h3 class="mt-1 text-lg font-semibold text-ink">{{ $post->title }}</h3>
                                <p class="mt-2 max-w-[68ch] text-muted">{{ Str::limit(strip_tags($post->body), 220) }}</p>

                                @if ($post->organizations->isNotEmpty())
                                    <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted">
                                        <span>Featuring:</span>
                                        @foreach ($post->organizations as $organization)
                                            <a href="{{ route('directory.show', $organization) }}"
                                               class="rounded-sm bg-canvas px-2 py-1 text-brand-700 hover:underline">
                                                {{ $organization->name }}
                                            </a>
                                        @endforeach
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6">{{ $posts->links('pagination.public') }}</div>
                @endif
            </section>

            <section class="mt-12">
                <h2 class="font-serif text-xl font-bold text-brand-900">Frequently asked questions</h2>
                {{-- Each question opens its answer (native <details>: keyboard and screen-reader friendly, no script). --}}
                <div class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ($faqs as $faq)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-4 py-4 font-semibold text-ink
                                            transition-colors duration-fast hover:text-brand-700 focus-visible:outline-focus
                                            [&::-webkit-details-marker]:hidden">
                                <span class="flex-1">{{ $faq['question'] }}</span>
                                <x-phosphor-caret-down class="h-5 w-5 shrink-0 text-brand-700 transition-transform duration-base group-open:rotate-180" aria-hidden="true" />
                            </summary>
                            <p class="max-w-[68ch] pb-4 pr-8 text-sm leading-relaxed text-muted">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        </div>

        <aside>
            <h2 class="font-serif text-xl font-bold text-brand-900">Annual reports</h2>
            @if ($reports->isEmpty())
                <div class="panel mt-6 p-6 text-center">
                    <p class="text-sm font-medium text-ink">No reports posted yet.</p>
                </div>
            @else
                <ul class="mt-6 space-y-2">
                    @foreach ($reports as $report)
                        <li>
                            <a href="{{ route('annual-reports.download', $report) }}"
                               class="panel flex items-baseline gap-3 p-4 transition-colors duration-fast ease-out-strong hover:border-brand-300">
                                <span class="text-sm font-semibold tabular-nums text-brand-600">{{ $report->year }}</span>
                                <span class="text-sm font-medium text-ink">{{ $report->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="mt-12 font-serif text-xl font-bold text-brand-900">Regulations</h2>
            <ul class="mt-6 space-y-3 text-sm text-muted">
                <li>RA 7160, Local Government Code of 1991, on the role of NGOs and people's organizations</li>
                <li>DILG Memorandum Circular No. 2025-060 (June 23, 2025), accreditation of CSOs and selection of representatives to Local Special Bodies; it replaced MC 2022-083</li>
            </ul>
        </aside>
    </div>
</x-layouts.public>
