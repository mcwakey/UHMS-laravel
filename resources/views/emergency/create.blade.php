@extends('layouts.app')
@section('title', 'New Emergency Case')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">New Emergency Case</h4>
        <p class="text-muted mb-0">Register a known patient or start care immediately for an unidentified patient.</p>
    </div>
    <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">Back to Board</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please correct the highlighted emergency intake details.</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.emergency.cases.store') }}">
    @csrf
    @if(!empty($existingVisit))
        <input type="hidden" name="visit_id" value="{{ $existingVisit->id }}">
        <div class="alert alert-info d-flex align-items-start gap-2">
            <div>
                <div class="fw-semibold">Emergency Session will be created for this visit.</div>
                <div class="small">{{ $existingVisit->visit_number }} - {{ $existingVisit->patient?->full_name }}. Billing remains on the same visit invoice.</div>
            </div>
        </div>
    @endif
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Patient Identity</h5>
                </div>
                <div class="card-body">
                    @if(!empty($existingVisit))
                        <input type="hidden" name="patient_id" value="{{ $existingVisit->patient_id }}">
                        <div class="mb-3">
                            <label class="form-label">Existing Patient</label>
                            <div class="form-control-plaintext fw-medium">
                                {{ $existingVisit->patient?->patient_number }} - {{ $existingVisit->patient?->full_name }}
                            </div>
                        </div>
                    @else
                        @include('patients.partials.patient-search-select', [
                            'id' => 'emergencyPatientSearch',
                            'name' => 'patient_id',
                            'label' => 'Find Existing Patient',
                            'emptyOption' => 'Create temporary emergency patient',
                            'selected' => $selectedPatient ?? null,
                        ])
                        <small class="text-muted d-block mb-3">Leave blank when the patient is unknown or cannot be identified yet — a temporary emergency folder will be created.</small>
                    @endif

                    <div id="emergencyTemporaryDetails" class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-2">Temporary Patient Details</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Display Name</label>
                                <input type="text" class="form-control" name="temporary_display_name" value="{{ old('temporary_display_name') }}" placeholder="Unknown Male Adult">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="temporary_gender">
                                    <option value="">Unknown</option>
                                    <option value="male" @selected(old('temporary_gender') === 'male')>Male</option>
                                    <option value="female" @selected(old('temporary_gender') === 'female')>Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estimated Age</label>
                                <input type="number" min="0" max="120" class="form-control" name="estimated_age" value="{{ old('estimated_age') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Temporary Reason</label>
                                <textarea class="form-control" name="temporary_reason" rows="2" placeholder="Unconscious, accident victim, no relatives present">{{ old('temporary_reason') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Arrival and Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Arrival Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="arrival_mode" required>
                                @foreach([
                                    'WALK_IN' => 'Walk-in',
                                    'AMBULANCE' => 'Ambulance',
                                    'POLICE' => 'Police',
                                    'FAMILY_BROUGHT' => 'Family brought',
                                    'REFERRAL' => 'Referral',
                                    'TRANSFER_FROM_OPD' => 'Transfer from OPD',
                                    'TRANSFER_FROM_WARD' => 'Transfer from ward',
                                    'UNKNOWN' => 'Unknown',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('arrival_mode') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Arrival Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="arrival_time" value="{{ old('arrival_time', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brought By</label>
                            <input type="text" class="form-control" name="brought_by" value="{{ old('brought_by') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Source</label>
                            <input type="text" class="form-control" name="source" value="{{ old('source') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Referral Facility</label>
                            <input type="text" class="form-control" name="referral_facility" value="{{ old('referral_facility') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Chief Complaint</label>
                            <textarea class="form-control" name="chief_complaint" rows="2">{{ old('chief_complaint') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Initial Condition</label>
                            <textarea class="form-control" name="initial_condition" rows="2">{{ old('initial_condition') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bay</label>
                            <select class="form-select" name="emergency_bay_id">
                                <option value="">Assign later</option>
                                @foreach($bays as $bay)
                                    <option value="{{ $bay->id }}" @selected(old('emergency_bay_id') == $bay->id)>{{ $bay->name }} ({{ str_replace('_', ' ', $bay->bay_type) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doctor</label>
                            <select class="form-select" name="assigned_doctor_id">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_doctor_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nurse</label>
                            <select class="form-select" name="assigned_nurse_id">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('assigned_nurse_id') == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary" type="submit">Create Emergency Case</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @include('patients.partials.patient-search-select-scripts', ['id' => 'emergencyPatientSearch'])
    <script>
        // When a known patient is chosen, soften the temporary-patient block;
        // restore it when the selection is cleared (unknown patient flow).
        window.emergencyPatientSearchOnSelect = function () {
            var box = document.getElementById('emergencyTemporaryDetails');
            if (box) { box.classList.add('opacity-50'); }
        };
        window.emergencyPatientSearchOnClear = function () {
            var box = document.getElementById('emergencyTemporaryDetails');
            if (box) { box.classList.remove('opacity-50'); }
        };
    </script>
@endpush
