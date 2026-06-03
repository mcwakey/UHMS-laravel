@php
    $showAlerts = $showAlerts ?? true;
    $patient = $visit->patient;
    $department = $visit->relationLoaded('department') ? $visit->department : null;
    $insurance = $visit->relationLoaded('visitInsurance') ? $visit->visitInsurance : null;
@endphp

<div class="card mb-3 border-primary">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
                        <span class="text-primary fw-bold fs-5">{{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $patient->full_name }}</h5>
                        <div class="text-muted small">
                            {{ $patient->patient_number }} &middot;
                            {{ $patient->age }}y &middot;
                            {{ $patient->gender->value }} &middot;
                            Blood: {{ $patient->blood_group?->value ?? 'N/A' }}
                            @if($patient->phone) &middot; <i class="ti ti-phone me-1"></i>{{ $patient->phone }} @endif
                        </div>
                        <div class="text-muted small mt-1 d-flex flex-wrap gap-2">
                            @if($patient->occupation)
                                <span><i class="ti ti-briefcase me-1"></i>{{ $patient->occupation }}</span>
                            @endif
                            @if($patient->religion)
                                <span><i class="ti ti-book me-1"></i>{{ $patient->religion }}</span>
                            @endif
                            @if($patient->marital_status)
                                <span><i class="ti ti-heart me-1"></i>{{ is_object($patient->marital_status) ? $patient->marital_status->value : $patient->marital_status }}</span>
                            @endif
                            @if($department)
                                <span><i class="ti ti-building-hospital me-1"></i>{{ $department->name }}</span>
                            @endif
                            @if($insurance && $insurance->relationLoaded('insuranceProvider') && $insurance->insuranceProvider)
                                <span><i class="ti ti-shield-check me-1"></i>{{ $insurance->insuranceProvider->name }}</span>
                            @endif
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
                    <span class="badge bg-{{ $vtBg }}-subtle text-{{ $vtBg }} border border-{{ $vtBg }} px-2 py-1 ms-1" title="Visit Type">
                        <i class="ti ti-{{ $visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'bed' : ($visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'ambulance' : 'walk') }} me-1"></i>{{ $visit->visit_type->label() }}
                    </span>
                @endif
                @if(isset($visit->priority) && $visit->priority)
                    <x-status-badge :status="$visit->priority" class="px-2 py-2 ms-1" />
                @endif
                @if($visit->currentConsultationDoctor())
                    <div class="text-muted small mt-1">
                    <span class="text-muted ms-2 small">{{ $visit->visit_number }}</span>
                    <i class="ti ti-user-md me-1"></i> | Dr. {{ $visit->currentConsultationDoctor()->full_name }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($showAlerts && $patient->allergies)
<div class="alert alert-danger py-2 mb-3">
    <i class="ti ti-alert-triangle me-1"></i><strong>Allergies:</strong> {{ $patient->allergies }}
</div>
@endif

@if($showAlerts && $patient->chronic_conditions)
<div class="alert alert-warning py-2 mb-3">
    <i class="ti ti-heart-rate-monitor me-1"></i><strong>Chronic Conditions:</strong> {{ $patient->chronic_conditions }}
</div>
@endif
