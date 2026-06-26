<div class="card shadow-sm mb-3">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-list-details me-1"></i>{{ $services['title'] ?? __('dashboards.department.department_services') }}</h6></div>
    <div class="card-body p-0">
        @if(empty($services['rows']))
            @include('admin.dashboards.department.partials.empty-card', ['message' => $services['empty'] ?? __('dashboards.department.no_department_services'), 'icon' => 'ti-list-details'])
        @else
            <div class="list-group list-group-flush">
                @foreach($services['rows'] as $service)
                <div class="list-group-item d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-medium">{{ $service['name'] }}</div>
                        @if(!empty($service['code']))<small class="text-muted">{{ $service['code'] }}</small>@endif
                    </div>
                    <div class="text-end">
                        @if($services['can_view_prices'] ?? false)
                            <span class="fw-bold">₵{{ number_format((float) $service['price'], 2) }}</span>
                        @else
                            <span class="badge bg-light text-dark">{{ __('dashboards.department.price_hidden') }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
