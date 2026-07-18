{{--
    Shared vitals-recording card for the admission view.
    Params:
        $admission (required)
        $icon (optional) — header icon class, defaults to heartbeat
--}}
@php
    $lastVital = $admission->visit->vitals->sortByDesc('recorded_at')->first();
@endphp
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0"><i class="ti {{ $icon ?? 'ti-heartbeat' }} me-1"></i>{{ __('admissions.record_vitals') }}</h5>
        @if($lastVital)
        <small class="text-muted">{{ __('admissions.last_recorded') }}: <strong>{{ $lastVital->recorded_at->diffForHumans() }}</strong> &middot; {{ $lastVital->recordedBy->name ?? '—' }}</small>
        @endif
    </div>
    <div class="card-body">
        @if($admission->status->value === 'admitted')
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.vitals.store', $admission) }}">
            @csrf
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.bp_systolic') }}</label><div class="input-group input-group-sm"><input type="number" name="blood_pressure_systolic" class="form-control" placeholder="120" min="0" max="300"><span class="input-group-text">mmHg</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.bp_diastolic') }}</label><div class="input-group input-group-sm"><input type="number" name="blood_pressure_diastolic" class="form-control" placeholder="80" min="0" max="200"><span class="input-group-text">mmHg</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.heart_rate') }}</label><div class="input-group input-group-sm"><input type="number" name="heart_rate" class="form-control" placeholder="72" min="0" max="300"><span class="input-group-text">bpm</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.temperature') }}</label><div class="input-group input-group-sm"><input type="number" name="temperature" class="form-control" placeholder="36.6" step="0.1" min="30" max="45"><span class="input-group-text">°C</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.resp_rate') }}</label><div class="input-group input-group-sm"><input type="number" name="respiratory_rate" class="form-control" placeholder="16" min="0" max="60"><span class="input-group-text">/min</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.spo2_col') }}</label><div class="input-group input-group-sm"><input type="number" name="spo2" class="form-control" placeholder="98" step="0.1" min="0" max="100"><span class="input-group-text">%</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.weight') }}</label><div class="input-group input-group-sm"><input type="number" name="weight" class="form-control" placeholder="70" step="0.1" min="0" max="500"><span class="input-group-text">kg</span></div></div>
                <div class="col-6 col-md-3"><label class="form-label small">{{ __('admissions.blood_sugar') }}</label><div class="input-group input-group-sm"><input type="number" name="blood_sugar" class="form-control" placeholder="5.0" step="0.1" min="0"><span class="input-group-text">mmol/L</span></div></div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4"><label class="form-label small">{{ __('admissions.recorded_at') }}</label><input type="datetime-local" name="recorded_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div class="col-md-8"><label class="form-label small">{{ __('admissions.notes') }}</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('admissions.observations_ph') }}"></div>
            </div>
            <div class="text-end"><button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_vitals') }}</button></div>
        </form>
        @else
        <p class="text-muted mb-0">{{ __('admissions.vitals_disabled') }}</p>
        @endif
    </div>
</div>
