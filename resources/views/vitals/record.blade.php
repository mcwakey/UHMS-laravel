@extends('layouts.app')
@section('title', 'Record Vitals')

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
                    <a href="{{ route('admin.vitals.create', ['visit_id' => $tv->id]) }}"
                       class="list-group-item list-group-item-action {{ $visit && $visit->id === $tv->id ? 'active' : '' }}">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="fw-medium">{{ $tv->patient->full_name }}</span>
                                <small class="d-block text-{{ $visit && $visit->id === $tv->id ? 'white-50' : 'muted' }}">{{ $tv->visit_number }}</small>
                            </div>
                            <span class="badge bg-{{ $tv->status->color() }} align-self-center">{{ $tv->status->label() }}</span>
                        </div>
                    </a>
                    @empty
                    <div class="text-center text-muted py-4">
                        <small>No active visits in triage/waiting.</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Vitals Form -->
    <div class="col-lg-8">
        @if($visit)
        <!-- Patient Info -->
        <div class="card mb-3 border-primary">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">{{ $visit->patient->full_name }}</h5>
                        <small class="text-muted">{{ $visit->patient->patient_number }} &middot; {{ $visit->patient->age }}y &middot; {{ $visit->patient->gender->value }}</small>
                    </div>
                    <span class="badge bg-{{ $visit->status->color() }} px-3 py-2">{{ $visit->status->label() }}</span>
                </div>
            </div>
        </div>

        @if($visit->latestVitals)
        <div class="alert alert-info py-2 mb-3">
            <i class="ti ti-info-circle me-1"></i><strong>Last Vitals:</strong>
            BP: {{ $visit->latestVitals->blood_pressure ?? '—' }} |
            HR: {{ $visit->latestVitals->heart_rate ?? '—' }} |
            T: {{ $visit->latestVitals->temperature ?? '—' }}°C |
            SpO2: {{ $visit->latestVitals->spo2 ?? '—' }}%
            <small class="d-block text-muted">Recorded {{ $visit->latestVitals->recorded_at->diffForHumans() }}</small>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-heartbeat me-1"></i>Vital Signs</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.vitals.store') }}">
                    @csrf
                    <input type="hidden" name="visit_id" value="{{ $visit->id }}">

                    <div class="row g-3">
                        <!-- Blood Pressure -->
                        <div class="col-md-3">
                            <label class="form-label">BP Systolic <small class="text-muted">(mmHg)</small></label>
                            <input type="number" name="blood_pressure_systolic" class="form-control @error('blood_pressure_systolic') is-invalid @enderror"
                                   placeholder="120" min="50" max="300" value="{{ old('blood_pressure_systolic') }}">
                            @error('blood_pressure_systolic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">BP Diastolic <small class="text-muted">(mmHg)</small></label>
                            <input type="number" name="blood_pressure_diastolic" class="form-control @error('blood_pressure_diastolic') is-invalid @enderror"
                                   placeholder="80" min="20" max="200" value="{{ old('blood_pressure_diastolic') }}">
                            @error('blood_pressure_diastolic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-md-3">
                            <label class="form-label">Heart Rate <small class="text-muted">(bpm)</small></label>
                            <input type="number" name="heart_rate" class="form-control @error('heart_rate') is-invalid @enderror"
                                   placeholder="72" min="20" max="250" value="{{ old('heart_rate') }}">
                            @error('heart_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Temperature -->
                        <div class="col-md-3">
                            <label class="form-label">Temperature <small class="text-muted">(°C)</small></label>
                            <input type="number" name="temperature" class="form-control @error('temperature') is-invalid @enderror"
                                   placeholder="36.5" min="30" max="45" step="0.1" value="{{ old('temperature') }}">
                            @error('temperature') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-md-3">
                            <label class="form-label">Resp. Rate <small class="text-muted">(/min)</small></label>
                            <input type="number" name="respiratory_rate" class="form-control @error('respiratory_rate') is-invalid @enderror"
                                   placeholder="18" min="5" max="60" value="{{ old('respiratory_rate') }}">
                            @error('respiratory_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- SpO2 -->
                        <div class="col-md-3">
                            <label class="form-label">SpO2 <small class="text-muted">(%)</small></label>
                            <input type="number" name="spo2" class="form-control @error('spo2') is-invalid @enderror"
                                   placeholder="98" min="50" max="100" value="{{ old('spo2') }}">
                            @error('spo2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Weight -->
                        <div class="col-md-3">
                            <label class="form-label">Weight <small class="text-muted">(kg)</small></label>
                            <input type="number" name="weight" id="vitalWeight" class="form-control @error('weight') is-invalid @enderror"
                                   placeholder="70" min="0.5" max="500" step="0.1" value="{{ old('weight') }}">
                            @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Height -->
                        <div class="col-md-3">
                            <label class="form-label">Height <small class="text-muted">(cm)</small></label>
                            <input type="number" name="height" id="vitalHeight" class="form-control @error('height') is-invalid @enderror"
                                   placeholder="170" min="20" max="300" step="0.1" value="{{ old('height') }}">
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
                            <input type="number" name="blood_sugar" class="form-control @error('blood_sugar') is-invalid @enderror"
                                   placeholder="5.5" min="0" max="50" step="0.1" value="{{ old('blood_sugar') }}">
                            @error('blood_sugar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Additional observations...">{{ old('notes') }}</textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>Save Vitals
                        </button>
                        <a href="{{ route('admin.vitals.create') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-heartbeat fs-1 d-block mb-2"></i>
                <p>Select a visit from the list to record vitals.</p>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const weightInput = document.getElementById('vitalWeight');
    const heightInput = document.getElementById('vitalHeight');
    const bmiDisplay = document.getElementById('vitalBmi');

    function calculateBMI() {
        if (!weightInput || !heightInput || !bmiDisplay) return;
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);
        if (weight > 0 && height > 0) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            let category = '';
            if (bmi < 18.5) category = ' (Underweight)';
            else if (bmi < 25) category = ' (Normal)';
            else if (bmi < 30) category = ' (Overweight)';
            else category = ' (Obese)';
            bmiDisplay.value = bmi + category;
        } else {
            bmiDisplay.value = '';
        }
    }

    if (weightInput) weightInput.addEventListener('input', calculateBMI);
    if (heightInput) heightInput.addEventListener('input', calculateBMI);
    calculateBMI();
});
</script>
@endpush

@endsection
