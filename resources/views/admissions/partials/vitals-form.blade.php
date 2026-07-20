@php
    $lastVital = $admission->visit->vitals->sortByDesc('recorded_at')->first();
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0"><i class="ti {{ $icon ?? 'ti-heartbeat' }} me-1"></i>{{ __('admissions.record_vitals') }}</h5>
        @if($lastVital)
            <small class="text-muted">{{ __('admissions.last_recorded') }}: <strong>{{ $lastVital->recorded_at->diffForHumans() }}</strong> &middot; {{ $lastVital->recordedBy->name ?? '-' }}</small>
        @endif
    </div>
    <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <span class="text-muted">{{ $admission->status->value === 'admitted' ? __('admissions.vitals_history') : __('admissions.vitals_disabled') }}</span>
        @if($admission->status->value === 'admitted')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordVitalsModal">
                <i class="ti ti-plus me-1"></i>{{ __('admissions.record_vitals') }}
            </button>
        @endif
    </div>
</div>

@include('admissions.partials.vitals-record-modal')
