<div class="card border-secondary shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar bg-secondary text-white rounded-circle"><i class="ti ti-plug-off"></i></span>
            <div>
                <div class="fw-semibold">{{ $title ?? __('dashboards.department.module_unavailable') }}</div>
                <div class="text-muted small">{{ $message ?? __('dashboards.department.chart_unavailable') }}</div>
            </div>
        </div>
    </div>
</div>
