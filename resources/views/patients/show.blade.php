@extends('layouts.app')
@section('title', $patient->full_name . ' - Patient Profile')

@section('content')
<!-- Page Header -->
<div class="mb-4">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.patients.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>Patients</a>
    </h6>
</div>

<!-- Patient Header Card -->
<div class="card">
    <div class="row align-items-end">
        <div class="col-xl-9 col-lg-8">
            <div class="d-sm-flex align-items-center position-relative z-0 overflow-hidden p-3">
                <img src="{{ URL::asset('build/img/icons/shape-01.svg') }}" alt="" class="z-n1 position-absolute end-0 top-0 d-none d-lg-flex">
                <span class="avatar avatar-xxxl rounded me-3 flex-shrink-0 {{ $patient->avatar ? '' : 'bg-primary text-white' }}">
                    @if($patient->avatar)
                        <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded">
                    @else
                        <span class="fs-24 fw-bold">{{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}</span>
                    @endif
                </span>
                <div>
                    <p class="text-primary mb-1 fw-medium">{{ $patient->patient_number }}</p>
                    <h5 class="mb-1"><span class="fw-bold">{{ $patient->full_name }}</span></h5>
                    <p class="mb-3">{{ $patient->address ? $patient->address . ', ' : '' }}{{ $patient->city ?? '' }}{{ $patient->region ? ', ' . $patient->region : '' }}</p>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-phone me-1 text-dark"></i>{{ $patient->phone }}</p>
                        @if($patient->email)
                        <span class="text-light">|</span>
                        <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-mail me-1 text-dark"></i>{{ $patient->email }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4">
            <div class="p-3 text-lg-end">
                <div class="mb-3">
                    @if($patient->status === 'active')
                        <span class="badge badge-soft-success fs-13 px-3 py-2">Active</span>
                    @elseif($patient->status === 'inactive')
                        <span class="badge badge-soft-warning fs-13 px-3 py-2">Inactive</span>
                    @else
                        <span class="badge badge-soft-dark fs-13 px-3 py-2">Deceased</span>
                    @endif
                </div>
                @can('patients.edit')
                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-primary btn-md"><i class="ti ti-edit me-1"></i>Edit Patient</a>
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- Info Cards Row -->
<div class="row">
    <!-- About Card -->
    <div class="col-xl-5 d-flex">
        <div class="card shadow-sm flex-fill w-100">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-user-star me-1"></i>About</h5>
            </div>
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-calendar-event fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Date of Birth</h6>
                                <p class="mb-0">{{ $patient->date_of_birth->format('d M Y') }} ({{ $patient->age }} yrs)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-droplet fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Blood Group</h6>
                                <p class="mb-0">{{ $patient->blood_group?->label() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-gender-male fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Gender</h6>
                                <p class="mb-0">{{ $patient->gender?->label() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-heart fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Marital Status</h6>
                                <p class="mb-0">{{ $patient->marital_status?->label() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-briefcase fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Occupation</h6>
                                <p class="mb-0">{{ $patient->occupation ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-map-pin fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Region</h6>
                                <p class="mb-0">{{ $patient->region ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Identification & Emergency Card -->
    <div class="col-xl-7 d-flex">
        <div class="card shadow-sm flex-fill w-100">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-id me-1"></i>Identification & Emergency</h5>
            </div>
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-id-badge-2 fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Ghana Card</h6>
                                <p class="mb-0">{{ $patient->ghana_card_number ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-map-pin-code fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Digital Address</h6>
                                <p class="mb-0">{{ $patient->digital_address ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-urgent fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Emergency Contact</h6>
                                <p class="mb-0">{{ $patient->emergency_contact_name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-phone-call fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Emergency Phone</h6>
                                <p class="mb-0">{{ $patient->emergency_contact_phone ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Medical Notes -->
@if($patient->allergies || $patient->chronic_conditions)
<div class="row">
    @if($patient->allergies)
    <div class="col-md-6 d-flex">
        <div class="card shadow-sm flex-fill">
            <div class="card-header">
                <h5 class="fw-bold mb-0 text-danger"><i class="ti ti-alert-triangle me-1"></i>Allergies</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $patient->allergies }}</p>
            </div>
        </div>
    </div>
    @endif
    @if($patient->chronic_conditions)
    <div class="col-md-6 d-flex">
        <div class="card shadow-sm flex-fill">
            <div class="card-header">
                <h5 class="fw-bold mb-0 text-warning"><i class="ti ti-heartbeat me-1"></i>Chronic Conditions</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $patient->chronic_conditions }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Tabs -->
<ul class="nav nav-tabs nav-bordered mb-3">
    <li class="nav-item">
        <a href="#visits" data-bs-toggle="tab" class="nav-link active bg-transparent"><span>Visit History</span></a>
    </li>
    <li class="nav-item">
        <a href="#registration-info" data-bs-toggle="tab" class="nav-link bg-transparent"><span>Registration Info</span></a>
    </li>
</ul>

<div class="tab-content">
    <!-- Visit History Tab -->
    <div class="tab-pane show active" id="visits">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                <p>No visits recorded yet.</p>
                <small class="text-muted">Visits will appear here once created in Phase 3.</small>
            </div>
        </div>
    </div>

    <!-- Registration Info Tab -->
    <div class="tab-pane" id="registration-info">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Patient Number</h6>
                        <p>{{ $patient->patient_number }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Registered By</h6>
                        <p>{{ $patient->registeredBy?->full_name ?? 'System' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Registration Date</h6>
                        <p>{{ $patient->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Last Updated</h6>
                        <p>{{ $patient->updated_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Secondary Phone</h6>
                        <p>{{ $patient->phone_secondary ?? '—' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Emergency Relationship</h6>
                        <p>{{ $patient->emergency_contact_relationship ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
