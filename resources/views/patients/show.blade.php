@extends('layouts.app')
@section('title', $patient->full_name . ' - ' . __('patients.profile_title'))

@section('content')
<x-page-header-back
        :title="__('patients.profile_title')"
        :href="route('admin.patients.index')"
    >
    <x-slot:actions>
        <div class="d-flex align-items-center justify-content-end flex-nowrap gap-2 text-end">
            @if($activeBreakGlass)
                <div class="d-inline-flex align-items-center gap-2 rounded border border-warning-subtle bg-warning-subtle text-warning-emphasis px-2 py-1">
                    <span class="d-inline-flex align-items-center">
                        <i class="ti ti-alert-triangle me-1"></i>
                        <strong>{{ __('patients.privacy.break_glass_active') }}</strong>
                    </span>
                    <span class="small">{{ __('patients.privacy.break_glass_expires_at', ['time' => $activeBreakGlass->expires_at?->format('d M Y H:i')]) }}</span>
                    @can('patients.privacy.break_glass')
                    <form method="POST" action="{{ route('admin.patients.privacy.break-glass.revoke', $activeBreakGlass) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-dark py-0">{{ __('common.revoke') }}</button>
                    </form>
                    @endcan
                </div>
            @endif

            @if($patient->activePrivacyDirectives->isNotEmpty())
                <div class="d-inline-flex align-items-center flex-wrap gap-1 rounded border border-danger-subtle bg-danger-subtle text-danger-emphasis px-2 py-1">
                    <span class="fw-semibold"><i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.privacy_directives') }}</span>
                    @can('patients.privacy_directives.view')
                        @foreach($patient->activePrivacyDirectives as $directive)
                            <span class="badge bg-danger text-white" title="{{ trim(($directive->summary ?? '').' '.($directive->details ?? '')) }}">
                                {{ __('patients.privacy.'.$directive->directive_type) !== 'patients.privacy.'.$directive->directive_type ? __('patients.privacy.'.$directive->directive_type) : $directive->directive_type }}
                            </span>
                            @if($directive->summary)
                                <span class="small text-danger-emphasis">{{ $directive->summary }}</span>
                            @endif
                        @endforeach
                    @else
                        <span class="small">{{ __('patients.privacy.directive_details_hidden') }}</span>
                    @endcan
                </div>
            @endif

            @can('patients.privacy_directives.manage')
                <button type="button" class="btn btn-primary btn-md flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addPrivacyDirectiveModal">
                    <i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.privacy_directive') }}
                </button>
            @endcan
            @can('patients.privacy.break_glass')
                <button type="button" class="btn btn-warning btn-md flex-shrink-0" data-bs-toggle="modal" data-bs-target="#startBreakGlassModal">
                    <i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.start_break_glass_access') }}
                </button>
            @endcan

            @can('patients.mark_deceased')
            @if(!$patient->is_deceased)
                <button type="button" class="btn btn-outline-danger btn-md flex-shrink-0" data-bs-toggle="modal" data-bs-target="#markDeceasedModal">
                    <i class="ti ti-skull me-1"></i>{{ __('patients.mark_deceased') }}
                </button>
            @endif
            @endcan
        </div>
    </x-slot:actions>
</x-page-header-back>

<!-- Page Header -->
<!-- <div class="d-flex mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.patients.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>{{ __('patients.title') }}</a>
    </h6>

    @can('patients.mark_deceased')
    @if(!$patient->is_deceased)
    <button type="button" class="btn btn-outline-danger btn-md ms-auto" data-bs-toggle="modal" data-bs-target="#markDeceasedModal">
        <i class="ti ti-skull me-1"></i>{{ __('patients.mark_deceased') }}
    </button>
    @endif
    @endcan
</div>

@if($activeBreakGlass)
<div class="alert alert-warning d-flex justify-content-between align-items-center gap-2">
    <div>
        <i class="ti ti-alert-triangle me-1"></i>
        <strong>{{ __('patients.privacy.break_glass_active') }}</strong>
        <span class="small">{{ __('patients.privacy.break_glass_expires_at', ['time' => $activeBreakGlass->expires_at?->format('d M Y H:i')]) }}</span>
    </div>
    @can('patients.privacy.break_glass')
    <form method="POST" action="{{ route('admin.patients.privacy.break-glass.revoke', $activeBreakGlass) }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-dark">{{ __('common.revoke') }}</button>
    </form>
    @endcan
</div>
@endif

@if($patient->activePrivacyDirectives->isNotEmpty())
<div class="alert alert-danger">
    <div class="fw-semibold mb-1"><i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.privacy_directives') }}</div>
    @can('patients.privacy_directives.view')
        <div class="d-flex flex-column gap-1">
            @foreach($patient->activePrivacyDirectives as $directive)
                <div>
                    <span class="badge bg-danger-subtle text-danger">{{ __('patients.privacy.'.$directive->directive_type) !== 'patients.privacy.'.$directive->directive_type ? __('patients.privacy.'.$directive->directive_type) : $directive->directive_type }}</span>
                    <span>{{ $directive->summary }}</span>
                    @if($directive->details)
                        <small class="text-muted d-block">{{ $directive->details }}</small>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <span>{{ __('patients.privacy.directive_details_hidden') }}</span>
    @endcan
</div>
@endif -->

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
                    <p class="mb-3">
                        @if($patient->address)
                            <x-patient-protected-field field="address" :value="$patient->address" :patient="$patient" />{{ collect([$patient->city, $patient->town, $patient->region])->filter()->isNotEmpty() ? ', ' : '' }}
                        @endif
                        {{ collect([$patient->city, $patient->town])->filter()->implode(', ') }}{{ $patient->region ? ', ' . $patient->region : '' }}
                    </p>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <p class="mb-0 d-inline-flex align-items-center">
                            <i class="ti ti-phone me-1 text-dark"></i>
                            <x-patient-protected-field field="phone" :value="$patient->phone" :patient="$patient" />
                            @if($patient->phone_secondary)
                                / <x-patient-protected-field field="phone_secondary" :value="$patient->phone_secondary" :patient="$patient" />
                            @endif
                        </p>
                        @if($patient->email)
                        <span class="text-light">|</span>
                        <p class="mb-0 d-inline-flex align-items-center"><i class="ti ti-mail me-1 text-dark"></i><x-patient-protected-field field="email" :value="$patient->email" :patient="$patient" /></p>
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
                        <span class="text-muted small me-2"><i class="ti ti-calendar-event me-1"></i>{{ __('patients.last_visit') }}: {{ $lastVisitDate->format('d M Y') }}</span>
                    @endif
                    @if($patient->status === 'active')
                        <span class="badge badge-soft-success fs-13 px-3 py-2">{{ __('common.active') }}</span>
                    @elseif($patient->status === 'inactive')
                        <span class="badge badge-soft-warning fs-13 px-3 py-2">{{ __('common.inactive') }}</span>
                    @elseif($patient->status === 'archived')
                        <span class="badge badge-soft-secondary fs-13 px-3 py-2">{{ __('patients.archived') }}</span>
                    @else
                        <span class="badge badge-soft-dark fs-13 px-3 py-2">{{ __('patients.deceased') }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
                    @if(!$patient->isMerged() && !$patient->is_deceased && $patient->status === 'active')
                    @can('visits.create')
                    @if(($lastVisitDate && $lastVisitDate->toDateString() === today()->toDateString()))
                    <button type="button" class="btn btn-success btn-md" disabled title="{{ __('patients.cannot_visit_deceased') }}">
                        <i class="ti ti-plus me-1"></i>{{ __('patients.new_visit') }}
                    </button>
                    @else
                    <a href="{{ route('admin.visits.create') }}?patient_id={{ $patient->id }}" class="btn btn-success btn-md"><i class="ti ti-plus me-1"></i>{{ __('patients.new_visit') }}</a>
                    @endif
                    @endcan
                    @endif

                    @can('patients.edit')
                    <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-primary btn-md"><i class="ti ti-edit me-1"></i>{{ __('patients.edit_patient') }}</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

@if($patient->is_deceased)
<div class="alert alert-danger d-flex align-items-start mb-3" role="alert">
    <i class="ti ti-skull fs-20 me-3 flex-shrink-0 mt-1"></i>
    <div>
        <h6 class="fw-bold mb-1">{{ __('patients.patient_deceased') }}</h6>
        <p class="mb-0">
            {{ __('patients.date_of_death') }}: <strong>{{ $patient->deceased_at ? $patient->deceased_at->format('d M Y') : '—' }}</strong>
            @if($patient->cause_of_death)
                &nbsp;|&nbsp; {{ __('patients.cause') }}: <strong>{{ $patient->cause_of_death }}</strong>
            @endif
            @if($patient->deceased_notes)
                <br><span class="text-muted">{{ $patient->deceased_notes }}</span>
            @endif
            @if($patient->markedDeceasedBy)
                <br><small class="text-muted">{{ __('patients.recorded_by') }} {{ $patient->markedDeceasedBy->name }}</small>
            @endif
        </p>
    </div>
</div>
@endif

<!-- Info Cards Row -->
<div class="row">
    <!-- About Card -->
    <div class="col-xl-5 d-flex">
        <div class="card shadow-sm flex-fill w-100">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-user-star me-1"></i>{{ __('patients.about') }}</h5>
            </div>
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-calendar-event fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.date_of_birth') }}</h6>
                                <p class="mb-0">{{ $patient->date_of_birth->format('d M Y') }} ({{ $patient->age }} yrs)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-droplet fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.blood_group') }}</h6>
                                <p class="mb-0">{{ $patient->blood_group?->translatedLabel() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-gender-male fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.gender') }}</h6>
                                <p class="mb-0">{{ $patient->gender?->translatedLabel() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-heart fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.marital_status') }}</h6>
                                <p class="mb-0">{{ $patient->marital_status?->translatedLabel() ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-pray fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.religion') }}</h6>
                                <p class="mb-0">{{ $patient->religion ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-briefcase fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.occupation') }}</h6>
                                <p class="mb-0">{{ $patient->occupation ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="col-sm-6">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-circle bg-light text-dark flex-shrink-0 me-2"><i class="ti ti-map-pin fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">Region</h6>
                                <p class="mb-0">{{ $patient->region ?? '—' }}</p>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Identification & Emergency Card -->
    <div class="col-xl-7 d-flex">
        <div class="card shadow-sm flex-fill w-100">
            <div class="card-header">
                <h5 class="fw-bold mb-0"><i class="ti ti-id me-1"></i>{{ __('patients.identification') }}</h5>
            </div>
            <div class="card-body pb-0">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-id-badge-2 fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.ghana_card') }}</h6>
                                <p class="mb-0"><x-patient-protected-field field="ghana_card_number" :value="$patient->ghana_card_number" :patient="$patient" /></p>
                            </div>
                        </div>
                    </div>
                    @if($patient->insurances->isNotEmpty())
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-shield-check fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.primary_insurance') }}</h6>
                                <p class="mb-0">{{ $patient->insurances->where('is_primary', true)->first()?->insuranceProvider?->name ?? 'Cash & Carry' }}</p>

                                {{-- <h6 class="fs-13 fw-bold mb-1">{{ $patient->insurances->where('is_primary', true)->first()?->insuranceProvider?->name ?? 'Cash & Carry' }}</h6>
                                <p class="mb-0">{{ $patient->insurances->where('is_primary', true)->first()?->membershipNumber ?? '—' }}</p> --}}
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-building-community fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.city_town') }}</h6>
                                <p class="mb-0">{{ collect([$patient->city, $patient->town])->filter()->implode(' / ') ?: '\u2014' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-map-pin-code fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.digital_address') }}</h6>
                                <p class="mb-0"><x-patient-protected-field field="digital_address" :value="$patient->digital_address" :patient="$patient" /></p>
                            </div>
                        </div>
                    </div>
                    @php $primaryContact = $patient->emergencyContacts->where('is_primary', true)->first() ?? $patient->emergencyContacts->first(); @endphp
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-urgent fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.emergency_contact') }}</h6>
                                <p class="mb-0">{{ $primaryContact?->name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-phone-call fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.emergency_phone') }}</h6>
                                <p class="mb-0"><x-patient-protected-field field="emergency_contact_phone" :value="$primaryContact?->phone" :patient="$patient" /></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar rounded-2 bg-light text-dark flex-shrink-0 me-2 border"><i class="ti ti-home fs-16"></i></span>
                            <div>
                                <h6 class="fs-13 fw-bold mb-1">{{ __('patients.address') }}</h6>
                                <p class="mb-0"><x-patient-protected-field field="address" :value="$patient->address" :patient="$patient" /></p>
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
            <div class="card-body d-flex align-items-center justify-content-center">
                <p class="mb-0"><x-patient-protected-field field="allergies" :value="$patient->allergies" :patient="$patient" /></p>
                <h5 class="fw-bold mb-0 text-danger"><i class="ti ti-alert-triangle me-1"></i>{{ __('patients.allergies') }}</h5>
            </div>
        </div>
    </div>
    @endif
    @if($patient->chronic_conditions)
    <div class="col-md-6 d-flex">
        <div class="card shadow-sm flex-fill">
            <div class="card-body d-flex align-items-center justify-content-center">
                <p class="mb-0"><x-patient-protected-field field="chronic_conditions" :value="$patient->chronic_conditions" :patient="$patient" /></p>
                <h5 class="fw-bold mb-0 text-warning"><i class="ti ti-heartbeat me-1"></i>{{ __('patients.chronic_conditions') }}</h5>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Tabs -->
<ul class="nav nav-tabs nav-bordered mb-3">
    <li class="nav-item">
        <a href="#visits" data-bs-toggle="tab" class="nav-link active bg-transparent"><i class="ti ti-calendar-event me-1"></i>{{ __('patients.tab_visit_history') }} <span class="badge bg-success ms-1">{{ $patient->visits->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a href="#insurance" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-shield-check me-1"></i>{{ __('patients.tab_insurance') }} <span class="badge bg-primary ms-1">{{ $patient->insurances->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a href="#emergency-contacts" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-urgent me-1"></i>{{ __('patients.tab_emergency_contacts') }} <span class="badge bg-secondary ms-1">{{ $patient->emergencyContacts->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a href="#billing" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-receipt me-1"></i>{{ __('patients.tab_billing') }} <span class="badge bg-warning text-dark ms-1">{{ $patient->visits->flatMap(fn($v) => $v->invoices)->count() }}</span></a>
    </li>
    @can('patients.financial_risk.view')
    <li class="nav-item">
        <a href="#financial-risk" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-alert-triangle me-1"></i>{{ __('patient_financial_risk.title') }}
            @if($financialRisk && $financialRisk->isRestrictive())<span class="badge bg-{{ $financialRisk->risk_level->color() }} ms-1">{{ $financialRisk->risk_level->label() }}</span>@endif
        </a>
    </li>
    @endcan
    <li class="nav-item">
        <a href="#registration-info" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-info-circle me-1"></i>{{ __('patients.tab_registration_info') }}</a>
    </li>
    <li class="nav-item">
        <a href="#activity-log" data-bs-toggle="tab" class="nav-link bg-transparent"><i class="ti ti-history me-1"></i>{{ __('patients.tab_activity_log') }} <span class="badge bg-secondary ms-1">{{ $activityLogs->count() }}</span></a>
    </li>
</ul>

<div class="tab-content">
    <!-- Visit History Tab -->
    <div class="tab-pane show active" id="visits">
        @if(($upcomingAppointments ?? collect())->isNotEmpty())
        <div class="card border-info mb-3">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="fw-bold mb-0 text-info"><i class="ti ti-calendar-plus me-1"></i>{{ __('patients.upcoming_appointments') }}</h6>
            </div>
            <x-data-table :card="false" local show-summary show-per-page>
                <x-slot:head>
                    <tr>
                        <th>{{ __('patients.col_date') }}</th>
                        <th>{{ __('patients.col_department') }}</th>
                        <th>{{ __('patients.col_doctor') }}</th>
                        <th>{{ __('patients.col_reason') }}</th>
                        <th>{{ __('patients.col_status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </x-slot:head>
                @foreach($upcomingAppointments as $appointment)
                <tr>
                    <td>
                        <span class="fw-medium">{{ $appointment->appointment_date?->format('d M Y') ?? '-' }}</span>
                        @if($appointment->start_time)
                            <small class="text-muted d-block">{{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $appointment->department?->name ?? '-' }}
                        @if($appointment->services->isNotEmpty())
                            <small class="text-muted d-block">{{ $appointment->services->pluck('name')->implode(', ') }}</small>
                        @endif
                    </td>
                    <td>{{ $appointment->doctor?->full_name ? 'Dr. '.$appointment->doctor->full_name : '-' }}</td>
                    <td>{{ Str::limit($appointment->reason ?: $appointment->notes ?: '-', 80) }}</td>
                    <td><span class="badge bg-{{ $appointment->status?->color() ?? 'secondary' }}">{{ $appointment->status?->translatedLabel() ?? '-' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                            <i class="ti ti-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </x-data-table>
        </div>
        @endif

        {{-- Upcoming Scheduled Visits --}}
        @if($upcomingVisits->isNotEmpty())
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary bg-opacity-10">
                <h6 class="fw-bold mb-0 text-primary"><i class="ti ti-calendar-plus me-1"></i>{{ __('patients.upcoming_visits') }}</h6>
            </div>
            <x-data-table :card="false" local show-summary show-per-page>
                <x-slot:head>
                            <tr>
                                <th>{{ __('patients.col_date') }}</th>
                                <th>{{ __('patients.col_time') }}</th>
                                <th>{{ __('patients.col_department') }}</th>
                                <th>{{ __('patients.col_doctor') }}</th>
                                <th>{{ __('patients.col_status') }}</th>
                                <th class="text-end">{{ __('common.actions') }}</th>
                            </tr>
                </x-slot:head>
                            @foreach($upcomingVisits as $uv)
                            <tr>
                                <td>{{ $uv->visit_date->format('d M Y') }}</td>
                                <td>{{ $uv->start_time ? \Carbon\Carbon::parse($uv->start_time)->format('h:i A') : '—' }}</td>
                                <td>{{ $uv->currentDepartment?->name ?? '—' }}</td>
                                <td>{{ $uv->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                                <td><span class="badge" style="background-color: {{ $uv->status->color() }}">{{ $uv->status->translatedLabel() }}</span></td>
                                <td class="text-end">
                                    @php $linkedAppointment = $uv->appointments->first(); @endphp
                                    <a href="{{ $linkedAppointment ? route('admin.appointments.show', $linkedAppointment) : route('admin.visits.show', $uv) }}" class="btn btn-sm btn-outline-primary" title="{{ __('common.view') }}">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
            </x-data-table>
        </div>
        @endif

        {{-- Past Visit History --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">{{ __('patients.visit_history') }}</h6>

                @if(!$patient->isMerged() && !$patient->is_deceased && $patient->status === 'active')
                @can('visits.create')
                @if(($lastVisitDate && $lastVisitDate->toDateString() === today()->toDateString()))
                <button type="button" class="btn btn-success btn-md" disabled title="{{ __('patients.cannot_visit_deceased') }}">
                    <i class="ti ti-plus me-1"></i>{{ __('patients.new_visit') }}
                </button>
                @else
                <a href="{{ route('admin.visits.create') }}?patient_id={{ $patient->id }}" class="btn btn-sm btn-success"><i class="ti ti-plus me-1"></i>{{ __('patients.new_visit') }}</a>
                @endif
                @endcan
                @endif
            </div>
            @if($patient->visits->isNotEmpty())
            <x-data-table :card="false" local show-summary show-per-page>
                <x-slot:head>
                            <tr>
                                <th>{{ __('patients.col_visit_no') }}</th>
                                <th>{{ __('patients.col_date') }}</th>
                                <th>{{ __('patients.col_type') }}</th>
                                <th>{{ __('patients.col_department') }}</th>
                                <th>{{ __('patients.col_doctor') }}</th>
                                <th>{{ __('patients.col_status') }}</th>
                                <th>{{ __('patients.col_action') }}</th>
                            </tr>
                </x-slot:head>
                            @foreach($patient->visits as $visit)
                            <tr>
                                <td><a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium">{{ $visit->visit_number }}</a></td>
                                <td>{{ $visit->visit_date->format('d M Y') }}</td>
                                <td>{{ $visit->visit_type?->translatedLabel() ?? '—' }}</td>
                                <td>—</td>
                                <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                                <td><x-status-badge :status="$visit->status" /></td>
                                <td><a aria-label="View" title="View" href="{{ route('admin.visits.show', $visit) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                            </tr>
                            @endforeach
            </x-data-table>
            @else
            <div class="card-body text-center py-4 text-muted">
                <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                <p class="mb-0">{{ __('patients.no_visits') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Insurance Tab -->
    <div class="tab-pane" id="insurance">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('patients.insurance_plans') }}</h6>
                @can('patients.edit')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addInsuranceModal"><i class="ti ti-plus me-1"></i>{{ __('patients.add_insurance') }}</button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($patient->insurances->isNotEmpty())
                <x-data-table :card="false" local show-summary show-per-page>
                    <x-slot:head>
                            <tr>
                                <th>{{ __('patients.col_provider') }}</th>
                                <th>{{ __('common.type') }}</th>
                                <th>{{ __('patients.col_tier') }}</th>
                                <th>{{ __('patients.col_member') }}</th>
                                <th>{{ __('patients.col_membership_no') }}</th>
                                <th>{{ __('patients.col_expiry') }}</th>
                                <th>{{ __('patients.col_coverage') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('patients.col_primary') }}</th>
                                <th>{{ __('common.actions') }}</th>
                            </tr>
                    </x-slot:head>
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
                                        {{ $ins->insuranceProvider->type instanceof \BackedEnum ? $ins->insuranceProvider->type->translatedLabel() : ucfirst($ins->insuranceProvider->type) }}
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
                                        <span class="badge bg-warning text-dark">{{ __('patients.beneficiary') }}</span>
                                    @else
                                        <span class="badge bg-info">{{ __('patients.card_holder') }}</span>
                                    @endif
                                </td>
                                <td><x-patient-protected-field field="membership_number" :value="$ins->membership_number" :patient="$patient" />
                                    @if($ins->ccc_code)
                                        <div class="small text-muted">CCC: <x-patient-protected-field field="ccc_code" :value="$ins->ccc_code" :patient="$patient" /></div>
                                    @endif
                                </td>
                                <td>
                                    @if($ins->expiry_date)
                                        <span class="{{ $ins->is_expired ? 'text-danger' : '' }}">{{ $ins->expiry_date->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted">{{ __('patients.no_expiry') }}</span>
                                    @endif
                                </td>
                                <td>{{ $insCoverage !== null ? $insCoverage . '%' : '—' }}</td>
                                <td>
                                    @if($ins->is_active && !$ins->is_expired)
                                        <span class="badge badge-soft-success">{{ __('statuses.default.active') }}</span>
                                    @elseif($ins->is_expired)
                                        <span class="badge badge-soft-danger">{{ __('statuses.default.expired') }}</span>
                                    @else
                                        <span class="badge badge-soft-warning">{{ __('statuses.default.inactive') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ins->is_primary)
                                        <span class="badge bg-primary">{{ __('patients.col_primary') }}</span>
                                    @else
                                        @can('patients.edit')
                                        <form method="POST" action="{{ route('admin.patients.insurances.set-primary', [$patient, $ins]) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('patients.set_primary') }}</button>
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
                                        data-provider-name="{{ $ins->insuranceProvider->name }}"
                                        data-tier="{{ $ins->insurance_tier_id }}"
                                        data-tier-name="{{ $insTier?->name }}"
                                        data-member-type="{{ $insMemberType }}"
                                        data-card-holder="{{ $ins->card_holder_insurance_id }}"
                                        data-membership="{{ app(\App\Services\PatientPrivacyService::class)->display('membership_number', $ins->membership_number) }}"
                                        data-policy="{{ app(\App\Services\PatientPrivacyService::class)->display('policy_number', $ins->policy_number) }}"
                                        data-ccc-code="{{ app(\App\Services\PatientPrivacyService::class)->display('ccc_code', $ins->ccc_code) }}"
                                        data-expiry="{{ $ins->expiry_date?->format('Y-m-d') }}"
                                        data-active="{{ $ins->is_active }}"
                                        data-bs-toggle="modal" data-bs-target="#editInsuranceModal" aria-label="{{ __('common.edit') }}" title="{{ __('common.edit') }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <x-confirm-form :action="route('admin.patients.insurances.destroy', [$patient, $ins])" method="DELETE"
                                        button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-trash"
                                        :confirm-title="__('patients.remove_insurance')" :confirm-text="__('patients.remove_insurance_text')" :confirm-button="__('patients.yes_remove')" />
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                </x-data-table>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-shield-off fs-1 d-block mb-2"></i>
                    <p class="mb-1">{{ __('patients.no_insurance') }}</p>
                    <small>{{ __('patients.cash_and_carry_default') }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Emergency Contacts Tab -->
    <div class="tab-pane" id="emergency-contacts">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-urgent me-1"></i>{{ __('patients.emergency_contacts') }}</h6>
                @can('patients.edit')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyContactModal"><i class="ti ti-plus me-1"></i>{{ __('patients.add_contact') }}</button>
                @endcan
            </div>
            <div class="card-body p-0">
                @if($patient->emergencyContacts->isNotEmpty())
                <x-data-table :card="false" local show-summary show-per-page>
                    <x-slot:head>
                            <tr>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('common.phone') }}</th>
                                <th>{{ __('patients.secondary_phone') }}</th>
                                <th>{{ __('patients.relationship') }}</th>
                                <th>{{ __('patients.col_primary') }}</th>
                                <th>{{ __('common.actions') }}</th>
                            </tr>
                    </x-slot:head>
                            @foreach($patient->emergencyContacts as $ec)
                            <tr>
                                <td class="fw-medium">{{ $ec->name }}</td>
                                <td><x-patient-protected-field field="emergency_contact_phone" :value="$ec->phone" :patient="$patient" /></td>
                                <td><x-patient-protected-field field="emergency_contact_phone" :value="$ec->phone_secondary" :patient="$patient" /></td>
                                <td>{{ $ec->relationship ?? '—' }}</td>
                                <td>
                                    @if($ec->is_primary)
                                        <span class="badge bg-primary">{{ __('patients.col_primary') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @can('patients.edit')
                                    <button type="button" class="btn btn-sm btn-outline-secondary edit-ec-btn"
                                        data-id="{{ $ec->id }}"
                                        data-name="{{ $ec->name }}"
                                        data-phone="{{ app(\App\Services\PatientPrivacyService::class)->display('emergency_contact_phone', $ec->phone) }}"
                                        data-phone-secondary="{{ app(\App\Services\PatientPrivacyService::class)->display('emergency_contact_phone', $ec->phone_secondary) }}"
                                        data-relationship="{{ $ec->relationship }}"
                                        data-primary="{{ $ec->is_primary }}"
                                        data-bs-toggle="modal" data-bs-target="#editEmergencyContactModal" aria-label="{{ __('common.edit') }}" title="{{ __('common.edit') }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <x-confirm-form :action="route('admin.patients.emergency-contacts.destroy', [$patient, $ec])" method="DELETE"
                                        button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-trash"
                                        :confirm-title="__('patients.remove_contact')" :confirm-text="__('patients.remove_contact_text')" :confirm-button="__('patients.yes_remove')" />
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                </x-data-table>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-address-book-off fs-1 d-block mb-2"></i>
                    <p class="mb-0">{{ __('patients.no_emergency_contacts') }}</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Billing Tab -->
    <div class="tab-pane" id="billing">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>{{ __('patients.billing_summary') }}</h6>
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
                            <h6 class="text-muted mb-1">{{ __('patients.total_billed') }}</h6>
                            <h4 class="fw-bold mb-0">&#8373;{{ number_format($totalBilled, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-1">{{ __('patients.total_paid') }}</h6>
                            <h4 class="fw-bold text-success mb-0">&#8373;{{ number_format($totalPaid, 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center">
                            <h6 class="text-muted mb-1">{{ __('patients.outstanding') }}</h6>
                            <h4 class="fw-bold {{ $totalOutstanding > 0 ? 'text-danger' : 'text-success' }} mb-0">&#8373;{{ number_format($totalOutstanding, 2) }}</h4>
                        </div>
                    </div>
                </div>

                @if($unbilledVisitServices->isNotEmpty())
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0">{{ __('patients.uninvoiced_services') }}</h6>
                    <span class="badge bg-warning text-dark">{{ $unbilledVisitServices->count() }} {{ __('patients.pending') }}</span>
                </div>
                <x-data-table :card="false" local show-summary show-per-page table-class="table-sm" class="mb-4">
                    <x-slot:head>
                            <tr>
                                <th>{{ __('patients.col_visit') }}</th>
                                <th>{{ __('patients.col_service') }}</th>
                                <th>{{ __('patients.col_department') }}</th>
                                <th class="text-end">{{ __('patients.col_amount') }}</th>
                                <th class="text-end">{{ __('patients.col_action') }}</th>
                            </tr>
                    </x-slot:head>
                            @foreach($unbilledVisitServices as $row)
                            <tr>
                                <td>{{ $row['visit']->visit_number }}</td>
                                <td>{{ $row['service']->serviceCatalog?->name ?? 'Service' }}</td>
                                <td>{{ $row['service']->department?->name ?? '—' }}</td>
                                <td class="text-end">&#8373;{{ number_format($row['service']->total_price, 2) }}</td>
                                <td class="text-end">
                                    @can('invoices.create')
                                    <a href="{{ route('admin.billing.invoices.create', ['visit_id' => $row['visit']->id]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-file-invoice me-1"></i>{{ __('patients.create_invoice') }}
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                </x-data-table>
                @endif

                @if($allInvoices->isNotEmpty())
                <h6 class="fw-bold mb-2">{{ __('patients.recent_invoices') }}</h6>
                <x-data-table :card="false" local show-summary show-per-page>
                    <x-slot:head>
                            <tr>
                                <th>{{ __('patients.col_invoice_no') }}</th>
                                <th>{{ __('patients.col_visit') }}</th>
                                <th>{{ __('patients.col_date') }}</th>
                                <th>{{ __('patients.col_amount') }}</th>
                                <th>{{ __('patients.total_paid') }}</th>
                                <th>{{ __('common.status') }}</th>
                            </tr>
                    </x-slot:head>
                            @foreach($allInvoices->sortByDesc('created_at')->take(10) as $inv)
                            <tr>
                                <td><a href="{{ route('admin.billing.invoices.show', $inv) }}" class="fw-medium">{{ $inv->invoice_number }}</a></td>
                                <td>{{ $inv->visit?->visit_number ?? '—' }}</td>
                                <td>{{ $inv->created_at->format('d M Y') }}</td>
                                <td>&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                                <td>&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                                <td><span class="badge badge-soft-{{ ($inv->status instanceof \BackedEnum ? $inv->status->value : $inv->status) === 'paid' ? 'success' : (($inv->status instanceof \BackedEnum ? $inv->status->value : $inv->status) === 'partial' ? 'warning' : 'danger') }}">{{ $inv->status instanceof \BackedEnum ? $inv->status->translatedLabel() : ucfirst($inv->status) }}</span></td>
                            </tr>
                            @endforeach
                </x-data-table>
                @else
                <div class="text-center py-3 text-muted">
                    <p class="mb-0">{{ __('patients.no_billing') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    @can('patients.financial_risk.view')
    <!-- Financial Risk Tab (Payment Timing Policy Phase 5) -->
    <div class="tab-pane" id="financial-risk">
        @include('patients.partials._financial-risk')
    </div>
    @endcan

    <!-- Registration Info Tab -->
    <div class="tab-pane" id="registration-info">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    {{-- <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Patient Number</h6>
                        <p>{{ $patient->patient_number }}</p>
                    </div> --}}
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">{{ __('patients.registered_by') }}</h6>
                        <p>{{ $patient->registeredBy?->full_name ?? 'System' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">{{ __('patients.registration_date') }}</h6>
                        <p>{{ $patient->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">{{ __('patients.last_updated') }}</h6>
                        <p>{{ $patient->updated_at->format('d M Y, h:i A') }}</p>
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Secondary Phone</h6>
                        <p>{{ $patient->phone_secondary ?? '—' }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold fs-13">Digital Address</h6>
                        <p>{{ $patient->digital_address ?? '—' }}</p>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Tab -->
    <div class="tab-pane" id="activity-log">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i>{{ __('patients.patient_activity_log') }}</h6>
            </div>
            @if($activityLogs->isNotEmpty())
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('patients.col_datetime') }}</th>
                                <th>{{ __('patients.col_event') }}</th>
                                <th>{{ __('patients.col_description') }}</th>
                                <th>{{ __('patients.col_changed_by') }}</th>
                                <th>{{ __('patients.col_fields_changed') }}</th>
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

@can('patients.privacy_directives.manage')
<div class="modal fade" id="addPrivacyDirectiveModal" tabindex="-1" aria-labelledby="addPrivacyDirectiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patients.privacy-directives.store', $patient) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPrivacyDirectiveModalLabel">
                        <i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.privacy_directive') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('common.type') }}</label>
                            <select name="directive_type" class="form-select @error('directive_type') is-invalid @enderror" required>
                                @foreach(['do_not_disclose_contact', 'restricted_address', 'restricted_identity', 'restricted_emergency_contact', 'minor_or_guardian_required', 'court_restriction', 'confidential_patient', 'custom'] as $type)
                                    <option value="{{ $type }}" @selected(old('directive_type') === $type)>{{ __('patients.privacy.'.$type) !== 'patients.privacy.'.$type ? __('patients.privacy.'.$type) : str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                            @error('directive_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('common.summary') }}</label>
                            <input type="text" name="summary" class="form-control @error('summary') is-invalid @enderror" value="{{ old('summary') }}" maxlength="255" required>
                            @error('summary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('common.notes') }}</label>
                            <textarea name="details" class="form-control @error('details') is-invalid @enderror" rows="4" maxlength="2000">{{ old('details') }}</textarea>
                            @error('details')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.add') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('patients.privacy.break_glass')
<div class="modal fade" id="startBreakGlassModal" tabindex="-1" aria-labelledby="startBreakGlassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patients.privacy.break-glass.start', $patient) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="startBreakGlassModalLabel">
                        <i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.break_glass_access') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">{{ __('patients.privacy.break_glass_reason') }}</label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="4" minlength="10" required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-shield-lock me-1"></i>{{ __('patients.privacy.start_break_glass_access') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Add Insurance Modal --}}
@can('patients.edit')
    @include('patients.partials.insurance-add-modal', [
        'patient' => $patient,
        'insuranceProviders' => $insuranceProviders,
        'formAction' => route('admin.patients.insurances.store', $patient),
    ])

{{-- Edit Insurance Modal --}}
    @include('patients.partials.insurance-edit-modal', [
        'patient' => $patient,
    ])
@if(false)
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
                            <label class="form-label">Insurance</label>
                            <div id="editInsProviderName" class="form-control-plaintext fw-medium text-muted">-</div>
                        </div>
                        <div class="col-md-6 mb-3">
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control" id="editInsMembership">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control" id="editInsPolicy">
                        </div>
                        {{-- <div class="col-md-6 mb-3">
                            <label class="form-label">CCC Code <small class="text-muted">(optional)</small></label>
                            <input type="text" name="ccc_code" class="form-control" id="editInsCccCode" maxlength="64">
                        </div> --}}
                        <div class="col-md-4 mb-3">
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('patients.update_insurance') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Add Emergency Contact Modal --}}
<div class="modal fade" id="addEmergencyContactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patients.emergency-contacts.store', $patient) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('patients.add_emergency_contact') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.contact_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.phone') }} <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                        <input type="tel" name="phone_secondary" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.relationship') }}</label>
                        <select name="relationship" class="form-select">
                            <option value="">{{ __('patients.select') }}</option>
                            @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                                <option value="{{ $rel }}">{{ __('patients.relationship_options.'.$rel) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="addEcPrimary">
                        <label class="form-check-label" for="addEcPrimary">{{ __('patients.primary_contact') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('patients.add_contact') }}</button>
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
                    <h5 class="modal-title">{{ __('patients.edit_emergency_contact') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.contact_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="editEcName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.phone') }} <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" id="editEcPhone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                        <input type="tel" name="phone_secondary" class="form-control" id="editEcPhoneSecondary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('patients.relationship') }}</label>
                        <select name="relationship" class="form-select" id="editEcRelationship">
                            <option value="">{{ __('patients.select') }}</option>
                            @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                                <option value="{{ $rel }}">{{ __('patients.relationship_options.'.$rel) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="editEcPrimary">
                        <label class="form-check-label" for="editEcPrimary">{{ __('patients.primary_contact') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('patients.update_contact') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ── Mark as Deceased Modal ──────────────────────────────────────────────── --}}
@can('patients.mark_deceased')
@if(!$patient->is_deceased)
<div class="modal fade" id="markDeceasedModal" tabindex="-1" aria-labelledby="markDeceasedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="markDeceasedModalLabel"><i class="ti ti-skull me-2"></i>Mark Patient as Deceased</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.patients.mark-deceased', $patient) }}" id="markDeceasedForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-center mb-3">
                        <i class="ti ti-alert-triangle me-2 fs-18"></i>
                        <span>This action is <strong>permanent</strong>. The patient will be marked as deceased and cannot start new visits. Their history will remain unchanged.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date of Death <span class="text-danger">*</span></label>
                        <input type="date" name="deceased_at" class="form-control @error('deceased_at') is-invalid @enderror"
                            value="{{ old('deceased_at', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                        @error('deceased_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cause of Death <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="cause_of_death" class="form-control @error('cause_of_death') is-invalid @enderror"
                            placeholder="e.g. Cardiac arrest" value="{{ old('cause_of_death') }}" maxlength="255">
                        @error('cause_of_death')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Additional Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="deceased_notes" class="form-control @error('deceased_notes') is-invalid @enderror"
                            rows="3" placeholder="{{ __('patients.additional_notes_placeholder') }}">{{ old('deceased_notes') }}</textarea>
                        @error('deceased_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-check me-1"></i>Confirm — Mark as Deceased</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan

@endsection

@section('scripts')
@include('patients.partials.insurance-edit-modal-scripts')
<script>
(function () {
    @php
        $patientShowI18nData = [
            'selectTypeFirst' => __('patients.select_type_first'),
            'selectProviderFirst' => __('patients.select_provider_first'),
            'loadingProviders' => __('patients.loading_providers'),
            'noProvidersForType' => __('patients.no_providers_for_type'),
            'selectProvider' => __('patients.select_provider'),
            'failedLoadProviders' => __('patients.failed_load_providers'),
            'loadingTiers' => __('patients.loading_tiers'),
            'noTiers' => __('patients.no_tiers'),
            'selectTier' => __('patients.select_tier'),
            'failedLoadTiers' => __('patients.failed_load_tiers'),
            'coverageSuffix' => __('patients.coverage_suffix', ['percentage' => ':percentage']),
        ];
    @endphp
    const patientI18n = @json($patientShowI18nData);
    // ── Auto-open Mark as Deceased modal on validation error ─────────────────
    @if($errors->any() && old('deceased_at'))
    var deceasedModal = document.getElementById('markDeceasedModal');
    if (deceasedModal) {
        new bootstrap.Modal(deceasedModal).show();
    }
    @endif

    // ── Edit Insurance Modal ──────────────────────────────────────────────────
    @if($errors->has('directive_type') || $errors->has('summary') || $errors->has('details'))
    var privacyDirectiveModal = document.getElementById('addPrivacyDirectiveModal');
    if (privacyDirectiveModal) {
        new bootstrap.Modal(privacyDirectiveModal).show();
    }
    @endif

    @if($errors->has('reason'))
    var breakGlassModal = document.getElementById('startBreakGlassModal');
    if (breakGlassModal) {
        new bootstrap.Modal(breakGlassModal).show();
    }
    @endif

    document.querySelectorAll('.edit-insurance-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('editInsuranceForm').action = '{{ url("admin/patients/" . $patient->id . "/insurances") }}/' + id;
            document.getElementById('editInsProviderId').value = this.dataset.provider || '';
            var providerNameEl = document.getElementById('editInsProviderName');
            if (providerNameEl) providerNameEl.textContent = this.dataset.providerName || '-';
            document.getElementById('editInsMembership').value = this.dataset.membership || '';
            document.getElementById('editInsPolicy').value = this.dataset.policy || '';
            var cccEl = document.getElementById('editInsCccCode');
            if (cccEl) cccEl.value = this.dataset.cccCode || '';
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

    function resetProviderSelect(message = patientI18n.selectTypeFirst) {
        providerSelect.innerHTML = '<option value="">' + message + '</option>';
        providerSelect.disabled = true;
    }

    function resetTierSelect(message = patientI18n.selectProviderFirst) {
        tierSelect.innerHTML = '<option value="">' + message + '</option>';
        tierSelect.disabled = true;
        if (tierInfo) tierInfo.textContent = '';
    }

    // ── Add Insurance: Type → Provider cascade ──────────────────────────────
    typeSelect.addEventListener('change', function() {
        const type = this.value;
        resetProviderSelect(type ? patientI18n.loadingProviders : patientI18n.selectTypeFirst);
        resetTierSelect();

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetProviderSelect(patientI18n.noProvidersForType);
                return;
            }

            providerSelect.innerHTML = '<option value="">' + patientI18n.selectProvider + '</option>';
            providers.forEach(provider => {
                const opt = document.createElement('option');
                opt.value = provider.id;
                opt.textContent = provider.short_name ? provider.name + ' (' + provider.short_name + ')' : provider.name;
                providerSelect.appendChild(opt);
            });
            providerSelect.disabled = false;
        })
        .catch(() => {
            resetProviderSelect(patientI18n.failedLoadProviders);
        });
    });

    // ── Add Insurance: Provider → Tier cascade ───────────────────────────────
    providerSelect.addEventListener('change', function() {
        const providerId = this.value;

        if (!providerId) {
            resetTierSelect();
            return;
        }

        tierSelect.innerHTML = '<option value="">' + patientI18n.loadingTiers + '</option>';
        tierSelect.disabled = true;

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                tierSelect.innerHTML = '<option value="">' + patientI18n.noTiers + '</option>';
                return;
            }
            tierSelect.innerHTML = '<option value="">' + patientI18n.selectTier + '</option>';
            tiers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.coverage_percentage ? ' (' + patientI18n.coverageSuffix.replace(':percentage', t.coverage_percentage) + ')' : '');
                if (t.is_default) opt.selected = true;
                tierSelect.appendChild(opt);
            });
            tierSelect.disabled = false;
            updateTierInfo();
        })
        .catch(() => {
            tierSelect.innerHTML = '<option value="">' + patientI18n.failedLoadTiers + '</option>';
        });
    });

    document.getElementById('addInsTier').addEventListener('change', updateTierInfo);

    function updateTierInfo() {
        const sel = document.getElementById('addInsTier');
        const opt = sel.options[sel.selectedIndex];
        const info = document.getElementById('addInsTierInfo');
        if (info) info.textContent = opt && opt.value ? opt.textContent : '';
    }

    // ── Add Insurance: Member Type → show/hide card holder row ───────────────
    document.querySelectorAll('input[name="member_type"]').forEach(r => {
        r.addEventListener('change', function() {
            const row = document.getElementById('addCardHolderRow');
            const sel = document.getElementById('addCardHolder');
            if (!row || !sel) return;
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
