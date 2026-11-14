@props(['rows', 'empty', 'status' => false])

{{--
    The organizations behind a bar on the analytics page (a sector or a barangay), with their figures
    for the period. $rows are ReportData::organizationRows() entries; `status` adds whether each is
    accredited, for lists that include organizations without an accreditation.
--}}
@if ($rows->isEmpty())
    <p class="px-4 py-6 text-center text-sm text-muted">{{ $empty }}</p>
@else
    <div class="table-wrap">
        <table class="w-full">
            <thead>
                <tr>
                    <th scope="col" class="table-head">Organization</th>
                    <th scope="col" class="table-head text-right">Activities</th>
                    <th scope="col" class="table-head text-right">Self / LGU</th>
                    <th scope="col" class="table-head text-right">Reached</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="table-row">
                        <td class="table-cell">
                            <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link">{{ $row['name'] }}</a>
                            @if ($status && ! $row['accredited'])
                                <span class="badge-neutral ml-1">Not accredited</span>
                            @endif
                        </td>
                        <td class="table-num">{{ $row['verified'] }}</td>
                        <td class="table-num">{{ $row['independent'] }} / {{ $row['lgu'] }}</td>
                        <td class="table-num">{{ number_format($row['reach']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
