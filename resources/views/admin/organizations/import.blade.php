<x-layouts.admin header="Import organizations"
                 subheader="Load the office's existing list from a spreadsheet. Imported organizations have no login until you invite a representative.">
    <div class="grid max-w-4xl gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <form method="POST" action="{{ route('admin.organizations.import.store') }}" enctype="multipart/form-data" class="panel space-y-5 p-6">
            @csrf
            <div>
                <label for="file" class="field-label">CSV file</label>
                <input type="file" id="file" name="file" accept=".csv,text/csv" required
                       class="field-input @error('file') field-input-error @enderror">
                <p class="field-hint">In Excel: File, Save As, "CSV UTF-8". Up to 2 MB.</p>
                @error('file')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            @if (session('importErrors'))
                <div role="alert" class="rounded-sm border border-danger-600 bg-danger-100 p-4 text-sm text-danger-600">
                    <p class="font-semibold">Nothing was imported. Fix these rows and upload again:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach (session('importErrors') as $line => $error)
                            <li>Line {{ $line }}: {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">Import</button>
                <a href="{{ route('admin.organizations.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>

        <section class="panel-flat p-6 text-sm">
            <h2 class="font-semibold text-ink">File format</h2>
            <p class="mt-2 text-muted">The first row names the columns: <span class="font-mono text-ink">name, sector, barangay</span>, and optionally <span class="font-mono text-ink">advocacy</span>. Other columns are ignored.</p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-muted">
                <li>Sector must be one of the office's sectors. Capitalisation doesn't matter.</li>
                <li>Barangay must be a Lingayen barangay. "Brgy." in front is fine.</li>
                <li>Names already on file are skipped, so uploading the same file twice is safe.</li>
                <li>If any row has a problem, nothing is imported.</li>
            </ul>
            <a href="{{ route('admin.organizations.import.template') }}" class="btn-secondary mt-4">Download template</a>
        </section>
    </div>
</x-layouts.admin>
