<x-data-table>
    <x-slot:head>
        <tr>
            <th>{{ __('reports.department_comparison.department') }}</th>
            <th>{{ __('reports.department_comparison.type') }}</th>
            <th class="text-end">{{ __('reports.department_comparison.assigned_users') }}</th>
            <th class="text-end">{{ __('reports.department_comparison.services') }}</th>
            <th class="text-end">{{ __('reports.department_comparison.activity') }}</th>
            <th class="text-end">{{ __('reports.department_comparison.pending_work') }}</th>
            <th class="text-end">{{ __('reports.department_comparison.completed_work') }}</th>
            @if($canViewRevenue)
                <th class="text-end">{{ __('reports.department_comparison.revenue') }}</th>
            @endif
            @if($canViewStock)
                <th class="text-end">{{ __('reports.department_comparison.stock_alerts') }}</th>
            @endif
        </tr>
    </x-slot:head>
    @forelse($rows as $row)
        <tr>
            <td class="fw-semibold">{{ $row['department'] }}</td>
            <td><span class="badge bg-light text-dark">{{ $row['type_label'] }}</span></td>
            <td class="text-end">{{ number_format($row['assigned_users']) }}</td>
            <td class="text-end">{{ number_format($row['services']) }}</td>
            <td class="text-end">{{ number_format($row['activity']) }}</td>
            <td class="text-end">{{ number_format($row['pending_work']) }}</td>
            <td class="text-end">{{ number_format($row['completed_work']) }}</td>
            @if($canViewRevenue)
                <td class="text-end">{{ number_format($row['revenue'], 2) }}</td>
            @endif
            @if($canViewStock)
                <td class="text-end">{{ number_format($row['stock_alerts']) }}</td>
            @endif
        </tr>
    @empty
        <tr>
            <td colspan="{{ 7 + ($canViewRevenue ? 1 : 0) + ($canViewStock ? 1 : 0) }}">
                <x-empty-state icon="ti-chart-bar-off" :title="__('reports.no_data')" :message="__('reports.department_comparison.no_departments')" />
            </td>
        </tr>
    @endforelse
</x-data-table>
