@extends('layouts.app')
@section('title', __('vitals.record_vitals'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Record Vitals</h4>
        <small class="text-muted">Record patient vital signs during triage</small>
    </div>
</div>

<div class="row">
    <!-- Left: Select Patient / Visit -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list me-1"></i>Active Visits</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                    @forelse($triageVisits as $tv)
                    <a href="{{ $workspaceRoutes->route('admin.vitals.create', ['visit_id' => $tv->id]) }}"
                       class="list-group-item list-group-item-action {{ $visit && $visit->id === $tv->id ? 'active' : '' }}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="fw-medium">{{ $tv->patient->full_name }}</span>
                                <small class="d-block text-{{ $visit && $visit->id === $tv->id ? 'white-50' : 'muted' }}">{{ $tv->visit_number }}</small>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <x-status-badge :status="$tv->status" />
                                @if($tv->triage_score)
                                    <x-status-badge :status="$tv->triage_score" style="font-size:.65rem" />
                                @endif
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="text-center text-muted py-4">
                        <small>{{ __('vitals.no_active_visits') }}</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Vitals Form + Triage Assessment -->
    <div class="col-lg-8">
        @if($visit)

        @php
            $isTriage    = $visit->status === \App\Enums\VisitStatus::TRIAGE;
            $triageRecord = $visit->triage;
            $score        = $visit->triage_score ?? $triageRecord?->triage_score;
            $showDeptChooser = request()->boolean('dept_chooser') && $isTriage && $score;
        @endphp

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
                <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Patient Info -->
        <div class="card mb-3 border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">{{ $visit->patient->full_name }}</h5>
                        <small class="text-muted">{{ $visit->patient->patient_number }} &middot; {{ $visit->patient->age }}y &middot; {{ $visit->patient->gender->value }}</small>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <x-status-badge :status="$visit->status" class="px-3 py-2" />
                        @if($visit->triage_score)
                            <x-status-badge :status="$visit->triage_score" icon="ti-activity" />
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Update -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="POST" action="{{ $workspaceRoutes->route('admin.vitals.update-priority', $visit) }}" class="d-flex align-items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label mb-0 text-muted small fw-semibold text-nowrap">
                        <i class="ti ti-flag me-1"></i>Priority:
                    </label>
                    <select name="priority" class="form-select form-select-sm" style="max-width:150px">
                        @foreach(\App\Enums\Priority::cases() as $p)
                            <option value="{{ $p->value }}" {{ $visit->priority === $p ? 'selected' : '' }}>
                                {{ $p->label() }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                </form>
            </div>
        </div>

        @if($visit->latestVitals)
        <div class="alert alert-info py-2 mb-3">
            <i class="ti ti-info-circle me-1"></i><strong>Previous Vitals:</strong>
            BP: {{ $visit->latestVitals->blood_pressure ?? '—' }} |
            HR: {{ $visit->latestVitals->heart_rate ?? '—' }} |
            T: {{ $visit->latestVitals->temperature ?? '—' }}°C |
            SpO₂: {{ $visit->latestVitals->spo2 ?? '—' }}%
            <small class="d-block text-muted">Recorded {{ $visit->latestVitals->recorded_at->diffForHumans() }}</small>
        </div>
        @endif

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             TRIAGE ASSESSMENT RESULT (shown after saving vitals for TRIAGE visits)
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        {{-- ══════════════════════════════════════════════════════════════
             CONSULTATION DEPARTMENT CHOOSER (shown after vitals saved for TRIAGE visits)
        ══════════════════════════════════════════════════════════════ --}}
        @if($showDeptChooser)
        <div class="alert alert-{{ $score->color() }} mb-3" style="border-left:4px solid;">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-activity" style="font-size:1.5rem;"></i>
                <div>
                    <h6 class="fw-bold mb-0">Triage Score: {{ $score->label() }}</h6>
                    <div class="small">Select a consultation department to direct this patient.</div>
                </div>
            </div>
        </div>

        <div class="card border-success mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>Direct Patient to Consultation Department</h6>
            </div>
            <div class="card-body">
                @if(isset($consultationDepts) && $consultationDepts->isNotEmpty())
                <p class="text-muted small mb-3">Choose the consultation department for this patient:</p>
                <div class="row g-2">
                    @foreach($consultationDepts as $dept)
                    <div class="col-12 col-sm-6">
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.vitals.assign-consultation', $visit) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="department_id" value="{{ $dept->id }}">
                            <button type="submit"
                                    class="btn btn-outline-{{ $dept->type?->color() ?? 'primary' }} w-100 text-start"
                                    onclick="return confirm('Send patient to {{ addslashes($dept->name) }}?')">
                                <i class="ti ti-building-hospital me-1"></i>
                                <strong>{{ $dept->name }}</strong>
                                @if($dept->type)
                                    <small class="d-block text-muted">{{ $dept->type->label() }}</small>
                                @endif
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted small">No consultation departments found. Please assign manually.</p>
                <a href="{{ $workspaceRoutes->route('admin.visits.show', $visit) }}" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-external-link me-1"></i>Go to Visit Page
                </a>
                @endif
            </div>
        </div>
        @endif
        {{-- END DEPT CHOOSER --}}

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-heartbeat me-1"></i>Vital Signs</h6>
                @if($isTriage)
                    <span class="badge bg-info">Triage Visit — Score will be computed on save</span>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $workspaceRoutes->route('admin.vitals.store') }}">
                    @csrf
                    <input type="hidden" name="visit_id" value="{{ $visit->id }}">

                    <div class="row g-3">
                        <!-- Blood Pressure -->
                        <div class="col-md-3">
                            <label class="form-label">BP Systolic <small class="text-muted">(mmHg)</small></label>
                            <input type="number" name="blood_pressure_systolic" id="inp_sbp"
                                   class="form-control @error('blood_pressure_systolic') is-invalid @enderror"
                                   placeholder="120" min="50" max="300" value="{{ old('blood_pressure_systolic') }}">
                            @error('blood_pressure_systolic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">BP Diastolic <small class="text-muted">(mmHg)</small></label>
                            <input type="number" name="blood_pressure_diastolic"
                                   class="form-control @error('blood_pressure_diastolic') is-invalid @enderror"
                                   placeholder="80" min="20" max="200" value="{{ old('blood_pressure_diastolic') }}">
                            @error('blood_pressure_diastolic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-md-3">
                            <label class="form-label">Heart Rate <small class="text-muted">(bpm)</small></label>
                            <input type="number" name="heart_rate" id="inp_hr"
                                   class="form-control @error('heart_rate') is-invalid @enderror"
                                   placeholder="72" min="20" max="250" value="{{ old('heart_rate') }}">
                            @error('heart_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Temperature -->
                        <div class="col-md-3">
                            <label class="form-label">Temperature <small class="text-muted">(Â°C)</small></label>
                            <input type="number" name="temperature" id="inp_temp"
                                   class="form-control @error('temperature') is-invalid @enderror"
                                   placeholder="36.5" min="30" max="45" step="0.1" value="{{ old('temperature') }}">
                            @error('temperature') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-md-3">
                            <label class="form-label">Resp. Rate <small class="text-muted">(/min)</small></label>
                            <input type="number" name="respiratory_rate" id="inp_rr"
                                   class="form-control @error('respiratory_rate') is-invalid @enderror"
                                   placeholder="18" min="5" max="60" value="{{ old('respiratory_rate') }}">
                            @error('respiratory_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- SpO2 -->
                        <div class="col-md-3">
                            <label class="form-label">SpOâ‚‚ <small class="text-muted">(%)</small></label>
                            <input type="number" name="spo2" id="inp_spo2"
                                   class="form-control @error('spo2') is-invalid @enderror"
                                   placeholder="98" min="50" max="100" value="{{ old('spo2') }}">
                            @error('spo2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Weight -->
                        <div class="col-md-3">
                            <label class="form-label">Weight <small class="text-muted">(kg)</small></label>
                            <input type="number" name="weight" id="vitalWeight"
                                   class="form-control @error('weight') is-invalid @enderror"
                                   placeholder="70" min="0.5" max="500" step="0.1" value="{{ old('weight') }}">
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Height -->
                        <div class="col-md-3">
                            <label class="form-label">Height <small class="text-muted">(cm)</small></label>
                            <input type="number" name="height" id="vitalHeight"
                                   class="form-control @error('height') is-invalid @enderror"
                                   placeholder="170" min="20" max="300" step="0.1" value="{{ old('height', $visit->patient->height ?? '') }}"> {{-- auto-load from patient record --}}
                            @error('height') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- BMI (auto-calculated) -->
                        <div class="col-md-3">
                            <label class="form-label">BMI <small class="text-muted">(kg/m&sup2;)</small></label>
                            <input type="text" id="vitalBmi" class="form-control bg-light" readonly placeholder="Auto-calculated">
                        </div>

                        <!-- Blood Sugar -->
                        <div class="col-md-3">
                            <label class="form-label">Blood Sugar <small class="text-muted">(mmol/L)</small></label>
                            <input type="number" name="blood_sugar"
                                   class="form-control @error('blood_sugar') is-invalid @enderror"
                                   placeholder="5.5" min="0" max="50" step="0.1" value="{{ old('blood_sugar') }}">
                            @error('blood_sugar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="2" placeholder="{{ __('vitals.additional_observations') }}">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if($isTriage)
                        <!-- Live Triage Score Preview (only for TRIAGE visits) -->
                        <div class="col-12">
                            <div id="triageScorePreview" class="alert alert-secondary d-none py-2">
                                <strong class="small">Estimated Score:</strong>
                                <span id="triageScoreLabel" class="badge ms-1"></span>
                                <span id="triageScoreReason" class="text-muted ms-2 small"></span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-{{ $isTriage ? 'info' : 'primary' }}">
                            <i class="ti ti-check me-1"></i>Save Vitals
                        </button>
                        <a href="{{ $workspaceRoutes->route('admin.vitals.create') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        @else
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-heartbeat fs-1 d-block mb-2"></i>
                <p>{{ __('vitals.select_visit_to_record') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // â”€â”€ BMI auto-calc â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    const weightInput = document.getElementById('vitalWeight');
    const heightInput = document.getElementById('vitalHeight');
    const bmiDisplay  = document.getElementById('vitalBmi');

    function calculateBMI() {
        if (!weightInput || !heightInput || !bmiDisplay) return;
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);
        if (weight > 0 && height > 0) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            let category = bmi < 18.5 ? ' (Underweight)' : bmi < 25 ? ' (Normal)' : bmi < 30 ? ' (Overweight)' : ' (Obese)';
            bmiDisplay.value = bmi + category;
        } else {
            bmiDisplay.value = '';
        }
    }
    if (weightInput) weightInput.addEventListener('input', calculateBMI);
    if (heightInput) heightInput.addEventListener('input', calculateBMI);
    calculateBMI();

    // â”€â”€ Live triage score preview (TRIAGE visits only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    @if($isTriage ?? false)
    const preview  = document.getElementById('triageScorePreview');
    const lblEl    = document.getElementById('triageScoreLabel');
    const reasonEl = document.getElementById('triageScoreReason');

    const SCORES = {
        EMERGENCY: { cls: 'bg-danger',           alert: 'alert-danger',  label: 'EMERGENCY', reason: 'Critical vitals â€” immediate intervention required' },
        URGENT:    { cls: 'bg-warning text-dark', alert: 'alert-warning', label: 'URGENT',    reason: 'Abnormal vitals â€” needs prompt attention' },
        ROUTINE:   { cls: 'bg-success',           alert: 'alert-success', label: 'ROUTINE',   reason: 'Vitals within acceptable range' },
    };

    function computePreview() {
        const sbp  = parseInt(document.getElementById('inp_sbp')?.value);
        const hr   = parseInt(document.getElementById('inp_hr')?.value);
        const temp = parseFloat(document.getElementById('inp_temp')?.value);
        const rr   = parseInt(document.getElementById('inp_rr')?.value);
        const spo2 = parseInt(document.getElementById('inp_spo2')?.value);

        const hasAny = [sbp, hr, temp, rr, spo2].some(v => !isNaN(v));
        if (!hasAny) { preview.classList.add('d-none'); return; }

        let s = SCORES.ROUTINE;
        if ((!isNaN(spo2) && spo2 < 92) || (!isNaN(temp) && (temp < 35.0 || temp > 39.5)) ||
            (!isNaN(hr) && (hr < 40 || hr > 130)) || (!isNaN(rr) && (rr < 8 || rr > 25)) ||
            (!isNaN(sbp) && (sbp < 80 || sbp > 200))) {
            s = SCORES.EMERGENCY;
        } else if ((!isNaN(spo2) && spo2 < 95) || (!isNaN(temp) && (temp < 36.0 || temp > 38.5)) ||
                   (!isNaN(hr) && (hr < 50 || hr > 110)) || (!isNaN(rr) && (rr < 12 || rr > 20)) ||
                   (!isNaN(sbp) && (sbp < 90 || sbp > 180))) {
            s = SCORES.URGENT;
        }

        preview.className = 'alert py-2 ' + s.alert;
        preview.classList.remove('d-none');
        lblEl.className = 'badge ms-1 ' + s.cls;
        lblEl.textContent = s.label;
        reasonEl.textContent = s.reason;
    }

    ['inp_sbp','inp_hr','inp_temp','inp_rr','inp_spo2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', computePreview);
    });
    computePreview();
    @endif

});
</script>
@endpush
