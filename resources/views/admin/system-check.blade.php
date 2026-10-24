@php
    $badge = ['pass' => ['badge-success', 'Pass'], 'warn' => ['badge-warning', 'Check'], 'fail' => ['badge-danger', 'Fix']];
    $failed = \App\Support\GoLiveChecks::failed($results);
@endphp

<x-layouts.app header="System check"
               subheader="Run this on the live site after setting it up, and again after any server change. It checks this server's own settings.">
    <div @class(['mb-4 rounded-sm border px-4 py-3 text-sm font-semibold',
                 'border-danger bg-danger-subtle text-danger' => $failed,
                 'border-success bg-success-subtle text-success' => ! $failed])>
        {{ $failed ? 'Not ready to go live: fix every item marked Fix.' : 'Ready to go live. Re-run this after any server or PHP change.' }}
    </div>

    <section class="card" x-data="{
        paths: @js($privatePaths),
        state: 'checking',      // checking | pass | fail
        exposed: [],
        async init() {
            for (const [path, proof] of Object.entries(this.paths)) {
                try {
                    const response = await fetch('{{ url('/') }}/' + path, { cache: 'no-store', redirect: 'manual' });
                    if (response.ok && (await response.text()).includes(proof)) this.exposed.push(path);
                } catch (error) { /* unreachable counts as not exposed */ }
            }
            this.state = this.exposed.length ? 'fail' : 'pass';
        },
    }">
        <ul class="divide-y divide-line">
            @foreach ($results as $check)
                <li class="flex flex-wrap items-start gap-x-4 gap-y-1 px-6 py-4">
                    <span class="{{ $badge[$check['status']][0] }} mt-hair w-16 justify-center">{{ $badge[$check['status']][1] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink">{{ $check['label'] }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $check['detail'] }}</p>
                    </div>
                </li>
            @endforeach

            {{-- Asked by this browser, so it works even where the server can't call itself. --}}
            <li class="flex flex-wrap items-start gap-x-4 gap-y-1 px-6 py-4" aria-live="polite">
                <span class="mt-hair w-16 justify-center"
                      :class="{ 'badge-neutral': state === 'checking', 'badge-success': state === 'pass', 'badge-danger': state === 'fail' }"
                      x-text="{ checking: '…', pass: 'Pass', fail: 'Fix' }[state]">…</span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">Private files</p>
                    <p class="mt-1 text-sm text-muted" x-show="state === 'checking'">Checking whether .env, logs, vendor, composer and .git files can be downloaded…</p>
                    <p class="mt-1 text-sm text-muted" x-show="state === 'pass'" x-cloak>.env, logs, vendor, composer and .git files are not downloadable.</p>
                    <p class="mt-1 text-sm text-danger" x-show="state === 'fail'" x-cloak>
                        Downloadable by anyone: <span class="font-semibold" x-text="exposed.join(', ')"></span>.
                        Point the web root at public/, or upload the deny-all .htaccess (docs/DEPLOYMENT.md). Then replace the app key and mail password, since they may have been seen.
                    </p>
                </div>
            </li>
        </ul>
    </section>

    <p class="mt-4 text-sm text-muted">
        The same checks run from a terminal with <code class="font-mono">php artisan app:go-live-check</code>.
    </p>
</x-layouts.app>
