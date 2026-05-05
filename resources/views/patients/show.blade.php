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
                    <p class="text-primary mb-1 fw-medium">{{ $patient->patient_number }} / {{ $patient->phone_secondary }}</p>
                    <h5 class="mb-1"><span class="fw-bold">{{ $patient->full_name }}</span></h5>
                    <p class="mb-3">{{ $patient->address ? $patient->address . ', ' : '' }}{{ collect([$patient->city, $patient->town])->filter()->implode(', ') }}{{ $patient->region ? ', ' . $patient->region : '' }}</p>
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
                    @php $lastVisitDate = $patient->visits->first()?->visit_date; @endphp
                    @if($lastVisitDate)
                        <span class="text-muted small me-2"><i class="ti ti-calendar-event me-1"></i>Last visit: {{ $lastVisitDate->format('d M Y') }}</span>
                    @endif
                    @if($patient->status === 'active')
                        <span class="badge badge-soft-success fs-13 px-3 py-2">Active</span>
                    @elseif($patient->status === 'inactive')
                        <span class="badge badge-soft-warning fs-13 px-3 py-2">Inactive</span>
                    @else
                        <span class="badge badge-soft-dark fs-13 px-3 py-2">Deceased</span>
                    @endif
                </div>
                <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                    @can('visits.create')
                    <a href="{{ route('admin.visits.create') }}?patient_id={{ $patient->id }}" class="btn btn-success btn-md"><i class="ti ti-plus me-1"></i>New Visit</a>
                    @endcan
                    @can('patients.edit')
                    <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-primary btn-md"><i class="ti ti-edit me-1"></i>Edit Patient</a>
                    @endcan
                </div>
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
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-pray fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Religion</h6>
                                <p class="mb-0">{{ $patient->religion ?? '—' }}</p>
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
                    </div>                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-building-community fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">City / Town</h6>
                                <p class="mb-0">{{ collect([$patient->city, $patient->town])->filter()->implode(' / ') ?: '\u2014' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-home fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Address</h6>
                                <p class="mb-0">{{ $patient->address ?? '\u2014' }}</p>
                            </div>
                        </div>
                    </div>                    @php $primaryContact = $patient->emergencyContacts->where('is_primary', true)->first() ?? $patient->emergencyContacts->first(); @endphp
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-urgent fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Emergency Contact</h6>
                                <p class="mb-0">{{ $primaryContact?->name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-phone-call fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Emergency Phone</h6>
                                <p class="mb-0">{{ $primaryContact?->phone ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    @if($patient->insurances->isNotEmpty())
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-shield-check fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Primary Insurance</h6>
                                <p class="mb-0">{{ $patient->insurances->where('is_primary', true)->first()?->insuranceProvider?->name ?? 'Cash & Carry' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
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
        <a href="#visits" data-bs-toggle="tab" class="nav-link active bg-transparent"><i class="ti ti-calendar-event me-1"></i>Visit History</a>
    </li>
    <li class="nav-item">
        <a href="#insurance" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-shield-check me-1"></i>Insurance <span class="badge bg-primary ms-1">{{ $patient->insurances->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a href="#emergency-contacts" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-urgent me-1"></i>Emergency Contacts</a>
    </li>
    <li class="nav-item">
        <a href="#billing" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-receipt me-1"></i>Billing</a>
    </li>
    <li class="nav-item">
        <a href="#registration-info" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-info-circle me-1"></i>Registration Info</a>
    </li>
    <li class="nav-item">
        <a href="#activity-log" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-history me-1"></i>Activity Log <span class="badge bg-secondary ms-1">{{ $activityLogs->count() }}</span></a>
    </li>
</ul>

<div class="tab-content">
    <!-- Visit History Tab -->
    <div class="tab-pane show active" id="visits">
        {{-- Upcoming Scheduled Visits --}}
        @if($upcomingVisits->isNotEmpty())
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary bg-opacity-10">
                <h6 class="fw-bold mb-0 text-primary"><i class="ti ti-calendar-plus me-1"></i>Upcoming Scheduled Visits</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingVisits as $uv)
                            <tr>
                                <td>{{ $uv->visit_date->format('d M Y') }}</td>
                                <td>{{ $uv->start_time ? \Carbon\Carbon::parse($uv->start_time)->format('h:i A') : '—' }}</td>
                                <td>{{ $uv->currentDepartment?->name ?? '—' }}</td>
                                <td>{{ $uv->assignedDoctor?->full_name ?? '—' }}</td>
                                <td><span class="badge" style="background-color: {{ $uv->status->color() }}">{{ $uv->status->label() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Past Visit History --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Visit History</h6>
                @can('visits.create')
                <a href="{{ route('admin.visits.create') }}?patient_id={{ $patient->id }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>New Visit</a>
                @endcan
            </div>
            @if($patient->visits->isNotEmpty())
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Visit #</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->visits as $visit)
                            <tr>
                                <td><a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium">{{ $visit->visit_number }}</a></td>
                                <td>{{ $visit->visit_date->format('d M Y') }}</td>
                                <td>{{ $visit->visit_type?->label() ?? '—' }}</td>
                                <td>—</td>
                                <td>{{ $visit->assignedDoctor?->full_name ?? '—' }}</td>
                                <td><span class="badge" style="background-color: {{ $visit->status->color() }}">{{ $visit->status->label() }}</span></td>
                                <td><a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body text-center py-4 text-muted">
                <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                <p class="mb-0">No visits recorded yet.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Insurance Tab -->
    <div class="tab-pane" id="insurance">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>Patient Insurance Plans</h6>
                @can('patients.edit')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addInsuranceModal"><i class="ti ti-plus me-1"></i>Add Insurance</button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($patient->insurances->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Provider</th>
                                <th>Type</th>
                                <th>Tier</th>
                                <th>Member</th>
                                <th>Membership #</th>
                                <th>Expiry</th>
                                <th>Coverage</th>
                                <th>Status</th>
                                <th>Primary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->insurances as $ins)
                            @php
                                $insTier = $ins->insuranceTier;
                                $insMemberType = $ins->member_type?->value ?? 'holder';
                                $insConstraints = $insTier ? $insTier->effectiveConstraints($insMemberType) : null;
                                $insCoverage = $insConstraints['coverage_percentage'] ?? null;
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $ins->insuranceProvider->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $ins->insuranceProvider->type instanceof \BackedEnum ? $ins->insuranceProvider->type->color() : 'secondary' }}">
                                        {{ $ins->insuranceProvider->type instanceof \BackedEnum ? $ins->insuranceProvider->type->label() : ucfirst($ins->insuranceProvider->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($insTier)
                                        <span class="badge bg-primary bg-opacity-75">{{ $insTier->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($insMemberType === 'beneficiary')
                                        <span class="badge bg-warning text-dark">Beneficiary</span>
                                    @else
                                        <span class="badge bg-info">Card Holder</span>
                                    @endif
                                </td>
                                <td>{{ $ins->membership_number ?? '—' }}</td>
                                <td>
                                    @if($ins->expiry_date)
                                        <span class="{{ $ins->is_expired ? 'text-danger' : '' }}">{{ $ins->expiry_date->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted">No expiry</span>
                                    @endif
                                </td>
                                <td>{{ $insCoverage !== null ? $insCoverage . '%' : '—' }}</td>
                                <td>
                                    @if($ins->is_active && !$ins->is_expired)
                                        <span class="badge badge-soft-success">Active</span>
                                    @elseif($ins->is_expired)
                                        <span class="badge badge-soft-danger">Expired</span>
                                    @else
                                        <span class="badge badge-soft-warning">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ins->is_primary)
                                        <span class="badge bg-primary">Primary</span>
                                    @else
                                        @can('patients.edit')
                                        <form method="POST" action="{{ route('admin.patients.insurances.set-primary', [$patient, $ins]) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Set Primary</button>
                                        </form>
                                        @endcan
                                    @endif
                                </td>
                                <td>
                                    @can('patients.edit')
                                    @if(!$ins->insuranceProvider->is_default)
                                    <button type="button" class="btn btn-sm btn-outline-secondary edit-insurance-btn"
                                        data-id="{{ $ins->id }}"
                                        data-provider="{{ $ins->insurance_provider_id }}"
                                        data-tier="{{ $ins->insurance_tier_id }}"
                                        data-tier-name="{{ $insTier?->name }}"
                                        data-member-type="{{ $insMemberType }}"
                                        data-card-holder="{{ $ins->card_holder_insurance_id }}"
                                        data-membership="{{ $ins->membership_number }}"
                                        data-policy="{{ $ins->policy_number }}"
                                        data-expiry="{{ $ins->expiry_date?->format('Y-m-d') }}"
                                        data-active="{{ $ins->is_active }}"
                                        data-bs-toggle="modal" data-bs-target="#editInsuranceModal">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.patients.insurances.destroy', [$patient, $ins]) }}" class="d-inline" onsubmit="return confirm('Remove this insurance?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-shield-off fs-1 d-block mb-2"></i>
                    <p class="mb-1">No insurance plans added yet.</p>
                    <small>Cash & Carry will be used by default for all visits.</small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Emergency Contacts Tab -->
    <div class="tab-pane" id="emergency-contacts">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-urgent me-1"></i>Emergency Contacts</h6>
                @can('patients.edit')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyContactModal"><i class="ti ti-plus me-1"></i>Add Contact</button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($patient->emergencyContacts->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Secondary Phone</th>
                                <th>Relationship</th>
                                <th>Primary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($patient->emergencyContacts as $ec)
                            <tr>
                                <td class="fw-medium">{{ $ec->name }}</td>
                                <td>{{ $ec->phone }}</td>
                                <td>{{ $ec->phone_secondary ?? '—' }}</td>
                                <td>{{ $ec->relationship ?? '—' }}</td>
                                <td>
                                    @if($ec->is_primary)
                                        <span class="badge bg-primary">Primary</span>
                                    @endif
                                </td>
                                <td>
                                    @can('patients.edit')
                                    <button type="button" class="btn btn-sm btn-outline-secondary edit-ec-btn"
                                        data-id="{{ $ec->id }}"
                                        data-name="{{ $ec->name }}"
                                        data-phone="{{ $ec->phone }}"
                                        data-phone-secondary="{{ $ec->phone_secondary }}"
                                        data-relationship="{{ $ec->relationship }}"
                                        data-primary="{{ $ec->is_primary }}"
                                        data-bs-toggle="modal" data-bs-target="#editEmergencyContactModal">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.patients.emergency-contacts.destroy', [$patient, $ec]) }}" class="d-inline" onsubmit="return confirm('Remove this contact?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-address-book-off fs-1 d-block mb-2"></i>
                    <p class="mb-0">No emergency contacts added yet.</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Billing Tab -->
    <div class="tab-pane" id="billing">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>Billing Summary</h6>
            </div>
            <div class="card-body">
                @php
                    $allInvoices = $patient->visits->flatMap->invoices;
                    $totalBilled = $allInvoices->sum('total_amount');
                    $totalPaid = $allInvoices->sum('amount_paid');
                    $totalOutstanding = $totalBilled - $totalPaid;
                    $invoicedServiceKeys = $allInvoices
                        ->flatMap(fn ($invoice) => $invoice->items->map(fn ($item) => $invoice->visit_id . ':' . $item->service_catalog_id))
                        ->filter(fn ($key) => ! str_ends_with($key, ':'))
                        ->flip();
                    $unbilledVisitServices = $patient->visits->flatMap(function ($visit) use ($invoicedServiceKeys) {
                        return $visit->visitServices
                            ->filter(fn ($service) => ! $invoicedServiceKeys->has($visit->id . ':' . $service->service_catalog_id))
                            ->map(fn ($service) => ['visit' => $visit, 'service' => $service]);
                    });
                @endphp
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-1">Total Billed</h6>
                            <h4 class="fw-bold mb-0">&#8373;{{ number_format($totalBilled, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-1">Total Paid</h6>
                            <h4 class="fw-bold text-success mb-0">&#8373;{{ number_format($totalPaid, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-1">Outstanding</h6>
                            <h4 class="fw-bold {{ $totalOutstanding > 0 ? 'text-danger' : 'text-success' }} mb-0">&#8373;{{ number_format($totalOutstanding, 2) }}</h4>
                        </div>
                    </div>
                </div>

                @if($unbilledVisitServices->isNotEmpty())
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0">Uninvoiced Visit Services</h6>
                    <span class="badge bg-warning text-dark">{{ $unbilledVisitServices->count() }} pending</span>
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Visit</th>
                                <th>Service</th>
                                <th>Department</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unbilledVisitServices as $row)
                            <tr>
                                <td>{{ $row['visit']->visit_number }}</td>
                                <td>{{ $row['service']->serviceCatalog?->name ?? 'Service' }}</td>
                                <td>{{ $row['service']->department?->name ?? '—' }}</td>
                                <td class="text-end">&#8373;{{ number_format($row['service']->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('invoices.create')
                                    <a href="{{ route('admin.billing.invoices.create', ['visit_id' => $row['visit']->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-file-invoice me-1"></i>Create Invoice
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($allInvoices->isNotEmpty())
                <h6 class="fw-bold mb-2">Recent Invoices</h6>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Visit</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allInvoices->sortByDesc('created_at')->take(10) as $inv)
                            <tr>
                                <td><a href="{{ route('admin.billing.invoices.show', $inv) }}" class="fw-medium">{{ $inv->invoice_number }}</a></td>
                                <td>{{ $inv->visit?->visit_number ?? '—' }}</td>
                                <td>{{ $inv->created_at->format('d M Y') }}</td>
                                <td>&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                                <td>&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                                <td><span class="badge badge-soft-{{ ($inv->status instanceof \BackedEnum ? $inv->status->value : $inv->status) === 'paid' ? 'success' : (($inv->status instanceof \BackedEnum ? $inv->status->value : $inv->status) === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($inv->status instanceof \BackedEnum ? $inv->status->value : $inv->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-3 text-muted">
                    <p class="mb-0">No billing records yet.</p>
                </div>
                @endif
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
                        <h6 class="fw-bold fs-13">Digital Address</h6>
                        <p>{{ $patient->digital_address ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Tab -->
    <div class="tab-pane" id="activity-log">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i>Patient Activity Log</h6>
            </div>
            @if($activityLogs->isNotEmpty())
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Event</th>
                                <th>Description</th>
                                <th>Changed By</th>
                                <th>Fields Changed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLogs as $log)
                            <tr>
                                <td class="text-nowrap small">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <span class="badge badge-soft-{{ $log->event === 'created' ? 'success' : ($log->event === 'deleted' ? 'danger' : 'info') }}">
                                        {{ ucfirst($log->event ?? 'updated') }}
                                    </span>
                                </td>
                                <td class="small">{{ $log->description }}</td>
                                <td class="small">{{ $log->causer?->full_name ?? $log->causer?->name ?? 'System' }}</td>
                                <td class="small">
                                    @if($log->properties->has('attributes') && $log->properties->has('old'))
                                        @php $changed = array_keys($log->properties['attributes'] ?? []); @endphp
                                        @if(count($changed))
                                            <span class="text-muted">{{ implode(', ', $changed) }}</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body text-center py-4 text-muted">
                <i class="ti ti-history-off fs-1 d-block mb-2"></i>
                <p class="mb-0">No activity recorded yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Add Insurance Modal --}}
@can('patients.edit')
@php
    $addableInsuranceTypes = $insuranceProviders
        ->where('is_default', false)
        ->pluck('type')
        ->filter()
        ->unique(fn ($type) => $type instanceof \BackedEnum ? $type->value : (string) $type)
        ->sortBy(fn ($type) => $type instanceof \BackedEnum ? $type->label() : ucfirst((string) $type));
@endphp
<div class="modal fade" id="addInsuranceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patients.insurances.store', $patient) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Insurance Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Insurance Type <span class="text-danger">*</span></label>
                            <select id="addInsType" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($addableInsuranceTypes as $type)
                                    @php
                                        $typeValue = $type instanceof \BackedEnum ? $type->value : (string) $type;
                                        $typeLabel = method_exists($type, 'label') ? $type->label() : ucfirst($typeValue);
                                    @endphp
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Insurance Provider <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="addInsProvider" class="form-select" required disabled>
                                <option value="">Select type first</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Insurance Tier <span class="text-danger">*</span></label>
                            <select name="insurance_tier_id" id="addInsTier" class="form-select" disabled>
                                <option value="">Select provider first</option>
                            </select>
                            <div id="addInsTierInfo" class="small text-muted mt-1">If no tier is chosen, the provider's default tier will be used.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Member Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberHolder" value="holder" checked>
                                    <label class="form-check-label" for="addMemberHolder">
                                        <span class="badge bg-info">Card Holder</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="addMemberBeneficiary" value="beneficiary">
                                    <label class="form-check-label" for="addMemberBeneficiary">
                                        <span class="badge bg-warning text-dark">Beneficiary</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" id="addCardHolderRow" style="display:none;">
                            <label class="form-label">Card Holder Insurance <span class="text-danger">*</span></label>
                            <select name="card_holder_insurance_id" id="addCardHolder" class="form-select">
                                <option value="">Select card holder</option>
                                @foreach($patient->insurances->where('member_type', null)->merge($patient->insurances->where('member_type', \App\Enums\MemberType::HOLDER)) as $holderIns)
                                    <option value="{{ $holderIns->id }}">{{ $holderIns->insuranceProvider->name }} — {{ $holderIns->membership_number ?? 'no membership #' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="addInsPrimary">
                                <label class="form-check-label" for="addInsPrimary">Set as primary insurance</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Insurance</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Insurance Modal --}}
<div class="modal fade" id="editInsuranceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editInsuranceForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Insurance Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="hidden" name="insurance_provider_id" id="editInsProviderId">
                            <label class="form-label">Tier</label>
                            <div id="editInsTierDisplay" class="form-control-plaintext fw-medium text-muted">—</div>
                            <input type="hidden" name="insurance_tier_id" id="editInsTierId">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Member Type</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="editMemberHolder" value="holder">
                                    <label class="form-check-label" for="editMemberHolder">
                                        <span class="badge bg-info">Card Holder</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="member_type" id="editMemberBeneficiary" value="beneficiary">
                                    <label class="form-check-label" for="editMemberBeneficiary">
                                        <span class="badge bg-warning text-dark">Beneficiary</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control" id="editInsMembership">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control" id="editInsPolicy">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" id="editInsExpiry">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editInsActive">
                                <label class="form-check-label" for="editInsActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Insurance</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Emergency Contact Modal --}}
<div class="modal fade" id="addEmergencyContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patients.emergency-contacts.store', $patient) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Emergency Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Secondary Phone</label>
                        <input type="tel" name="phone_secondary" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <select name="relationship" class="form-select">
                            <option value="">Select</option>
                            @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                                <option value="{{ $rel }}">{{ $rel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="addEcPrimary">
                        <label class="form-check-label" for="addEcPrimary">Set as primary contact</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Emergency Contact Modal --}}
<div class="modal fade" id="editEmergencyContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editEcForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Emergency Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contact Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="editEcName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" id="editEcPhone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Secondary Phone</label>
                        <input type="tel" name="phone_secondary" class="form-control" id="editEcPhoneSecondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <select name="relationship" class="form-select" id="editEcRelationship">
                            <option value="">Select</option>
                            @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                                <option value="{{ $rel }}">{{ $rel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="editEcPrimary">
                        <label class="form-check-label" for="editEcPrimary">Set as primary contact</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@section('scripts')
<script>
(function () {
    // ── Edit Insurance Modal ──────────────────────────────────────────────────
    document.querySelectorAll('.edit-insurance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('editInsuranceForm').action = '{{ url("admin/patients/" . $patient->id . "/insurances") }}/' + id;
            document.getElementById('editInsProviderId').value = this.dataset.provider || '';
            document.getElementById('editInsMembership').value = this.dataset.membership || '';
            document.getElementById('editInsPolicy').value = this.dataset.policy || '';
            document.getElementById('editInsExpiry').value = this.dataset.expiry || '';
            document.getElementById('editInsActive').checked = this.dataset.active === '1';
            // Tier display
            document.getElementById('editInsTierId').value = this.dataset.tier || '';
            document.getElementById('editInsTierDisplay').textContent = this.dataset.tierName || '—';
            // Member type
            const mt = this.dataset.memberType || 'holder';
            document.getElementById('editMemberHolder').checked = mt === 'holder';
            document.getElementById('editMemberBeneficiary').checked = mt === 'beneficiary';
        });
    });

    const providerByTypeUrl = '{{ route("admin.insurance-providers.by-type") }}';
    const tiersForProviderUrl = '{{ route("admin.insurance-providers.tiers.for-patient", ":pid") }}';
    const typeSelect = document.getElementById('addInsType');
    const providerSelect = document.getElementById('addInsProvider');
    const tierSelect = document.getElementById('addInsTier');
    const tierInfo = document.getElementById('addInsTierInfo');

    function resetProviderSelect(message = 'Select type first') {
        providerSelect.innerHTML = '<option value="">' + message + '</option>';
        providerSelect.disabled = true;
    }

    function resetTierSelect(message = 'Select provider first') {
        tierSelect.innerHTML = '<option value="">' + message + '</option>';
        tierSelect.disabled = true;
        tierInfo.textContent = '';
    }

    // ── Add Insurance: Type → Provider cascade ──────────────────────────────
    typeSelect.addEventListener('change', function() {
        const type = this.value;
        resetProviderSelect(type ? 'Loading providers…' : 'Select type first');
        resetTierSelect();

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetProviderSelect('No providers for selected type');
                return;
            }

            providerSelect.innerHTML = '<option value="">Select Provider</option>';
            providers.forEach(provider => {
                const opt = document.createElement('option');
                opt.value = provider.id;
                opt.textContent = provider.short_name ? provider.name + ' (' + provider.short_name + ')' : provider.name;
                providerSelect.appendChild(opt);
            });
            providerSelect.disabled = false;
        })
        .catch(() => {
            resetProviderSelect('Failed to load providers');
        });
    });

    // ── Add Insurance: Provider → Tier cascade ───────────────────────────────
    providerSelect.addEventListener('change', function() {
        const providerId = this.value;

        if (!providerId) {
            resetTierSelect();
            return;
        }

        tierSelect.innerHTML = '<option value="">Loading…</option>';
        tierSelect.disabled = true;

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                tierSelect.innerHTML = '<option value="">No tiers available</option>';
                return;
            }
            tierSelect.innerHTML = '<option value="">Select Tier</option>';
            tiers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.coverage_percentage ? ' (' + t.coverage_percentage + '% coverage)' : '');
                if (t.is_default) opt.selected = true;
                tierSelect.appendChild(opt);
            });
            tierSelect.disabled = false;
            updateTierInfo();
        })
        .catch(() => {
            tierSelect.innerHTML = '<option value="">Failed to load tiers</option>';
        });
    });

    document.getElementById('addInsTier').addEventListener('change', updateTierInfo);

    function updateTierInfo() {
        const sel = document.getElementById('addInsTier');
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('addInsTierInfo').textContent = opt && opt.value ? opt.textContent : '';
    }

    // ── Add Insurance: Member Type → show/hide card holder row ───────────────
    document.querySelectorAll('input[name="member_type"]').forEach(r => {
        r.addEventListener('change', function() {
            const row = document.getElementById('addCardHolderRow');
            const sel = document.getElementById('addCardHolder');
            if (this.value === 'beneficiary') {
                row.style.display = '';
                sel.required = true;
            } else {
                row.style.display = 'none';
                sel.required = false;
                sel.value = '';
            }
        });
    });

    // ── Edit Emergency Contact Modal ──────────────────────────────────────────
    document.querySelectorAll('.edit-ec-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('editEcForm').action = '{{ url("admin/patients/" . $patient->id . "/emergency-contacts") }}/' + id;
            document.getElementById('editEcName').value = this.dataset.name || '';
            document.getElementById('editEcPhone').value = this.dataset.phone || '';
            document.getElementById('editEcPhoneSecondary').value = this.dataset.phoneSecondary || '';
            document.getElementById('editEcRelationship').value = this.dataset.relationship || '';
            document.getElementById('editEcPrimary').checked = this.dataset.primary === '1';
        });
    });
})();
</script>
@endsection
