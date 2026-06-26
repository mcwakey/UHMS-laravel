<div class="card border-warning shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar bg-warning text-dark rounded-circle"><i class="ti ti-lock"></i></span>
            <div>
                <div class="fw-semibold">{{ $card['title'] ?? __('dashboards.department.restricted_metric') }}</div>
                <div class="text-muted small">{{ $card['message'] ?? __('dashboards.department.restricted_metric') }}</div>
            </div>
        </div>
    </div>
</div>
