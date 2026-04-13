@extends('layouts.app')

@section('title', 'Schedule Appointment')

@section('content')
<div class="content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Schedule Appointment</h3>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Appointment Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.appointments.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient <span class="text-danger">*</span></label>
                                @if($patient)
                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                    <input type="text" class="form-control" value="{{ $patient->first_name }} {{ $patient->last_name }} ({{ $patient->patient_number }})" readonly>
                                @else
                                    <select name="patient_id" class="form-select select2" id="patientSelect" required>
                                        <option value="">Select Patient...</option>
                                        @foreach($patients as $p)
                                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                                {{ $p->first_name }} {{ $p->last_name }} ({{ $p->patient_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Visit Type <span class="text-danger">*</span></label>
                                <select name="visit_type" class="form-select" required>
                                    @foreach($visitTypes as $type)
                                        <option value="{{ $type->value }}" {{ old('visit_type', 'outpatient') == $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select" id="departmentSelect" required>
                                    <option value="">Select Department...</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doctor</label>
                                <select name="doctor_id" class="form-select" id="doctorSelect">
                                    <option value="">Select Doctor (optional)...</option>
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ old('doctor_id') == $doc->id ? 'selected' : '' }}>
                                            Dr. {{ $doc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '09:00') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                                <small class="text-muted">Defaults to 30 minutes if empty</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Visit</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Brief description of the reason for appointment...">{{ old('reason') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.appointments.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-calendar-plus me-1"></i> Schedule Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Patient Info Sidebar --}}
            @if($patient)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-user me-2"></i>Patient Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Name</td>
                            <td class="fw-medium">{{ $patient->first_name }} {{ $patient->last_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Patient #</td>
                            <td>{{ $patient->patient_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phone</td>
                            <td>{{ $patient->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gender</td>
                            <td>{{ $patient->gender?->label() ?? '—' }}</td>
                        </tr>
                        @if($patient->date_of_birth)
                        <tr>
                            <td class="text-muted">Age</td>
                            <td>{{ $patient->date_of_birth->age }} years</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            {{-- Quick Stats --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>Scheduling Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="ti ti-clock text-muted me-1"></i>
                            Default slot is 30 minutes
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-alert-triangle text-warning me-1"></i>
                            Double-booking will be flagged
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i>
                            Confirm appointment before visit day
                        </li>
                        <li>
                            <i class="ti ti-login text-primary me-1"></i>
                            Check-in creates a new visit automatically
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('#patientSelect').select2({
            placeholder: 'Search patient...',
            allowClear: true
        });
    }
});
</script>
@endsection
