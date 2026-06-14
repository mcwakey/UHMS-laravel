{{--
    Reusable Patient Summary Card
    -----------------------------
    Usage:
        @include('partials.patient-card', ['patient' => $patient])
        @include('partials.patient-card', ['patient' => $patient, 'visit' => $visit])
        @include('partials.patient-card', ['patient' => $patient, 'visit' => $visit, 'compact' => true])

    Props:
        $patient (required) — App\Models\Patient
        $visit   (optional) — App\Models\Visit (to show insurance for that visit)
        $compact (optional, default false) — render a slimmer variant
--}}
@php
    $compact   = $compact ?? false;
    $visit     = $visit   ?? null;
    $vIns      = $visit?->visitInsurance;
    $vProvider = $vIns?->insuranceProvider;
    $vTier     = $vIns?->insuranceTier;
    $hasRealIns = $vIns && $vIns->is_active && $vProvider && ! $vProvider->is_default;
    $insExpired = $vIns?->is_expired ?? false;

    $initials = $patient ? strtoupper(
        substr($patient->first_name ?? '?', 0, 1) .
        substr($patient->last_name  ?? '',  0, 1)
    ) : '';

    $gender = $patient?->gender?->translatedLabel() ?? '—';
    $age    = $patient?->age ?? '—';
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
                    <span><i class="ti ti-phone fs-12"></i> {{ $patient->phone }}</span>
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
                <span class="fs-13">{{ $patient->allergies }}</span>
            </div>
        </div>
        @endif
        @if($patient->chronic_conditions)
        <div class="patient-card__alert patient-card__alert--warning">
            <i class="ti ti-heart-rate-monitor"></i>
            <div>
                <small class="fw-semibold d-block">Chronic Conditions</small>
                <span class="fs-13">{{ $patient->chronic_conditions }}</span>
            </div>
        </div>
        @endif
    </div>
    @endif

                        @module('insurance')
    @if($visit)
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
                        @if($vIns->membership_number) · #{{ $vIns->membership_number }} @endif
                    </small>
                        @can('visits.edit')
                        <button type="button" class="btn btn-outline-primary btn-xs" data-bs-toggle="modal" data-bs-target="#changeVisitInsuranceModal">
                            <i class="ti ti-switch-horizontal me-1"></i>
                        </button>
                        @endcan
                        {{-- <span class="fw-semibold text-truncate">{{ $vProvider->name }}</span> --}}
                    </div>
                </div>
            </div>
        @else
            <div class="patient-card__insurance-row">
                <div class="patient-card__insurance-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="ti ti-cash"></i>
                </div>
                <div>
                    <span class="fw-semibold">Cash &amp; Carry</span>
                    <small class="text-muted d-block">No active insurance for this visit</small>
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
    .min-w-0 { min-width: 0; }
</style>
@endpush
@endonce
