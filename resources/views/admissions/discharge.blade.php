@extends('layouts.app')
@section('title', 'Discharge Patient — ' . $admission->patient->full_name)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Discharge Patient</h4>
    </div>
    <div class="text-end">
        <a href="{{ route('admin.admissions.show', $admission) }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>Back to Admission</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0"><i class="ti ti-logout me-1"></i>Discharge Form</h5>
            </div>
            <div class="card-body">
                <!-- Patient Summary -->
                <div class="alert alert-info d-flex align-items-start mb-4">
                    <i class="ti ti-info-circle fs-4 me-2 mt-1"></i>
                    <div>
                        <strong>{{ $admission->patient->full_name }}</strong> ({{ $admission->patient->patient_number }})<br>
                        <small>Admission: {{ $admission->admission_number }} | Ward: {{ $admission->bed->ward->name }} | Bed: {{ $admission->bed->bed_number }}</small><br>
                        <small>Admitted: {{ $admission->admission_date->format('d M Y, H:i') }} | Length of Stay: {{ $admission->length_of_stay }} day(s)</small>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.admissions.process-discharge', $admission) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Discharge Summary <span class="text-danger">*</span></label>
                        <textarea name="discharge_summary" class="form-control" rows="5" required placeholder="Summary of treatment, outcomes, and condition at discharge...">{{ old('discharge_summary') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Discharge Instructions</label>
                        <textarea name="discharge_instructions" class="form-control" rows="4" placeholder="Follow-up appointments, medications to continue, dietary restrictions, activity limitations...">{{ old('discharge_instructions') }}</textarea>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('admin.admissions.show', $admission) }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to discharge this patient? The bed will be freed up.')">
                            <i class="ti ti-logout me-1"></i>Discharge Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Admission Summary -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Admission Summary</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Admission #</td>
                        <td class="fw-medium">{{ $admission->admission_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Admitted On</td>
                        <td>{{ $admission->admission_date->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Admitted By</td>
                        <td>{{ $admission->admittedBy->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Days</td>
                        <td>{{ $admission->length_of_stay }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Ward Rounds</td>
                        <td>{{ $admission->wardRounds->count() }}</td>
                    </tr>
                </table></div>

                @if($admission->admitting_diagnosis)
                <hr>
                <h6 class="text-muted mb-1">Admitting Diagnosis</h6>
                <p class="mb-0">{{ $admission->admitting_diagnosis }}</p>
                @endif
            </div>
        </div>

        <!-- Estimated Charges -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-cash me-1"></i>Estimated Bed Charges</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Daily Rate</td>
                        <td class="fw-medium">GH₵ {{ number_format($admission->bed->daily_rate, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Days</td>
                        <td>{{ max(1, $admission->length_of_stay) }}</td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold">Total Estimate</td>
                        <td class="fw-bold text-primary">GH₵ {{ number_format($admission->bed->daily_rate * max(1, $admission->length_of_stay), 2) }}</td>
                    </tr>
                </table></div>
            </div>
        </div>
    </div>
</div>
@endsection
