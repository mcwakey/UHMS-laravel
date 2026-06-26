<div class="card border-0 bg-light mb-3">
    <div class="card-body text-center py-4">
        <i class="ti {{ $icon ?? 'ti-chart-bar-off' }} fs-28 text-muted d-block mb-2"></i>
        <div class="fw-semibold">{{ $title ?? __('dashboards.department.no_department_data') }}</div>
        <div class="text-muted small">{{ $message ?? __('dashboards.department.not_configured') }}</div>
    </div>
</div>
