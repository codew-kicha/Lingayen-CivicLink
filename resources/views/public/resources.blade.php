<x-layouts.public title="Resources">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-ink">Resources</h1>
            <p class="mt-3 max-w-[68ch] text-muted">
                News from the PESO office, annual accreditation reports, and answers to the questions
                organizations ask most often.
            </p>
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:px-8">
        <div>
            <section>
                <h2 class="text-xl font-semibold text-ink">News and community updates</h2>

                @if ($posts->isEmpty())
                    <div class="panel mt-5 p-8 text-center">
                        <p class="font-medium text-ink">No updates published yet.</p>
                        <p class="mt-1 text-sm text-muted">PESO announcements will appear here.</p>
                    </div>
                @else
                    <ul class="mt-5 divide-y divide-line border-y border-line">
                        @foreach ($posts as $post)
                            <li class="py-5">
                                <p class="font-mono text-xs uppercase tracking-wide text-muted">
                                    {{ $post->published_at?->format('d M Y') }}
                                </p>
                                <h3 class="mt-1 text-lg font-semibold text-ink">{{ $post->title }}</h3>
                                <p class="mt-2 max-w-[68ch] text-muted">{{ Str::limit(strip_tags($post->body), 220) }}</p>

                                @if ($post->organizations->isNotEmpty())
                                    <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted">
                                        <span>Featuring:</span>
                                        @foreach ($post->organizations as $organization)
                                            <a href="{{ route('directory.show', $organization) }}"
                                               class="rounded-sm bg-surface px-2 py-0.5 text-navy-700 hover:underline">
                                                {{ $organization->name }}
                                            </a>
                                        @endforeach
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8">{{ $posts->links() }}</div>
                @endif
            </section>

            <section class="mt-14">
                <h2 class="text-xl font-semibold text-ink">Frequently asked questions</h2>
                <dl class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($faqs as $faq)
                        <div class="py-4">
                            <dt class="font-semibold text-ink">{{ $faq['question'] }}</dt>
                            <dd class="mt-1.5 max-w-[68ch] text-sm text-muted">{{ $faq['answer'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <aside>
            <h2 class="text-xl font-semibold text-ink">Annual reports</h2>
            @if ($reports->isEmpty())
                <div class="panel mt-5 p-6 text-center">
                    <p class="text-sm font-medium text-ink">No reports posted yet.</p>
                </div>
            @else
                <ul class="mt-5 space-y-2">
                    @foreach ($reports as $report)
                        <li>
                            <a href="{{ route('annual-reports.download', $report) }}"
                               class="panel flex items-baseline gap-3 p-4 transition-colors duration-fast ease-out-strong hover:border-navy-300">
                                <span class="font-mono text-sm tabular-nums text-navy-600">{{ $report->year }}</span>
                                <span class="text-sm font-medium text-ink">{{ $report->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <h2 class="mt-12 text-xl font-semibold text-ink">Regulations</h2>
            <ul class="mt-5 space-y-3 text-sm text-muted">
                <li>RA 7160, Local Government Code of 1991, on the role of NGOs and people's organizations</li>
                <li>DILG Memorandum Circular 2022-083, CSO accreditation for Local Special Bodies</li>
            </ul>
        </aside>
    </div>
</x-layouts.public>
