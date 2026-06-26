<div class="card shadow-sm mb-3">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-packages me-1"></i>{{ $stock_usage['title'] ?? __('dashboards.department.department_stock') }}</h6></div>
    <div class="card-body p-0">
        @if(!empty($stock_usage['restricted']))
            @include('admin.dashboards.department.partials.restricted-card', ['card' => $stock_usage])
        @elseif(empty($stock_usage['rows']))
            @include('admin.dashboards.department.partials.empty-card', ['message' => $stock_usage['empty'] ?? __('dashboards.department.no_stock_usage'), 'icon' => 'ti-packages'])
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($stock_usage['rows'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-end">{{ number_format((float) $row['quantity'], 2) }}</td>
                            @if($stock_usage['can_view_cost'] ?? false)
                                <td class="text-end">₵{{ number_format((float) $row['value'], 2) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
