@extends('layouts.app')
@section('title', 'Admission Details — ' . $admission->admission_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            Admission {{ $admission->admission_number }}
            <span class="badge badge-soft-{{ $admission->status->color() }} ms-2">{{ $admission->status->label() }}</span>
        </h4>
    </div>
    <div class="text-end d-flex gap-2">
        @if($admission->status->value === 'admitted')
            @can('ward.discharge')
            <a href="{{ route('admin.admissions.discharge', $admission) }}" class="btn btn-warning btn-md fs-13"><i class="ti ti-logout me-1"></i>Discharge Patient</a>
            @endcan
        @endif
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row">
    <!-- Left Column - Admission Info -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-user-heart me-1"></i>Patient</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <span class="fw-bold text-primary">{{ strtoupper(substr($admission->patient->first_name, 0, 1) . substr($admission->patient->last_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h6 class="mb-0">
                            <a href="{{ route('admin.patients.show', $admission->patient) }}" class="text-decoration-none">
                                {{ $admission->patient->full_name }}
                            </a>
                        </h6>
                        <small class="text-muted">{{ $admission->patient->patient_number }}</small>
                    </div>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">Age / Gender</td>
                        <td>{{ $admission->patient->age }} / {{ $admission->patient->gender->label() }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td>{{ $admission->patient->phone }}</td>
                    </tr>
                    @if($admission->patient->allergies)
                    <tr>
                        <td class="text-muted">Allergies</td>
                        <td class="text-danger fw-medium">{{ $admission->patient->allergies }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Admission Details Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard me-1"></i>Admission Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 45%">Admission #</td>
                        <td class="fw-medium">{{ $admission->admission_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Visit #</td>
                        <td>
                            <a href="{{ route('admin.visits.show', $admission->visit) }}" class="text-decoration-none">
                                {{ $admission->visit->visit_number }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Ward</td>
                        <td>{{ $admission->bed->ward->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Bed</td>
                        <td>{{ $admission->bed->bed_number }} ({{ $admission->bed->bed_type->label() }})</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Daily Rate</td>
                        <td>GH₵ {{ number_format($admission->bed->daily_rate, 2) }}</td>
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
                        <td class="text-muted">Length of Stay</td>
                        <td><span class="badge badge-soft-secondary">{{ $admission->length_of_stay }} day(s)</span></td>
                    </tr>
                    @if($admission->expected_discharge_date)
                    <tr>
                        <td class="text-muted">Expected Discharge</td>
                        <td>{{ $admission->expected_discharge_date->format('d M Y') }}</td>
                    </tr>
                    @endif
                    @if($admission->actual_discharge_date)
                    <tr>
                        <td class="text-muted">Discharged On</td>
                        <td>{{ $admission->actual_discharge_date->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Discharged By</td>
                        <td>{{ $admission->dischargedBy->name ?? '—' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Diagnosis Card -->
        @if($admission->admitting_diagnosis)
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>Admitting Diagnosis</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $admission->admitting_diagnosis }}</p>
            </div>
        </div>
        @endif

        <!-- Discharge Info -->
        @if($admission->status->value === 'discharged')
        <div class="card mb-3 border-success">
            <div class="card-header bg-success bg-opacity-10">
                <h5 class="card-title mb-0 text-success"><i class="ti ti-logout me-1"></i>Discharge Summary</h5>
            </div>
            <div class="card-body">
                @if($admission->discharge_summary)
                    <p>{{ $admission->discharge_summary }}</p>
                @endif
                @if($admission->discharge_instructions)
                    <hr>
                    <h6>Instructions</h6>
                    <p class="mb-0">{{ $admission->discharge_instructions }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column - Ward Rounds -->
    <div class="col-lg-8">
        <!-- Add Ward Round -->
        @if($admission->status->value === 'admitted')
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-plus me-1"></i>Record Ward Round</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.admissions.rounds.store', $admission) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Round Notes <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Observations, vital signs, patient condition...">{{ old('notes') }}</textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="2" placeholder="Medication changes, dietary instructions...">{{ old('instructions') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Round Date/Time</label>
                            <input type="datetime-local" name="round_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Save Round</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Ward Rounds History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>Ward Rounds ({{ $admission->wardRounds->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($admission->wardRounds->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 15%">Date/Time</th>
                                <th style="width: 15%">Recorded By</th>
                                <th>Notes</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admission->wardRounds as $round)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $round->round_date->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $round->round_date->format('H:i') }}</small>
                                </td>
                                <td>{{ $round->recordedBy->name ?? '—' }}</td>
                                <td>{{ $round->notes }}</td>
                                <td>{{ $round->instructions ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-notes-off fs-1 d-block mb-2"></i>
                    No ward rounds recorded yet.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
