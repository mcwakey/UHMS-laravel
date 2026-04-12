@extends('layouts.app')
@section('title', 'New Admission')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">New Admission</h4>
    </div>
    <div class="text-end">
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>Back to Admissions</a>
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
            <div class="card-header">
                <h5 class="card-title mb-0">Admission Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.admissions.store') }}">
                    @csrf

                    @if($visit)
                        <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                        <input type="hidden" name="patient_id" value="{{ $visit->patient_id }}">

                        <!-- Patient Info -->
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="ti ti-user-heart fs-4 me-2"></i>
                            <div>
                                <strong>{{ $visit->patient->full_name }}</strong> ({{ $visit->patient->patient_number }})
                                <span class="ms-2 badge badge-soft-primary">Visit: {{ $visit->visit_number }}</span>
                            </div>
                        </div>
                    @else
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Visit <span class="text-danger">*</span></label>
                                <select name="visit_id" id="visitSelect" class="form-select" required>
                                    <option value="">Select a visit...</option>
                                </select>
                                <small class="text-muted">Only active visits (consulting status) are shown</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Patient</label>
                                <input type="text" id="patientDisplay" class="form-control" readonly placeholder="Auto-filled from visit">
                                <input type="hidden" name="patient_id" id="patientId">
                            </div>
                        </div>
                    @endif

                    <!-- Ward & Bed Selection -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Ward <span class="text-danger">*</span></label>
                            <select id="wardFilter" class="form-select">
                                <option value="">All Wards</option>
                                @foreach($wards as $ward)
                                    <option value="{{ $ward->id }}">{{ $ward->name }} ({{ $ward->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bed <span class="text-danger">*</span></label>
                            <select name="bed_id" id="bedSelect" class="form-select" required>
                                <option value="">Select available bed...</option>
                                @foreach($availableBeds as $bed)
                                    <option value="{{ $bed->id }}" data-ward="{{ $bed->ward_id }}" data-rate="{{ $bed->daily_rate }}" {{ old('bed_id') == $bed->id ? 'selected' : '' }}>
                                        {{ $bed->ward->name }} — {{ $bed->bed_number }} ({{ $bed->bed_type->label() }}) — GH₵{{ number_format($bed->daily_rate, 2) }}/day
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Diagnosis & Dates -->
                    <div class="mb-3">
                        <label class="form-label">Admitting Diagnosis</label>
                        <textarea name="admitting_diagnosis" class="form-control" rows="3" placeholder="Reason for admission...">{{ old('admitting_diagnosis') }}</textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Admission Date</label>
                            <input type="datetime-local" name="admission_date" class="form-control" value="{{ old('admission_date', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Discharge Date</label>
                            <input type="date" name="expected_discharge_date" class="form-control" value="{{ old('expected_discharge_date') }}">
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="{{ route('admin.admissions.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Admit Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        @if($visit)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Patient Info</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Name</td>
                        <td class="fw-medium">{{ $visit->patient->full_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Age / Gender</td>
                        <td>{{ $visit->patient->age }} / {{ $visit->patient->gender->label() }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td>{{ $visit->patient->phone }}</td>
                    </tr>
                    @if($visit->patient->nhis_number)
                    <tr>
                        <td class="text-muted">NHIS</td>
                        <td>{{ $visit->patient->nhis_number }}</td>
                    </tr>
                    @endif
                    @if($visit->patient->allergies)
                    <tr>
                        <td class="text-muted">Allergies</td>
                        <td class="text-danger">{{ $visit->patient->allergies }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Beds Summary</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Ward</th>
                            <th class="text-center">Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wards as $ward)
                        <tr>
                            <td>{{ $ward->name }}</td>
                            <td class="text-center">
                                <span class="badge badge-soft-{{ $availableBeds->where('ward_id', $ward->id)->count() > 0 ? 'success' : 'danger' }}">
                                    {{ $availableBeds->where('ward_id', $ward->id)->count() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Filter beds by ward
    $('#wardFilter').on('change', function() {
        var wardId = $(this).val();
        $('#bedSelect option').each(function() {
            if (!$(this).val()) return;
            if (!wardId || $(this).data('ward') == wardId) {
                $(this).show();
            } else {
                $(this).hide();
                if ($(this).is(':selected')) {
                    $('#bedSelect').val('');
                }
            }
        });
    });
});
</script>
@endpush
