@props([
    'visit',
    'showAlerts' => true,
    'activeAdmission' => null,
])

@php
    $patient = $visit->patient;
    $department = $visit->relationLoaded('department') ? $visit->department : null;
    $insurance = $visit->visitInsurance;
    $insurance?->loadMissing(['insuranceProvider', 'insuranceTier']);
    $insuranceProvider = $insurance?->insuranceProvider;
    $insuranceTier = $insurance?->insuranceTier;
    $hasRealInsurance = $insurance
        && $insurance->is_active
        && $insuranceProvider
        && ! $insuranceProvider->is_default;
    $insuranceUsageSummary = $hasRealInsurance
        ? app(\App\Services\InsuranceService::class)->getUsageSummary($insurance)
        : null;
    $admissionShortcut = $activeAdmission;
@endphp

<div {{ $attributes->merge(['class' => 'card mb-3 border-primary']) }}>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                        <span class="text-primary fw-bold fs-5">{{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold">{{ $patient->full_name }}</h5>

                            <a href="{{ $workspaceRoutes->route('admin.patients.show', $patient) }}"
                            class="btn btn-sm btn-outline-primary patient-card__profile-btn ms-4"
                            title="{{ __('patients.view_patient_profile') }}">
                                <i class="ti ti-external-link"></i>
                            </a>
                            @if($admissionShortcut)
                                <a href="{{ $workspaceRoutes->route('admin.admissions.show', $admissionShortcut) }}"
                                   class="btn btn-sm btn-outline-info patient-card__admission-btn"
                                   title="{{ __('consultations.workspace.open_admission_title', ['admission' => $admissionShortcut->admission_number]) }}">
                                    <i class="ti ti-bed me-1"></i>{{ __('consultations.workspace.open_admission') }}
                                </a>
                            @endif
                        </div>

                        <div class="text-muted small">
                            {{ $patient->patient_number }} &middot;
                            {{ __('patients.age_years', ['age' => $patient->age]) }} &middot;
                            {{ $patient->gender->translatedLabel() }} &middot;
                            {{ __('patients.blood_group') }}: {{ $patient->blood_group?->translatedLabel() ?? 'N/A' }}
                            @if($patient->phone) &middot; <i class="ti ti-phone me-1"></i><x-patient-protected-field field="phone" :value="$patient->phone" /> @endif
                        </div>
                        <div class="text-muted small mt-1 d-flex flex-wrap gap-2">
                            @if($patient->occupation)
                                <span class="me-3"><i class="ti ti-briefcase me-1"></i>{{ $patient->occupation }}</span>
                            @endif
                            @if($patient->religion)
                                <span class="me-3"><i class="ti ti-book me-1"></i>{{ $patient->religion }}</span>
                            @endif
                            @if($patient->marital_status)
                                <span class="me-3"><i class="ti ti-heart me-1"></i>{{ is_object($patient->marital_status) ? $patient->marital_status->translatedLabel() : $patient->marital_status }}</span>
                            @endif
                            @if($department)
                                <span class="me-3"><i class="ti ti-building-hospital me-1"></i>{{ $department->name }}</span>
                            @endif
                            <!-- @if($insurance && $insurance->relationLoaded('insuranceProvider') && $insurance->insuranceProvider)
                                <span><i class="ti ti-shield-check me-1"></i>{{ $insurance->insuranceProvider->name }}</span>
                            @endif -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                @if(isset($visit->status) && $visit->status)
                    <x-status-badge :status="$visit->status" class="px-3 py-2" />
                @endif
                @if(isset($visit->visit_type) && $visit->visit_type)
                    @php
                        $vtBg = match($visit->visit_type) {
                            \App\Enums\VisitType::INPATIENT  => 'info',
                            \App\Enums\VisitType::EMERGENCY  => 'danger',
                            default                           => 'primary',
                        };
                    @endphp
                    <span class="badge bg-{{ $vtBg }}-subtle text-{{ $vtBg }} border border-{{ $vtBg }} px-2 py-1 ms-1" title="{{ __('visits.visit_type') }}">
                        <i class="ti ti-{{ $visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'bed' : ($visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'ambulance' : 'walk') }} me-1"></i>{{ $visit->visit_type->translatedLabel() }}
                    </span>
                @endif
                @if(isset($visit->priority) && $visit->priority)
                    <x-status-badge :status="$visit->priority" class="px-2 py-2 ms-1" />
                @endif
                @if($visit->currentConsultationDoctor())
                    <div class="text-muted small mt-1">
                        <span class="text-muted ms-2 small">{{ $visit->visit_number }}</span>
                        <i class="ti ti-user-md me-1"></i> | Dr. {{ $visit->currentConsultationDoctor()->full_name }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @module('insurance')
    <div class="border-top px-3 py-2 bg-light bg-opacity-50">
        @if($hasRealInsurance)
            @php
                $annualLimit = (float) ($insuranceUsageSummary['annual_limit'] ?? 0);
                $monthlyLimit = (float) ($insuranceUsageSummary['max_per_month'] ?? 0);
                $perVisitLimit = (float) ($insuranceUsageSummary['per_visit_limit'] ?? 0);
                $usedThisYear = (float) ($insuranceUsageSummary['used_this_year'] ?? 0);
                $usedThisMonth = (float) ($insuranceUsageSummary['used_this_month'] ?? 0);
                $usedThisVisit = (float) $insurance->usedForVisit($visit->id);
                $limitClass = function (float $used, float $limit) {
                    if ($limit <= 0) return '';
                    return $used > $limit ? 'text-danger fw-bold' : ($used >= $limit ? 'text-danger' : 'text-dark');
                };
            @endphp
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="avatar avatar-sm bg-success-subtle text-success rounded d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;">
                        <i class="ti ti-shield-check"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate">
                            {{ $insuranceProvider->name }}
                            @if($insuranceTier?->name)
                                <span class="text-muted fw-normal">/ {{ $insuranceTier->name }}</span>
                            @endif
                            @if($insurance->membership_number)
                                <span class="badge bg-light text-dark border ms-1">#<x-patient-protected-field field="membership_number" :value="$insurance->membership_number" /></span>
                            @endif
                        </div>
                    </div>
                </div>
                @if($annualLimit > 0 || $monthlyLimit > 0 || $perVisitLimit > 0)
                    <div class="d-flex flex-wrap gap-3">
                        @if($perVisitLimit > 0)
                            <span class="{{ $limitClass($usedThisVisit, $perVisitLimit) }}">
                                {{ __('patients.used_this_visit') }}:
                                <strong>&#8373;{{ number_format($usedThisVisit, 2) }}</strong> / &#8373;{{ number_format($perVisitLimit, 2) }}
                            </span>
                        @endif
                        @if($monthlyLimit > 0)
                            <span class="{{ $limitClass($usedThisMonth, $monthlyLimit) }}">
                                {{ __('patients.used_this_month') }}:
                                <strong>&#8373;{{ number_format($usedThisMonth, 2) }}</strong> / &#8373;{{ number_format($monthlyLimit, 2) }}
                            </span>
                        @endif
                        @if($annualLimit > 0)
                            <span class="{{ $limitClass($usedThisYear, $annualLimit) }}">
                                {{ __('patients.used_this_year') }}:
                                <strong>&#8373;{{ number_format($usedThisYear, 2) }}</strong> / &#8373;{{ number_format($annualLimit, 2) }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="d-flex align-items-center gap-2 small text-muted">
                <span class="avatar avatar-sm bg-secondary-subtle text-secondary rounded d-inline-flex align-items-center justify-content-center" style="width:30px;height:30px;">
                    <i class="ti ti-cash"></i>
                </span>
                <span class="fw-semibold text-dark">Cash &amp; Carry</span>
                <span>{{ __('patients.no_active_insurance_for_visit') }}</span>
            </div>
        @endif
    </div>
    @endmodule
</div>

@if($showAlerts && ($patient->allergies || $patient->chronic_conditions))
<div class="row g-2 align-items-stretch mb-3">
    @if($patient->allergies)
    <div class="col-md-6">
        <div class="alert alert-danger h-100 mb-0 text-wrap">
            <i class="ti ti-alert-triangle me-1"></i><strong>{{ __('patients.allergies') }}:</strong> <x-patient-protected-field field="allergies" :value="$patient->allergies" />
        </div>
    </div>
    @endif

    @if($patient->chronic_conditions)
    <div class="col-md-6">
        <div class="alert alert-warning h-100 mb-0 text-wrap">
            <i class="ti ti-heart-rate-monitor me-1"></i><strong>{{ __('patients.chronic_conditions') }}:</strong> <x-patient-protected-field field="chronic_conditions" :value="$patient->chronic_conditions" />
        </div>
    </div>
    @endif
</div>
@endif
