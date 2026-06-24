@props([
    'selectedPatient' => null,
    'canOverrideActiveAdmission' => false,
    'showActiveAdmissionWarning' => true,
    'patientFieldName' => 'patient_id',
    'patientSearchId' => 'patientSearch',
    'patientIdId' => 'patientId',
    'patientInfoId' => 'patientInfo',
    'deceasedWarningId' => 'deceasedWarning',
    'activeAdmissionWarningId' => 'activeAdmissionWarning',
    'activeAdmissionTextId' => 'activeAdmissionText',
    'admissionOverrideReasonId' => 'admissionOverrideReason',
    'patientInitialId' => 'patientInitial',
    'patientNameId' => 'patientName',
    'patientNumberId' => 'patientNumber',
    'patientPhoneId' => 'patientPhone',
    'patientLastVisitId' => 'patientLastVisit',
    'clearPatientHandler' => 'clearPatient()',
    'title' => null,
    'searchLabel' => null,
    'searchPlaceholder' => null,
    'required' => true,
    'showSearch' => true,
    'showClearButton' => true,
    'icon' => 'ti-search',
])

@php
    $title ??= __('visits.select_patient_heading');
    $searchLabel ??= __('visits.search_patient_label');
    $searchPlaceholder ??= __('visits.search_patient_placeholder');
    $selectedActiveAdmission = $showActiveAdmissionWarning ? $selectedPatient?->activeAdmission : null;
    $lastVisitDate = $selectedPatient?->visits()->latest('visit_date')->value('visit_date');
@endphp

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <h5 class="fw-bold mb-0">
            @if($icon)
                <i class="ti {{ $icon }} me-1"></i>
            @endif
            {{ $title }}
        </h5>
    </div>
    <div class="card-body">
        @if($showSearch)
            <div class="mb-3">
                <label class="form-label">{{ $searchLabel }} @if($required)<span class="text-danger">*</span>@endif</label>
                <select id="{{ $patientSearchId }}"
                        class="form-select form-select-lg @error($patientFieldName) is-invalid @enderror"
                        data-placeholder="{{ $searchPlaceholder }}"
                        style="width:100%">
                    <option value=""></option>
                    @if($selectedPatient)
                        <option value="{{ $selectedPatient->id }}" selected>{{ $selectedPatient->patient_number }} - {{ $selectedPatient->full_name }}</option>
                    @endif
                </select>
                <input type="hidden" name="{{ $patientFieldName }}" id="{{ $patientIdId }}" value="{{ $selectedPatient?->id ?? old($patientFieldName) }}">
                @error($patientFieldName)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endif

        <div id="{{ $patientInfoId }}" class="{{ $selectedPatient ? '' : 'd-none' }}">
            @if($selectedPatient?->is_deceased)
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-2">
                    <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                    <span><strong>{{ __('visits.patient_deceased_warning') }}</strong></span>
                </div>
            @endif
            <div id="{{ $deceasedWarningId }}" class="alert alert-danger d-flex align-items-center gap-2 mb-2 d-none">
                <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                <span><strong>{{ __('visits.patient_deceased_warning') }}</strong></span>
            </div>

            @if($showActiveAdmissionWarning)
                <div id="{{ $activeAdmissionWarningId }}" class="alert alert-warning mb-2 {{ $selectedActiveAdmission ? '' : 'd-none' }}">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-bed fs-18 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold">{{ __('visits.currently_admitted') }}</div>
                            <div class="small" id="{{ $activeAdmissionTextId }}">
                                @if($selectedActiveAdmission)
                                    Admission {{ $selectedActiveAdmission->admission_number }}{{ $selectedActiveAdmission->bed ? ' - '.$selectedActiveAdmission->bed->ward?->name.' / Bed '.$selectedActiveAdmission->bed->bed_number : '' }}.
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($canOverrideActiveAdmission)
                        <div class="mt-2">
                            <label class="form-label small mb-1" for="{{ $admissionOverrideReasonId }}">{{ __('visits.override_reason') }}</label>
                            <textarea class="form-control" id="{{ $admissionOverrideReasonId }}" name="admission_override_reason" rows="2" placeholder="{{ __('visits.override_reason') }}">{{ old('admission_override_reason') }}</textarea>
                        </div>
                    @else
                        <div class="small mt-2">{{ __('visits.complete_admission_first') }}</div>
                    @endif
                </div>
            @endif

            <div class="alert alert-light border d-flex align-items-center gap-3 mb-0">
                <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                    <span id="{{ $patientInitialId }}">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1)) : '' }}</span>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0" id="{{ $patientNameId }}">{{ $selectedPatient?->full_name }} &bull; {{ $selectedPatient?->gender }}</h6>
                    <small class="text-muted">
                        <span id="{{ $patientNumberId }}">{{ $selectedPatient?->patient_number }}</span>
                        &bull; <span id="{{ $patientPhoneId }}">{{ $selectedPatient?->phone }}</span>
                        <span id="{{ $patientLastVisitId }}" class="{{ $lastVisitDate ? '' : 'd-none' }}">
                            &bull; {{ __('visits.last_visit') }} <strong>{{ $lastVisitDate ? \Carbon\Carbon::parse($lastVisitDate)->format('d M Y') : '' }}</strong>
                        </span>
                    </small>
                </div>
                @if($showClearButton)
                    <button aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}" type="button" class="btn btn-sm btn-outline-danger" onclick="{{ $clearPatientHandler }}">
                        <i class="ti ti-x"></i>
                    </button>
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
