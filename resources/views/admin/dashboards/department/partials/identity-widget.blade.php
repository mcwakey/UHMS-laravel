{{-- Operational-intelligence widget — one per department type. Status + level +
     supporting metric lines, all derived from already-computed KPI values. --}}
@if(!empty($identity_widget))
@php $w = $identity_widget; @endphp
<div class="card border-0 shadow-sm mb-3 department-identity-widget">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar avatar-lg rounded-2 bg-{{ $w['variant'] }}-subtle text-{{ $w['variant'] }} flex-shrink-0">
            <i class="ti {{ $w['icon'] ?? 'ti-activity' }} fs-26"></i>
        </span>
        <div class="flex-fill min-w-0">
            <div class="d-flex align-items-baseline justify-content-between gap-2">
                <span class="text-muted small text-uppercase fw-semibold">{{ $w['title'] }}</span>
                <span class="h5 fw-bold mb-0 text-{{ $w['variant'] }}">{{ $w['value'] }}</span>
            </div>
            @if(!is_null($w['level'] ?? null))
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-{{ $w['variant'] }}" role="progressbar" style="width: {{ (int) $w['level'] }}%"></div>
                </div>
            @endif
            @if(!empty($w['metrics']))
                <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                    @foreach($w['metrics'] as $metric)
                        <span class="d-inline-flex align-items-center gap-1"><i class="ti ti-point-filled text-{{ $w['variant'] }} fs-10"></i>{{ $metric['text'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endif
