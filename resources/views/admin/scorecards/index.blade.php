<x-layouts.admin header="Performance scorecards"
                 subheader="Equal-weighted across four components. Informational only: scores do not affect renewal decisions.">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.scorecards.recalculate') }}">
            @csrf
            <button type="submit" class="btn-secondary">Recalculate all</button>
        </form>
    </x-slot:actions>

    <section class="panel-flat overflow-hidden">
        @if ($organizations->isEmpty())
            <p class="px-4 py-14 text-center text-sm text-muted">No organizations are registered yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">Performance scores by organization</caption>
                    <thead class="sticky top-0 bg-paper">
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head text-right">Activity frequency</th>
                            <th scope="col" class="table-head text-right">Community reach</th>
                            <th scope="col" class="table-head text-right">Compliance</th>
                            <th scope="col" class="table-head text-right">Document currency</th>
                            <th scope="col" class="table-head text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($organizations as $organization)
                            @php $score = $organization->performanceScores->first(); @endphp
                            <tr class="table-row">
                                <td class="table-cell font-medium">{{ $organization->name }}</td>
                                @if ($score)
                                    <td class="table-num">{{ number_format($score->activity_frequency * 100, 0) }}%</td>
                                    <td class="table-num">{{ number_format($score->community_reach * 100, 0) }}%</td>
                                    <td class="table-num">{{ number_format($score->compliance_timeliness * 100, 0) }}%</td>
                                    <td class="table-num">{{ number_format($score->document_currency * 100, 0) }}%</td>
                                    <td class="table-num font-semibold text-ink">
                                        {{ number_format($score->total_score * 100, 0) }}%
                                    </td>
                                @else
                                    <td class="table-cell text-muted" colspan="5">
                                        Not yet computed
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="mt-4">{{ $organizations->links() }}</div>
</x-layouts.admin>
