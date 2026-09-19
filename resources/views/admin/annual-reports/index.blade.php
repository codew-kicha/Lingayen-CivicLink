<x-layouts.admin header="Annual reports" subheader="Reports listed here are downloadable from the public Resources page.">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <section class="panel-flat h-fit p-5">
            <h2 class="font-semibold text-ink">Upload a report</h2>
            <p class="mt-0.5 text-sm text-muted">PDF only, up to 10 MB.</p>

            <form method="POST" action="{{ route('admin.annual-reports.store') }}"
                  enctype="multipart/form-data" class="mt-5 space-y-5">
                @csrf

                <div>
                    <label for="title" class="field-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}"
                           class="field-input @error('title') field-input-error @enderror"
                           placeholder="CSO Accreditation and Activity Report" required>
                    @error('title')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="year" class="field-label">Year</label>
                    <input type="number" id="year" name="year" value="{{ old('year', now()->year - 1) }}"
                           min="2000" max="{{ now()->year + 1 }}"
                           class="field-input @error('year') field-input-error @enderror" required>
                    @error('year')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="file" class="field-label">Report file</label>
                    <input type="file" id="file" name="file" accept=".pdf"
                           class="field-input @error('file') field-input-error @enderror" required>
                    @error('file')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full">Publish report</button>
            </form>
        </section>

        <section class="panel-flat overflow-hidden">
            <div class="border-b border-line px-5 py-3">
                <h2 class="font-semibold text-ink">Published reports</h2>
            </div>

            @if ($reports->isEmpty())
                <p class="px-5 py-14 text-center text-sm text-muted">No annual reports published yet.</p>
            @else
                <table class="w-full">
                    <caption class="sr-only">Published annual reports</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Year</th>
                            <th scope="col" class="table-head">Title</th>
                            <th scope="col" class="table-head">Uploaded by</th>
                            <th scope="col" class="table-head"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr class="table-row">
                                <td class="table-cell font-mono tabular-nums">{{ $report->year }}</td>
                                <td class="table-cell">
                                    <a href="{{ route('annual-reports.download', $report) }}"
                                       class="font-medium text-navy-700 hover:underline">{{ $report->title }}</a>
                                </td>
                                <td class="table-cell text-muted">{{ $report->uploadedBy?->name ?? 'Unknown' }}</td>
                                <td class="table-cell text-right">
                                    <form method="POST" action="{{ route('admin.annual-reports.destroy', $report) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost px-3 py-1.5 text-danger-600">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.admin>
