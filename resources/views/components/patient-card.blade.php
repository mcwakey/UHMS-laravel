@props([
    'patient' => null,
    'visit' => null,
    'visitInsurance' => null,
    'compact' => false,
])

{{--
    Reusable Patient Summary Card
    -----------------------------
    Usage:
        <x-patient-card :patient="$patient" />
        <x-patient-card :patient="$patient" :visit="$visit" />
        <x-patient-card :patient="$patient" :visit-insurance="$appointment->visitInsurance" />
        <x-patient-card :patient="$patient" :visit="$visit" compact />

    Props:
        $patient (required) - App\Models\Patient
        $visit   (optional) - App\Models\Visit (to show insurance for that visit)
        $visitInsurance (optional) - App\Models\PatientInsurance (for appointments before visit check-in)
        $compact (optional, default false) - render a slimmer variant
--}}
@php
    $vIns      = $visitInsurance ?? $visit?->visitInsurance;
    $vProvider = $vIns?->insuranceProvider;
    $vTier     = $vIns?->insuranceTier;
    $hasRealIns = $vIns && $vIns->is_active && $vProvider && ! $vProvider->is_default;
    $insExpired = $vIns?->is_expired ?? false;
    $showInsuranceSection = $visit !== null || $visitInsurance !== null;

    $initials = $patient ? strtoupper(
        substr($patient->first_name ?? '?', 0, 1) .
        substr($patient->last_name  ?? '',  0, 1)
    ) : '';

    $gender = $patient?->gender?->translatedLabel() ?? '—';
    $age    = $patient?->age ?? '—';
    $insuranceUsageSummary = null;
    if ($hasRealIns) {
        $vIns->loadMissing('insuranceTier');
        $insuranceUsageSummary = app(\App\Services\InsuranceService::class)->getUsageSummary($vIns);
    }
@endphp

@if($patient !== null)
<div class="card patient-card shadow-sm border-0 mb-3">
    <div class="patient-card__header">
        <div class="patient-card__avatar">
            @if(! empty($patient->avatar))
                <img src="{{ asset('storage/' . $patient->avatar) }}" alt="{{ $patient->full_name }}">
            @else
                <span>{{ $initials }}</span>
            @endif
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.patients.show', $patient) }}"
                   class="patient-card__name text-decoration-none">
                    {{ $patient->full_name }}
                </a>
                <span class="badge badge-soft-secondary fs-11 fw-medium">{{ $patient->patient_number }}</span>
            </div>
            <div class="patient-card__meta">
                <span><i class="ti ti-user fs-12"></i> {{ $age }} yrs · {{ $gender }}</span>
                @if($patient->blood_group)
                    <span><i class="ti ti-droplet fs-12 text-danger"></i> {{ $patient->blood_group?->value }}</span>
                @endif
                @if($patient->phone)
                    <span><i class="ti ti-phone fs-12"></i> <x-patient-protected-field field="phone" :value="$patient->phone" /></span>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.patients.show', $patient) }}"
           class="btn btn-sm btn-outline-primary patient-card__profile-btn"
           title="{{ __('patients.view_patient_profile') }}">
            <i class="ti ti-external-link"></i>
        </a>
    </div>

    @if(! $compact && ($patient->allergies || $patient->chronic_conditions))
    <div class="patient-card__alerts">
        @if($patient->allergies)
        <div class="patient-card__alert patient-card__alert--danger">
            <i class="ti ti-alert-triangle"></i>
            <div>
                <small class="fw-semibold d-block">Allergies</small>
                <span class="fs-13"><x-patient-protected-field field="allergies" :value="$patient->allergies" /></span>
            </div>
        </div>
        @endif
        @if($patient->chronic_conditions)
        <div class="patient-card__alert patient-card__alert--warning">
            <i class="ti ti-heart-rate-monitor"></i>
            <div>
                <small class="fw-semibold d-block">Chronic Conditions</small>
                <span class="fs-13"><x-patient-protected-field field="chronic_conditions" :value="$patient->chronic_conditions" /></span>
            </div>
        </div>
        @endif
    </div>
    @endif

                        @module('insurance')
    @if($showInsuranceSection)
    <div class="patient-card__insurance">
        @if($hasRealIns)
            <div class="patient-card__insurance-row">
                <div class="patient-card__insurance-icon bg-success bg-opacity-10 text-success">
                    <i class="ti ti-shield-check"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <span class="fw-semibold text-truncate">{{ $vProvider->name }}</span>
                        @if($insExpired)
                            <span class="badge bg-danger fs-11">Expired</span>
                        @elseif($vIns->is_valid)
                            <span class="badge bg-success fs-11">Active</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <small class="text-muted d-block">
                        @if($vTier?->name){{ $vTier->name }}@endif
                        {{-- @if($vTier?->coverage_percentage) · {{ $vTier->coverage_percentage }}% coverage @endif --}}
                        @if($vIns->membership_number) · #<x-patient-protected-field field="membership_number" :value="$vIns->membership_number" /> @endif
                    </small>
                        @can('visits.edit')
                        @if($visit)
                        <button type="button" class="btn btn-outline-primary btn-xs" data-bs-toggle="modal" data-bs-target="#changeVisitInsuranceModal">
                            <i class="ti ti-switch-horizontal me-1"></i>
                        </button>
                        @endif
                        @endcan
                        {{-- <span class="fw-semibold text-truncate">{{ $vProvider->name }}</span> --}}
                    </div>
                    @if($insuranceUsageSummary)
                    @php
                        $annualLimit = (float) ($insuranceUsageSummary['annual_limit'] ?? 0);
                        $monthlyLimit = (float) ($insuranceUsageSummary['max_per_month'] ?? 0);
                        $perVisitLimit = (float) ($insuranceUsageSummary['per_visit_limit'] ?? 0);
                        $usedThisYear = (float) ($insuranceUsageSummary['used_this_year'] ?? 0);
                        $usedThisMonth = (float) ($insuranceUsageSummary['used_this_month'] ?? 0);
                        $usedThisVisit = $visit ? (float) $vIns->usedForVisit($visit->id) : null;
                        $perVisitLimitReached = $perVisitLimit > 0 && $usedThisVisit !== null && $usedThisVisit >= $perVisitLimit;
                        $monthlyLimitReached = $monthlyLimit > 0 && $usedThisMonth >= $monthlyLimit;
                        $annualLimitReached = $annualLimit > 0 && $usedThisYear >= $annualLimit;
                        $perVisitLimitExceeded = $perVisitLimit > 0 && $usedThisVisit !== null && $usedThisVisit > $perVisitLimit;
                        $monthlyLimitExceeded = $monthlyLimit > 0 && $usedThisMonth > $monthlyLimit;
                        $annualLimitExceeded = $annualLimit > 0 && $usedThisYear > $annualLimit;
                    @endphp
                    @if($annualLimit > 0 || $monthlyLimit > 0 || $perVisitLimit > 0)
                    <div class="patient-card__insurance-usage">
                        @if($perVisitLimit > 0)
                        <div @class([
                            'patient-card__insurance-usage-item',
                            'patient-card__insurance-usage-item--limit' => $perVisitLimitReached,
                            'patient-card__insurance-usage-item--exceeded' => $perVisitLimitExceeded,
                        ])>
                            <span>{{ $visit ? __('patients.used_this_visit') : __('patients.per_visit_limit') }}</span>
                            <strong>
                                @if($visit)
                                    &#8373;{{ number_format($usedThisVisit ?? 0, 2) }} / &#8373;{{ number_format($perVisitLimit, 2) }}
                                @else
                                    &#8373;{{ number_format($perVisitLimit, 2) }}
                                @endif
                                <!-- @if($perVisitLimitReached)
                                    <small>{{ $perVisitLimitExceeded ? __('patients.limit_exceeded') : __('patients.limit_reached') }}</small>
                                @endif -->
                            </strong>
                        </div>
                        @endif
                        @if($monthlyLimit > 0)
                        <div @class([
                            'patient-card__insurance-usage-item',
                            'patient-card__insurance-usage-item--limit' => $monthlyLimitReached,
                            'patient-card__insurance-usage-item--exceeded' => $monthlyLimitExceeded,
                        ])>
                            <span>{{ __('patients.used_this_month') }}</span>
                            <strong>
                                &#8373;{{ number_format($usedThisMonth, 2) }} / &#8373;{{ number_format($monthlyLimit, 2) }}
                                    <!-- @if($monthlyLimitReached)
                                        <small>{{ $monthlyLimitExceeded ? __('patients.limit_exceeded') : __('patients.limit_reached') }}</small>
                                    @endif -->
                            </strong>
                        </div>
                        @endif
                        @if($annualLimit > 0)
                        <div @class([
                            'patient-card__insurance-usage-item',
                            'patient-card__insurance-usage-item--limit' => $annualLimitReached,
                            'patient-card__insurance-usage-item--exceeded' => $annualLimitExceeded,
                        ])>
                            <span>{{ __('patients.used_this_year') }}</span>
                            <strong>
                                &#8373;{{ number_format($usedThisYear, 2) }} / &#8373;{{ number_format($annualLimit, 2) }}
                                <!-- @if($annualLimitReached)
                                    <small>{{ $annualLimitExceeded ? __('patients.limit_exceeded') : __('patients.limit_reached') }}</small>
                                @endif -->
                            </strong>
                        </div>
                        @endif
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        @else
            <div class="patient-card__insurance-row">
                <div class="patient-card__insurance-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="ti ti-cash"></i>
                </div>
                <div>
                    <span class="fw-semibold">Cash &amp; Carry</span>
                    <small class="text-muted d-block">{{ __('patients.no_active_insurance_for_visit') }}</small>
                </div>
            </div>
        @endif
    </div>
    @endif
                        @endmodule
</div>
@endif

@once
@push('styles')
<style>
    .patient-card {
        border-radius: 14px;
        overflow: hidden;
        background: var(--white, #fff);
    }
    .patient-card__header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: linear-gradient(135deg, rgba(46,55,164,.04) 0%, rgba(46,55,164,.01) 100%);
        border-bottom: 1px solid var(--border-color, #E7E8EB);
    }
    .patient-card__avatar {
        width: 52px; height: 52px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2E37A4 0%, #4C56C5 100%);
        color: #fff;
        font-weight: 700;
        font-size: 16px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(46,55,164,.18);
        overflow: hidden;
    }
    .patient-card__avatar img { width: 100%; height: 100%; object-fit: cover; }
    .patient-card__name {
        font-weight: 600;
        font-size: 15px;
        color: var(--bs-body-color, #212529);
        line-height: 1.2;
    }
    .patient-card__name:hover { color: var(--primary, #2E37A4); }
    .patient-card__meta {
        margin-top: 4px;
        display: flex; flex-wrap: wrap; gap: 10px;
        font-size: 12px;
        color: var(--body-color, #6C7688);
    }
    .patient-card__meta span { display: inline-flex; align-items: center; gap: 4px; }
    .patient-card__profile-btn {
        flex-shrink: 0;
        width: 34px; height: 34px;
        padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px;
    }
    .patient-card__alerts {
        padding: 12px 16px;
        display: flex; flex-direction: column; gap: 8px;
        border-bottom: 1px solid var(--border-color, #E7E8EB);
    }
    .patient-card__alert {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 13px;
    }
    .patient-card__alert i { font-size: 18px; flex-shrink: 0; margin-top: 2px; }
    .patient-card__alert--danger  { background: rgba(220,53,69,.07); color: #b02a37; }
    .patient-card__alert--warning { background: rgba(255,193,7,.10); color: #997404; }
    .patient-card__insurance {
        padding: 12px 16px;
        background: rgba(0,0,0,.015);
    }
    .patient-card__insurance-row {
        display: flex; align-items: center; gap: 12px;
    }
    .patient-card__insurance-icon {
        width: 36px; height: 36px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .patient-card__insurance-usage {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 10px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed rgba(25, 135, 84, .25);
        font-size: 11px;
    }
    .patient-card__insurance-usage .patient-card__insurance-usage-item {
        display: flex;
        justify-content: space-between;
        gap: 6px;
        min-width: 0;
    }
    .patient-card__insurance-usage-item--limit {
        border-radius: 6px;
        background: rgba(220, 53, 69, .08);
        padding: 2px 4px;
    }
    .patient-card__insurance-usage-item--exceeded {
        background: rgba(220, 53, 69, .14);
        box-shadow: inset 0 0 0 1px rgba(220, 53, 69, .2);
    }
    .patient-card__insurance-usage-item--limit span,
    .patient-card__insurance-usage-item--limit strong {
        color: #b02a37;
    }
    .patient-card__insurance-usage span {
        color: var(--body-color, #6C7688);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .patient-card__insurance-usage strong {
        color: var(--bs-body-color, #212529);
        font-weight: 600;
        white-space: nowrap;
    }
    .patient-card__insurance-usage strong small {
        display: block;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.1;
        text-align: right;
        text-transform: uppercase;
    }
    .min-w-0 { min-width: 0; }
</style>
@endpush
@endonce
