{{-- KPI stat card. Params: label, value, icon, color, caption, trend(?int), badge(?string) --}}
<div class="card border rounded-2 shadow-sm h-100 mb-0">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <p class="mb-1 text-muted small">{{ $label }}</p>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold mb-0">{{ is_numeric($value) ? number_format((float) $value) : $value }}</h3>
                    @if(($trend ?? null) !== null)
                        <span class="badge badge-soft-{{ $trend >= 0 ? 'success' : 'danger' }} rounded-pill fs-10">{{ $trend >= 0 ? '+' : '' }}{{ $trend }}%</span>
                    @elseif(!empty($badge))
                        <span class="badge badge-soft-{{ $badgeColor ?? 'warning' }} rounded-pill fs-10">{{ $badge }}</span>
                    @endif
                </div>
            </div>
            <span class="avatar avatar-lg bg-{{ $color }} rounded-circle flex-shrink-0"><i class="ti {{ $icon }} fs-24"></i></span>
        </div>
        @if(!empty($caption))
            <p class="mb-0 mt-2 text-muted small">{{ $caption }}</p>
        @endif
    </div>
</div>
