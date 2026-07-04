@extends('layouts.app')
@section('title', __('admissions.create_title'))

@section('content')
<x-page-header :title="__('admissions.create_title')" icon="ti-bed-filled">
    <x-slot:actions>
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.back_to_admissions') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.admissions.store') }}" id="admissionForm">
    @csrf
    @if($preselectedAdmissionRequest ?? null)
        <input type="hidden" name="admission_request_id" value="{{ $preselectedAdmissionRequest->id }}">
    @endif
<div class="row g-3">
    {{-- ========== LEFT COLUMN ========== --}}
    <div class="col-lg-8">

        {{-- PATIENT / VISIT SELECTION --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-user-heart me-2 text-primary"></i>{{ __('admissions.patient_visit') }}</h6>
            </div>
            <div class="card-body">
                @if($preselectedVisit)
                    <input type="hidden" name="visit_id"   value="{{ $preselectedVisit->id }}">
                    <input type="hidden" name="patient_id" value="{{ $preselectedVisit->patient_id }}">
                    <div class="alert alert-teal d-flex align-items-center mb-0 py-2">
                        <i class="ti ti-user-check fs-4 me-3"></i>
                        <div>
                            <strong>{{ $preselectedVisit->patient->full_name }}</strong>
                            <span class="badge bg-secondary ms-2">{{ $preselectedVisit->patient->patient_number }}</span>
                            <span class="badge bg-primary ms-1">Visit: {{ $preselectedVisit->visit_number }}</span>
                            @if($preselectedAdmissionRequest ?? null)
                                <span class="badge bg-info ms-1">{{ __('admissions.request_ref', ['id' => $preselectedAdmissionRequest->id]) }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">{{ __('admissions.visit_awaiting') }} <span class="text-danger">*</span></label>
                            <select name="visit_id" id="visitSelect" class="form-select" required>
                                <option value="">{{ __('admissions.select_visit') }}</option>
                                @foreach($admittingVisits as $v)
                                    @php
                                        $vIns = $v->visitInsurance;
                                        $vProvider = $vIns?->insuranceProvider;
                                        $vTier = $vIns?->insuranceTier;
                                        $vHasReal = $vIns && $vIns->is_active && $vProvider && !$vProvider->is_default;
                                    @endphp
                                    <option value="{{ $v->id }}"
                                            data-patient-id="{{ $v->patient_id }}"
                                            data-patient-name="{{ $v->patient->full_name }}"
                                            data-patient-number="{{ $v->patient->patient_number }}"
                                            data-patient-age="{{ $v->patient->age ?? 'N/A' }}"
                                            data-patient-gender="{{ $v->patient->gender->label() ?? '' }}"
                                            data-patient-phone="{{ $v->patient->phone ?? '' }}"
                                            data-patient-allergies="{{ $v->patient->allergies ?? '' }}"
                                            data-patient-chronic="{{ $v->patient->chronic_conditions ?? '' }}"
                                            data-ins-has="{{ $vHasReal ? '1' : '0' }}"
                                            data-ins-provider="{{ $vHasReal ? e($vProvider->name) : 'Cash & Carry' }}"
                                            data-ins-tier="{{ $vHasReal ? e($vTier?->name ?? '') : '' }}"
                                            data-ins-coverage="{{ $vHasReal ? ($vTier?->coverage_percentage ?? 0) : 0 }}"
                                            data-ins-valid="{{ ($vIns && $vIns->is_valid) ? '1' : '0' }}"
                                            data-ins-expired="{{ ($vIns && $vIns->is_expired) ? '1' : '0' }}"
                                            data-ins-annual-remaining="{{ $vHasReal ? ($vIns->remaining_annual_limit ?? '') : '' }}"
                                            data-ins-monthly-remaining="{{ $vHasReal ? ($vIns->remaining_monthly_limit ?? '') : '' }}"
                                            data-ins-membership="{{ $vHasReal ? e($vIns->membership_number ?? '') : '' }}"
                                            {{ old('visit_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->visit_number }} — {{ $v->patient->full_name }} ({{ $v->patient->patient_number }})
                                    </option>
                                @endforeach
                            </select>
                            @if($admittingVisits->isEmpty())
                                <small class="text-danger"><i class="ti ti-alert-circle me-1"></i>{{ __('admissions.no_visits_waiting') }}</small>
                            @else
                                <small class="text-muted">{{ __('admissions.showing_visits', ['count' => $admittingVisits->count()]) }}</small>
                            @endif
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">{{ __('admissions.patient') }}</label>
                            <input type="text" id="patientDisplay" class="form-control bg-light" readonly placeholder="{{ __('admissions.auto_filled_from_visit') }}">
                            <input type="hidden" name="patient_id" id="patientId" value="{{ old('patient_id') }}">
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ADMISSION TYPE --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-clipboard-list me-2 text-info"></i>{{ __('admissions.admission_type') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-check form-check-card">
                            <input class="form-check-input" type="radio" name="admission_type" id="typeAdmission"
                                   value="admission" {{ old('admission_type', 'admission') === 'admission' ? 'checked' : '' }}>
                            <span class="form-check-label p-3 d-block border rounded text-center" style="cursor:pointer">
                                <i class="ti ti-bed fs-2 d-block mb-1 text-primary"></i>
                                <strong>{{ __('admissions.type_admission') }}</strong>
                                <small class="d-block text-muted">{{ __('admissions.stay_over_24h') }}</small>
                            </span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-check form-check-card">
                            <input class="form-check-input" type="radio" name="admission_type" id="typeDetention"
                                   value="detention" {{ old('admission_type') === 'detention' ? 'checked' : '' }}>
                            <span class="form-check-label p-3 d-block border rounded text-center" style="cursor:pointer">
                                <i class="ti ti-clock-hour-4 fs-2 d-block mb-1 text-warning"></i>
                                <strong>{{ __('admissions.type_detention') }}</strong>
                                <small class="d-block text-muted">{{ __('admissions.stay_under_24h') }}</small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- WARD & BED --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-building-hospital me-2 text-success"></i>{{ __('admissions.ward_and_bed') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('admissions.filter_by_ward') }}</label>
                        <select id="wardFilter" class="form-select">
                            <option value="">{{ __('admissions.all_wards_option') }}</option>
                            @foreach($wards as $ward)
                                <option value="{{ $ward->id }}">{{ $ward->name }} ({{ $ward->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('admissions.bed_label') }} <span class="text-danger">*</span></label>
                        <select name="bed_id" id="bedSelect" class="form-select" required>
                            <option value="">{{ __('admissions.select_bed') }}</option>
                            @foreach($availableBeds as $bed)
                                <option value="{{ $bed->id }}"
                                        data-ward="{{ $bed->ward_id }}"
                                        data-rate="{{ $bed->daily_rate }}"
                                        data-ward-name="{{ $bed->ward->name }}"
                                        data-bed-number="{{ $bed->bed_number }}"
                                        data-bed-type="{{ $bed->bed_type->label() }}"
                                        {{ (old('bed_id', $preselectedBedId ?? '') == $bed->id) ? 'selected' : '' }}>
                                    {{ $bed->ward->name }} — {{ $bed->bed_number }} ({{ $bed->bed_type->label() }}) — GH₵{{ number_format($bed->daily_rate, 2) }}/day
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- DATES --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-calendar me-2 text-orange"></i>{{ __('admissions.dates_section') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('admissions.admission_date') }}</label>
                        <input type="datetime-local" name="admission_date" id="admissionDate" class="form-control"
                               value="{{ old('admission_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('admissions.expected_discharge') }}</label>
                        <input type="date" name="expected_discharge_date" id="expectedDischarge" class="form-control"
                               value="{{ old('expected_discharge_date') }}" min="{{ now()->addDay()->format('Y-m-d') }}">
                        <small class="text-muted">{{ __('admissions.billing_estimate_hint') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ADMITTING DIAGNOSIS --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-stethoscope me-2 text-purple"></i>{{ __('admissions.admitting_diagnosis') }}</h6>
            </div>
            <div class="card-body">
                <textarea name="admitting_diagnosis" class="form-control" rows="3"
                          placeholder="{{ __('admissions.admitting_diagnosis_ph') }}">{{ old('admitting_diagnosis', $preselectedAdmissionRequest->provisional_diagnosis ?? '') }}</textarea>
            </div>
        </div>

        {{-- FEE SERVICES --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-receipt me-2 text-cyan"></i>{{ __('admissions.fee_services') }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" id="admFeeServiceLabel">{{ __('admissions.admission_fee_service') }}</label>
                        <select name="admission_fee_service_id" id="admissionFeeService" class="form-select"
                                data-default-admission="{{ $defaultAdmissionFeeServiceId ?? '' }}"
                                data-default-detention="{{ $defaultDetentionFeeServiceId ?? '' }}">
                            <option value="">{{ __('admissions.none_manual') }}</option>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}" data-price="{{ $svc->price }}"
                                        {{ old('admission_fee_service_id', $defaultAdmissionFeeServiceId) == $svc->id ? 'selected' : '' }}>
                                    {{ $svc->name }} — GH₵{{ number_format($svc->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('admissions.one_time_fee') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('admissions.consumable_daily_fee') }}</label>
                        <select name="consumable_fee_service_id" id="consumableFeeService" class="form-select">
                            <option value="">{{ __('admissions.none_manual') }}</option>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}" data-price="{{ $svc->price }}"
                                        {{ old('consumable_fee_service_id', $defaultConsumableFeeServiceId) == $svc->id ? 'selected' : '' }}>
                                    {{ $svc->name }} — GH₵{{ number_format($svc->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('admissions.per_day_consumables') }}</small>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /col-lg-8 --}}

    {{-- ========== RIGHT COLUMN ========== --}}
    <div class="col-lg-4">

        {{-- Ensure patient-card styles are always injected (even when @include below is skipped) --}}
        @include('partials.patient-card', ['patient' => null])

        {{-- PATIENT INFO --}}
        @if($preselectedVisit)
        @include('partials.patient-card', [
            'patient' => $preselectedVisit->patient,
            'visit'   => $preselectedVisit,
        ])
        {{-- <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold">Patient Details</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0 px-2">
                    <tr><td class="text-muted ps-3">Name</td><td class="fw-medium">{{ $preselectedVisit->patient->full_name }}</td></tr>
                    <tr><td class="text-muted ps-3">ID</td><td>{{ $preselectedVisit->patient->patient_number }}</td></tr>
                    <tr><td class="text-muted ps-3">Age / Gender</td><td>{{ $preselectedVisit->patient->age ?? 'N/A' }} / {{ $preselectedVisit->patient->gender->label() }}</td></tr>
                    <tr><td class="text-muted ps-3">Phone</td><td>{{ $preselectedVisit->patient->phone ?? 'N/A' }}</td></tr>
                    @if($preselectedVisit->patient->allergies)
                    <tr><td class="text-muted ps-3">Allergies</td><td class="text-danger fw-semibold">{{ $preselectedVisit->patient->allergies }}</td></tr>
                    @endif
                </table></div>
            </div>
        </div> --}}
        @else
        {{-- Dynamic patient card skeleton (same look as the reusable patient-card partial) --}}
        <div class="card patient-card shadow-sm border-0 mb-3 d-none" id="patientInfoCard">
            <div class="patient-card__header">
                <div class="patient-card__avatar">
                    <span id="infoInitials">?</span>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="patient-card__name" id="infoName">—</span>
                        <span class="badge badge-soft-secondary fs-11 fw-medium" id="infoNumber"></span>
                    </div>
                    <div class="patient-card__meta">
                        <span><i class="ti ti-user fs-12"></i> <span id="infoAge">—</span></span>
                        <span><i class="ti ti-phone fs-12"></i> <span id="infoPhone">—</span></span>
                    </div>
                </div>
                <a href="#" class="btn btn-sm btn-outline-primary patient-card__profile-btn d-none" id="infoProfileLink" target="_blank" title="View patient profile">
                    <i class="ti ti-external-link"></i>
                </a>
            </div>
            <div class="patient-card__alerts" id="infoAlertsSection" style="display:none!important">
                <div class="patient-card__alert patient-card__alert--danger d-none" id="allergyRow">
                    <i class="ti ti-alert-triangle"></i>
                    <div>
                        <small class="fw-semibold d-block">Allergies</small>
                        <span class="fs-13" id="infoAllergies"></span>
                    </div>
                </div>
                <div class="patient-card__alert patient-card__alert--warning d-none" id="chronicRow">
                    <i class="ti ti-heart-rate-monitor"></i>
                    <div>
                        <small class="fw-semibold d-block">Chronic Conditions</small>
                        <span class="fs-13" id="infoChronic"></span>
                    </div>
                </div>
            </div>
            <div class="patient-card__insurance d-none" id="patientCardInsurance">
                <div class="patient-card__insurance-row" id="patientCardInsuranceRow">
                    {{-- Populated by updateInsurancePanel() JS --}}
                </div>
            </div>
        </div>
        @endif

        {{-- INSURANCE CARD (static for preselected, dynamic for dropdown) --}}
        @if($preselectedVisit)
        @php
            $ins = $preselectedVisit->visitInsurance;
            $insProvider = $ins?->insuranceProvider;
            $insTier = $ins?->insuranceTier;
            $hasRealIns = $ins && $ins->is_active && $insProvider && !$insProvider->is_default;
            $memberType = $ins?->member_type?->value ?? 'holder';
            $insConstraints = $insTier?->effectiveConstraints($memberType);
        @endphp
        {{-- <div class="card mb-3 {{ $hasRealIns ? ($ins->is_valid ? 'border-info' : 'border-danger') : 'border-secondary' }}">
            <div class="card-header py-2 {{ $hasRealIns ? ($ins->is_valid ? 'bg-info text-white' : 'bg-danger text-white') : 'bg-light' }}">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-shield-half me-2"></i>Insurance Coverage</h6>
            </div>
            <div class="card-body">
                @if($hasRealIns)
                    @if(!$ins->is_valid)
                    <div class="alert alert-danger py-1 mb-2 small"><i class="ti ti-alert-triangle me-1"></i>Insurance is <strong>{{ $ins->is_expired ? 'expired' : 'inactive' }}</strong> — billing will be CASH</div>
                    @endif
                    <div class="table-responsive"><table class="table table-sm table-borderless mb-2">
                        <tr><td class="text-muted">Provider</td><td class="fw-semibold">{{ $insProvider->name }}</td></tr>
                        <tr><td class="text-muted">Tier</td><td>{{ $insTier?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Member</td><td>{{ $ins->membership_number ?? '—' }} <span class="badge bg-secondary ms-1">{{ ucfirst($memberType) }}</span></td></tr>
                        <tr><td class="text-muted">Coverage</td><td><strong class="text-success">{{ $insConstraints['coverage_percentage'] ?? 0 }}%</strong></td></tr>
                        @if($insConstraints['per_visit_limit'])
                        <tr><td class="text-muted">Per Visit Limit</td><td>GH₵{{ number_format($insConstraints['per_visit_limit'], 2) }} <small class="text-muted">(remaining: GH₵{{ number_format($ins->remaining_annual_limit ?? 0, 2) }})</small></td></tr>
                        @endif
                        @if($insConstraints['annual_limit'])
                        <tr>
                            <td class="text-muted">Annual Limit</td>
                            <td>
                                @php $remAnnual = $ins->remaining_annual_limit; @endphp
                                GH₵{{ number_format($insConstraints['annual_limit'], 2) }}
                                @if($remAnnual !== null)
                                <small class="d-block {{ $remAnnual < ($insConstraints['annual_limit'] * 0.1) ? 'text-danger' : 'text-muted' }}">
                                    Remaining: GH₵{{ number_format($remAnnual, 2) }}
                                    @if($remAnnual <= 0) <span class="badge bg-danger ms-1">Exhausted</span>@endif
                                </small>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($insConstraints['max_per_month'])
                        <tr>
                            <td class="text-muted">Monthly Limit</td>
                            <td>
                                @php $remMonthly = $ins->remaining_monthly_limit; @endphp
                                GH₵{{ number_format($insConstraints['max_per_month'], 2) }}
                                @if($remMonthly !== null)
                                <small class="d-block {{ $remMonthly < ($insConstraints['max_per_month'] * 0.1) ? 'text-danger' : 'text-muted' }}">
                                    Remaining: GH₵{{ number_format($remMonthly, 2) }}
                                    @if($remMonthly <= 0) <span class="badge bg-danger ms-1">Exhausted</span>@endif
                                </small>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @if($insConstraints['expiry_date'] ?? false)
                        <tr><td class="text-muted">Expires</td><td>{{ $ins->expiry_date->format('d M Y') }}</td></tr>
                        @endif
                    </table></div>
                    @if($ins->is_valid && ($insConstraints['annual_limit'] ?? null) !== null && ($ins->remaining_annual_limit ?? 0) <= 0)
                    <div class="alert alert-warning py-1 small mb-0"><i class="ti ti-alert-circle me-1"></i>Annual limit exhausted — billing will be CASH</div>
                    @endif
                @else
                    <div class="text-center text-muted py-2">
                        <i class="ti ti-cash fs-2 d-block mb-1"></i>
                        <strong>Cash & Carry</strong><br>
                        <small>No active insurance for this visit</small>
                    </div>
                @endif
            </div>
        </div> --}}
        @else
        {{-- (insurance now rendered inline inside #patientInfoCard above) --}}
        @endif

        {{-- BILLING SUMMARY --}}
        <div class="card mb-3 border-primary">
            <div class="card-header py-2 bg-primary text-white">
                <h6 class="card-title mb-0 fw-semibold"><i class="ti ti-cash me-2"></i>{{ __('admissions.billing_preview') }}</h6>
            </div>
            <div class="card-body pb-0">
                <div id="billingDaysInfo" class="alert alert-light py-1 text-center mb-3 small text-muted">
                    {{ __('admissions.set_discharge_hint') }}
                </div>
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admissions.description') }}</th>
                            <th class="text-center">{{ __('admissions.qty') }}</th>
                            <th class="text-end">{{ __('admissions.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span id="admFeeLabel">{{ __('admissions.admission_fee') }}</span>
                                <small class="d-block text-muted">{{ __('admissions.one_time_label') }}</small>
                            </td>
                            <td class="text-center">1</td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="width:100px;margin-left:auto">
                                    <span class="input-group-text p-1 small">GH₵</span>
                                    <input type="number" name="admission_fee_amount" id="admFeeInput" class="form-control form-control-sm text-end billing-input"
                                           min="0" step="0.01" value="{{ old('admission_fee_amount', 0) }}" placeholder="0.00">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {{ __('admissions.bed_fee') }}
                                <small class="d-block text-muted" id="bedRateHint">{{ __('admissions.select_bed_hint') }}</small>
                            </td>
                            <td class="text-center" id="daysQty">1</td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="width:100px;margin-left:auto">
                                    <span class="input-group-text p-1 small">GH₵</span>
                                    <input type="number" name="bed_fee_amount" id="bedFeeInput" class="form-control form-control-sm text-end billing-input"
                                           min="0" step="0.01" value="{{ old('bed_fee_amount', 0) }}" placeholder="0.00" readonly>
                                </div>
                                <small class="text-muted" id="bedFeeCalc">per day × days</small>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                {{ __('admissions.consumable_fee') }}
                                <small class="d-block text-muted">{{ __('admissions.daily_label') }}</small>
                            </td>
                            <td class="text-center" id="daysQty2">1</td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="width:100px;margin-left:auto">
                                    <span class="input-group-text p-1 small">GH₵</span>
                                    <input type="number" name="consumable_fee_amount" id="consumableFeeInput" class="form-control form-control-sm text-end billing-input"
                                           min="0" step="0.01" value="{{ old('consumable_fee_amount', 0) }}" placeholder="0.00">
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-top-2">
                        <tr id="insRow" class="text-success d-none">
                            <td colspan="2"><i class="ti ti-shield-check me-1"></i>{{ __('admissions.insurance_coverage_row') }}</td>
                            <td class="text-end text-success" id="insCoveredDisplay">— GH₵ 0.00</td>
                        </tr>
                        <tr id="patientPayRow" class="text-info d-none">
                            <td colspan="2">{{ __('admissions.patient_pays') }}</td>
                            <td class="text-end text-info" id="patientPayDisplay">GH₵ 0.00</td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="2">{{ __('admissions.estimated_total') }}</td>
                            <td class="text-end text-primary" id="billingTotal">GH₵ 0.00</td>
                        </tr>
                    </tfoot>
                </table></div>
                <small class="text-muted d-block pb-2 text-center">{{ __('admissions.amounts_editable') }}</small>
            </div>
        </div>

        {{-- AVAILABLE BEDS SUMMARY --}}
        <div class="card mb-3">
            <div class="card-header py-2 bg-light">
                <h6 class="card-title mb-0 fw-semibold">{{ __('admissions.available_beds') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>{{ __('admissions.ward_col') }}</th><th class="text-center">{{ __('admissions.free_col') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($wards as $ward)
                        @php $free = $availableBeds->where('ward_id', $ward->id)->count(); @endphp
                        <tr>
                            <td>{{ $ward->name }}</td>
                            <td class="text-center">
                                <span class="badge {{ $free > 0 ? 'bg-success' : 'bg-danger' }}">{{ $free }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        </div>

        {{-- SUBMIT --}}
        <div class="card border-success">
            <div class="card-body text-center py-3">
                <button type="submit" class="btn btn-success btn-lg w-100" id="submitBtn">
                    <i class="ti ti-bed-filled me-2"></i>{{ __('admissions.complete_admission') }}
                </button>
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-sm mt-2 w-100">{{ __('admissions.cancel') }}</a>
            </div>
        </div>
    </div>{{-- /col-lg-4 --}}
</div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ─── State ───────────────────────────────────────────────────────────
    var bedRate        = 0;
    var admFeeRate     = 0;
    var consumableRate = 0;
    var days           = 1;
    var insuranceCovPct = 0;   // effective coverage %
    var hasInsurance    = false;

    // Preselected visit insurance (PHP-injected)
    @if($preselectedVisit)
    @php
        $preIns = $preselectedVisit->visitInsurance;
        $preInsProvider = $preIns?->insuranceProvider;
        $preInsTier = $preIns?->insuranceTier;
        $preHasReal = $preIns && $preIns->is_active && $preInsProvider && !$preInsProvider->is_default && $preIns->is_valid;
        $preInsConstraints = $preInsTier?->effectiveConstraints($preIns?->member_type?->value ?? 'holder');
    @endphp
    hasInsurance   = {{ $preHasReal ? 'true' : 'false' }};
    insuranceCovPct = {{ $preHasReal ? ($preInsConstraints['coverage_percentage'] ?? 0) : 0 }};
    @endif

    // ─── Helpers ─────────────────────────────────────────────────────────
    function fmt(n) { return 'GH₵ ' + parseFloat(n || 0).toFixed(2); }

    function calcDays() {
        var admDate = document.getElementById('admissionDate').value;
        var disDate = document.getElementById('expectedDischarge').value;
        if (!admDate || !disDate) return 1;
        var a = new Date(admDate), d = new Date(disDate);
        var diff = Math.round((d - a) / (1000 * 60 * 60 * 24));
        return Math.max(1, diff);
    }

    function refreshBilling() {
        days = calcDays();

        var admFee     = parseFloat(document.getElementById('admFeeInput').value || 0);
        var bedPerDay  = bedRate;
        var consPerDay = parseFloat(document.getElementById('consumableFeeInput').value || 0);

        var bedTotal   = bedPerDay * days;
        var consTotal  = consPerDay * days;
        var grandTotal = admFee + bedTotal + consTotal;

        document.getElementById('daysQty').textContent  = days;
        document.getElementById('daysQty2').textContent = days;

        var bedFeeInput = document.getElementById('bedFeeInput');
        bedFeeInput.value = bedTotal.toFixed(2);
        document.getElementById('bedFeeCalc').textContent = 'GH₵ ' + bedPerDay.toFixed(2) + '/day × ' + days + ' day(s)';
        document.getElementById('bedRateHint').textContent = bedRate > 0
            ? 'GH₵ ' + bedRate.toFixed(2) + '/day'
            : 'Select a bed';

        document.getElementById('billingTotal').textContent = fmt(grandTotal);

        // Insurance coverage row
        var insRow       = document.getElementById('insRow');
        var patRow       = document.getElementById('patientPayRow');
        if (hasInsurance && insuranceCovPct > 0 && grandTotal > 0) {
            var covered    = Math.round(grandTotal * insuranceCovPct / 100 * 100) / 100;
            var patientPay = Math.round((grandTotal - covered) * 100) / 100;
            insRow.classList.remove('d-none');
            patRow.classList.remove('d-none');
            document.getElementById('insCoveredDisplay').textContent = '— ' + fmt(covered) + ' (' + insuranceCovPct + '%)';
            document.getElementById('patientPayDisplay').textContent = fmt(patientPay);
        } else {
            insRow.classList.add('d-none');
            patRow.classList.add('d-none');
        }

        var daysInfo = document.getElementById('billingDaysInfo');
        if (days > 1) {
            daysInfo.textContent = days + ' day(s) estimated stay';
            daysInfo.className = 'alert alert-info py-1 text-center mb-3 small';
        } else {
            daysInfo.textContent = 'Set expected discharge date to calculate billing';
            daysInfo.className = 'alert alert-light py-1 text-center mb-3 small text-muted';
        }
    }

    // ─── Insurance Panel (dynamic) ────────────────────────────────────────
    function updateInsurancePanel(opt) {
        var insSec = document.getElementById('patientCardInsurance');
        var insRow = document.getElementById('patientCardInsuranceRow');
        // Fallback for legacy separate card (no longer rendered in the dropdown branch, but kept safe)
        var card = document.getElementById('insuranceCard');

        if (!insSec) return; // preselected case — static panel

        if (!opt || !opt.value) {
            insSec.classList.add('d-none');
            if (card) card.classList.add('d-none');
            hasInsurance = false;
            insuranceCovPct = 0;
            refreshBilling();
            return;
        }

        var has      = opt.dataset.insHas === '1';
        var valid    = opt.dataset.insValid === '1';
        var expired  = opt.dataset.insExpired === '1';
        var provider = opt.dataset.insProvider || 'Cash & Carry';
        var tier     = opt.dataset.insTier || '';
        var coverage = parseFloat(opt.dataset.insCoverage || 0);
        var annual   = opt.dataset.insAnnualRemaining;
        var monthly  = opt.dataset.insMonthlyRemaining;
        var membership = opt.dataset.insMembership || '';

        hasInsurance    = has && valid;
        insuranceCovPct = hasInsurance ? coverage : 0;

        insSec.classList.remove('d-none');

        if (has) {
            var iconCls  = valid ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger';
            var iconName = valid ? 'ti-shield-check' : 'ti-shield-off';
            var badgeHtml = expired
                ? '<span class="badge bg-danger fs-11">Expired</span>'
                : (valid ? '<span class="badge bg-success fs-11">Active</span>' : '<span class="badge bg-secondary fs-11">Inactive</span>');
            var meta = [];
            if (tier) meta.push(tier);
            if (has && valid && coverage) meta.push(coverage + '% coverage');
            if (membership) meta.push('#' + membership);

            insRow.innerHTML =
                '<div class="patient-card__insurance-icon ' + iconCls + '">' +
                    '<i class="ti ' + iconName + '"></i>' +
                '</div>' +
                '<div class="flex-grow-1 min-w-0">' +
                    '<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">' +
                        '<span class="fw-semibold text-truncate">' + provider + '</span>' +
                        badgeHtml +
                    '</div>' +
                    '<small class="text-muted d-block">' + (meta.join(' · ') || 'No coverage details') + '</small>' +
                    ((!valid && annual !== '' && parseFloat(annual) <= 0) ? '<small class="text-danger d-block"><i class="ti ti-alert-circle me-1"></i>Limit exhausted — billed as CASH</small>' : '') +
                '</div>';
        } else {
            insRow.innerHTML =
                '<div class="patient-card__insurance-icon bg-secondary bg-opacity-10 text-secondary">' +
                    '<i class="ti ti-cash"></i>' +
                '</div>' +
                '<div>' +
                    '<span class="fw-semibold">Cash &amp; Carry</span>' +
                    '<small class="text-muted d-block">No active insurance for this visit</small>' +
                '</div>';
        }

        refreshBilling();
    }

    // ─── Admission Type Labels + Fee Service Swap ────────────────────────
    function updateTypeLabel() {
        var t = document.querySelector('input[name="admission_type"]:checked');
        var isDetention = t && t.value === 'detention';
        var label = isDetention ? 'Detention Fee' : 'Admission Fee';
        document.getElementById('admFeeLabel').textContent = label;

        // Swap the fee-service label
        var serviceLabel = document.getElementById('admFeeServiceLabel');
        if (serviceLabel) serviceLabel.textContent = isDetention ? 'Detention Fee Service' : 'Admission Fee Service';

        // Swap pre-selected default only when the user hasn't already made a choice
        var sel = document.getElementById('admissionFeeService');
        if (sel && !sel.dataset.userPicked) {
            var defaultId = isDetention ? sel.dataset.defaultDetention : sel.dataset.defaultAdmission;
            if (defaultId) sel.value = defaultId;
        }
    }

    document.getElementById('admissionFeeService').addEventListener('change', function() {
        this.dataset.userPicked = '1';
    });

    document.querySelectorAll('input[name="admission_type"]').forEach(function(el) {
        el.addEventListener('change', updateTypeLabel);
        el.addEventListener('change', function() {
            document.querySelectorAll('input[name="admission_type"]').forEach(function(r) {
                r.closest('.form-check').querySelector('.form-check-label').classList.toggle('border-primary', r.checked);
                r.closest('.form-check').querySelector('.form-check-label').classList.toggle('bg-light', r.checked);
            });
        });
    });

    // ─── Bed Selection ───────────────────────────────────────────────────
    document.getElementById('wardFilter').addEventListener('change', function() {
        var wid = this.value;
        var bedSel = document.getElementById('bedSelect');
        Array.from(bedSel.options).forEach(function(opt) {
            if (!opt.value) return;
            opt.hidden = !!(wid && opt.dataset.ward != wid);
            if (opt.hidden && opt.selected) { bedSel.value = ''; bedRate = 0; refreshBilling(); }
        });
    });

    document.getElementById('bedSelect').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        bedRate = opt.value ? parseFloat(opt.dataset.rate || 0) : 0;
        refreshBilling();
    });

    // ─── Date Changes ────────────────────────────────────────────────────
    document.getElementById('admissionDate').addEventListener('change', refreshBilling);
    document.getElementById('expectedDischarge').addEventListener('change', refreshBilling);

    // ─── Service Fee Selectors ───────────────────────────────────────────
    document.getElementById('admissionFeeService').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        admFeeRate = opt.value ? parseFloat(opt.dataset.price || 0) : 0;
        document.getElementById('admFeeInput').value = admFeeRate.toFixed(2);
        refreshBilling();
    });

    document.getElementById('consumableFeeService').addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        consumableRate = opt.value ? parseFloat(opt.dataset.price || 0) : 0;
        document.getElementById('consumableFeeInput').value = consumableRate.toFixed(2);
        refreshBilling();
    });

    // ─── Manual Fee Override ─────────────────────────────────────────────
    document.getElementById('admFeeInput').addEventListener('input', refreshBilling);
    document.getElementById('consumableFeeInput').addEventListener('input', refreshBilling);

    // ─── Visit Selector ──────────────────────────────────────────────────
    var visitSel = document.getElementById('visitSelect');
    if (visitSel) {
        visitSel.addEventListener('change', function() {
            var opt = this.options[this.selectedIndex];
            if (opt.value) {
                document.getElementById('patientId').value       = opt.dataset.patientId;
                document.getElementById('patientDisplay').value  = opt.dataset.patientName + ' (' + opt.dataset.patientNumber + ')';
                // Populate patient-card skeleton
                var name    = opt.dataset.patientName   || '';
                var number  = opt.dataset.patientNumber || '';
                var parts   = name.split(' ');
                var initials = ((parts[0] || '').charAt(0) + (parts[parts.length - 1] || '').charAt(0)).toUpperCase();
                document.getElementById('infoInitials').textContent = initials || '?';
                document.getElementById('infoName').textContent   = name;
                document.getElementById('infoNumber').textContent  = number;
                document.getElementById('infoAge').textContent    = (opt.dataset.patientAge || 'N/A') + ' yrs · ' + (opt.dataset.patientGender || '');
                document.getElementById('infoPhone').textContent  = opt.dataset.patientPhone || 'N/A';
                var alertsSection = document.getElementById('infoAlertsSection');
                var allergyRow = document.getElementById('allergyRow');
                var chronicRow = document.getElementById('chronicRow');
                if (opt.dataset.patientAllergies) {
                    allergyRow.classList.remove('d-none');
                    document.getElementById('infoAllergies').textContent = opt.dataset.patientAllergies;
                } else {
                    allergyRow.classList.add('d-none');
                }
                if (opt.dataset.patientChronic) {
                    chronicRow.classList.remove('d-none');
                    document.getElementById('infoChronic').textContent = opt.dataset.patientChronic;
                } else {
                    chronicRow.classList.add('d-none');
                }
                var hasAlerts = opt.dataset.patientAllergies || opt.dataset.patientChronic;
                alertsSection.style.removeProperty('display');
                if (!hasAlerts) alertsSection.style.setProperty('display', 'none', 'important');
                document.getElementById('patientInfoCard').classList.remove('d-none');
                updateInsurancePanel(opt);
            } else {
                document.getElementById('patientId').value = '';
                document.getElementById('patientDisplay').value = '';
                document.getElementById('patientInfoCard').classList.add('d-none');
                updateInsurancePanel(null);
            }
        });
    }

    // ─── Highlight active admission type on load ──────────────────────────
    var checkedType = document.querySelector('input[name="admission_type"]:checked');
    if (checkedType) {
        checkedType.closest('.form-check').querySelector('.form-check-label').classList.add('border-primary', 'bg-light');
    }

    // ─── Init ─────────────────────────────────────────────────────────────
    updateTypeLabel();
    refreshBilling();

})();
</script>
@endpush
