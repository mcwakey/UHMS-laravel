<div class="card shadow-sm mb-3">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-activity me-1"></i>{{ __('dashboards.department.department_activity') }}</h6></div>
    <div class="card-body p-0">
        @if(empty($activities))
            @include('admin.dashboards.department.partials.empty-card', ['message' => __('dashboards.department.no_department_activity'), 'icon' => 'ti-activity'])
        @else
            <div class="list-group list-group-flush">
                @foreach($activities as $activity)
                    <div class="list-group-item d-flex justify-content-between gap-2">
                        <span>{{ $activity['label'] }}</span>
                        <small class="text-muted">{{ $activity['meta'] ?? '' }}</small>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
