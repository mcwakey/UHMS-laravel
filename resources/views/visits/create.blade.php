@extends('layouts.app')
@section('title', __('visits.create_new_visit'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.visits.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>{{ __('visits.create_new_visit') }}</a>
    </h6>
    {{-- <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"></h4>
    </div> --}}
    {{-- <div>
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
    </div> --}}
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div id="visitFormFeedback" class="alert d-none" role="alert"></div>

<form method="POST" action="{{ route('admin.visits.store') }}" id="visitForm">
    @csrf

    <div class="row">
        <!-- Left Column - Patient, Insurance, Visit Details -->
        <div class="col-lg-8">
            <!-- Patient Search -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-search me-1"></i>{{ __('visits.select_patient_heading') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('visits.search_patient_label') }} <span class="text-danger">*</span></label>
                        <select id="patientSearch"
                                class="form-select form-select-lg @error('patient_id') is-invalid @enderror"
                                data-placeholder="{{ __('visits.search_patient_placeholder') }}"
                                style="width:100%">
                            <option value=""></option>
                            @if($selectedPatient)
                                <option value="{{ $selectedPatient->id }}" selected>{{ $selectedPatient->patient_number }} - {{ $selectedPatient->full_name }}</option>
                            @endif
                        </select>
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <!-- Selected Patient Info Card -->
                    <div id="patientInfo" class="{{ $selectedPatient ? '' : 'd-none' }}">
                        @if($selectedPatient?->is_deceased)
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                            <span><strong>{{ __('visits.patient_deceased_warning') }}</strong></span>
                        </div>
                        @endif
                        <div id="deceasedWarning" class="alert alert-danger d-flex align-items-center gap-2 mb-2 d-none">
                            <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                            <span><strong>{{ __('visits.patient_deceased_warning') }}</strong></span>
                        </div>
                        @php($selectedActiveAdmission = $selectedPatient?->activeAdmission)
                        <div id="activeAdmissionWarning" class="alert alert-warning mb-2 {{ $selectedActiveAdmission ? '' : 'd-none' }}">
                            <div class="d-flex align-items-start gap-2">
                                <i class="ti ti-bed fs-18 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-semibold">{{ __('visits.currently_admitted') }}</div>
                                    <div class="small" id="activeAdmissionText">
                                        @if($selectedActiveAdmission)
                                            Admission {{ $selectedActiveAdmission->admission_number }}{{ $selectedActiveAdmission->bed ? ' - '.$selectedActiveAdmission->bed->ward?->name.' / Bed '.$selectedActiveAdmission->bed->bed_number : '' }}.
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($canOverrideActiveAdmission)
                                <div class="mt-2">
                                    <label class="form-label small mb-1" for="admissionOverrideReason">{{ __('visits.override_reason') }}</label>
                                    <textarea class="form-control" id="admissionOverrideReason" name="admission_override_reason" rows="2" placeholder="{{ __('visits.override_reason') }}">{{ old('admission_override_reason') }}</textarea>
                                </div>
                            @else
                                <div class="small mt-2">{{ __('visits.complete_admission_first') }}</div>
                            @endif
                        </div>
                        <div class="alert alert-light border d-flex align-items-center gap-3 mb-0">
                            <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                                <span id="patientInitial">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1)) : '' }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0" id="patientName">{{ $selectedPatient?->full_name }} &bull; {{ $selectedPatient?->gender }}</h6>
                                <small class="text-muted">
                                    <span id="patientNumber">{{ $selectedPatient?->patient_number }}</span>
                                    &bull; <span id="patientPhone">{{ $selectedPatient?->phone }}</span>
                                    <span id="patientLastVisit" class="{{ $selectedPatient && $selectedPatient->visits()->exists() ? '' : 'd-none' }}">
                                        &bull; {{ __('visits.last_visit') }} <strong>{{ $selectedPatient ? ($selectedPatient->visits()->latest('visit_date')->value('visit_date') ? \Carbon\Carbon::parse($selectedPatient->visits()->latest('visit_date')->value('visit_date'))->format('d M Y') : '') : '' }}</strong>
                                    </span>
                                </small>
                            </div>
                            <button aria-label="Close" title="Close" type="button" class="btn btn-sm btn-outline-danger" onclick="clearPatient()">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance Selection -->
            <div class="card d-none" id="insuranceCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('visits.insurance_heading') }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark" id="insuranceFallbackBadge" style="display:none;">{{ __('visits.insurance_fallback_badge') }}</span>
                        @can('patients.edit')
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addInsuranceBtn">
                                <i class="ti ti-plus me-1"></i>{{ __('visits.add_insurance_btn') }}
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <!-- Insurance List (radio selection) -->
                    <div id="insuranceList" class="mb-3">
                        <div class="text-muted text-center py-3">
                            <i class="ti ti-loader me-1"></i>{{ __('visits.loading_insurances') }}
                        </div>
                    </div>

                    <input type="hidden" name="visit_insurance_id" id="visitInsuranceId" value="">
                    <input type="hidden" name="insurance_verification_id" id="insuranceVerificationId" value="">

                    {{-- Provider-agnostic verification panel.
                         Visibility / inputs are driven entirely by the response from
                         /admin/insurance/verify; no provider names appear here. --}}
                    <div id="verificationPanel" class="border rounded p-3 mt-3 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0"><i class="ti ti-shield-lock me-1"></i>{{ __('visits.verification') }}</h6>
                            <span class="badge bg-secondary" id="verificationStatusBadge">{{ __('visits.not_started') }}</span>
                        </div>
                        <div class="text-muted small mb-2" id="verificationProviderMeta">&mdash;</div>

                        <div class="row g-2 align-items-end" id="verificationCodeRow" style="display:none;">
                            <div class="col-sm-8">
                                <label class="form-label mb-1">{{ __('visits.ccc_code_label') }}</label>
                                <input type="text" id="verificationReferenceInput" name="verification_reference_code"
                                       class="form-control" placeholder="{{ __('visits.ccc_code_label') }}"
                                       autocomplete="off">
                            </div>
                            <div class="col-sm-4 d-grid">
                                <button type="button" class="btn btn-primary" id="runVerificationBtn">
                                    <i class="ti ti-shield-check me-1"></i>{{ __('common.confirm') }}
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 align-items-end mt-1" id="verificationManualRow" style="display:none;">
                            <div class="col-12 d-grid">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="runVerificationBtn2">
                                    <i class="ti ti-shield-check me-1"></i>{{ __('common.confirm') }}
                                </button>
                            </div>
                        </div>

                        <div id="verificationFeedback" class="small mt-2"></div>
                    </div>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('visits.visit_details_heading') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.visit_type_label') }} <span class="text-danger">*</span></label>
                            <select name="visit_type" class="form-select @error('visit_type') is-invalid @enderror" required>
                                <option value="">{{ __('visits.select_type_opt') }}</option>
                                @foreach(\App\Enums\VisitType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('visit_type') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.priority_label') }} <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach(\App\Enums\Priority::cases() as $priority)
                                    <option value="{{ $priority->value }}" {{ old('priority', 'normal') == $priority->value ? 'selected' : '' }}>{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.visit_date_label') }}</label>
                            <input type="date" name="visit_date" class="form-control @error('visit_date') is-invalid @enderror" value="{{ old('visit_date', date('Y-m-d')) }}" id="visitDate">
                            @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted" id="schedulingHint">{{ __('visits.today_scheduling_hint') }}</small>
                        </div>
                    </div>

                    {{-- Scheduling Fields (shown when future date selected) --}}
                    <div class="row" id="schedulingFields" style="display: none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.start_time_label') }}</label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}">
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.end_time_label') }}</label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('visits.consultation_mode_label') }}</label>
                            <select name="consultation_mode" class="form-select @error('consultation_mode') is-invalid @enderror">
                                @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" {{ old('consultation_mode', 'in_person') == $mode->value ? 'selected' : '' }}>{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                            @error('consultation_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('visits.chief_complaint_field') }}</label>
                            <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3" placeholder="{{ __('visits.complaint_placeholder') }}">{{ old('chief_complaint') }}</textarea>
                            @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('visits.notes_field') }}</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="{{ __('visits.notes_placeholder') }}">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Department, Services & Submit -->
        <div class="col-lg-4">
            <!-- Department, Services & Doctor Selection -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>{{ __('visits.dept_services_heading') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('visits.department_filter_label') }} <small class="text-muted">{{ __('visits.dept_filters_services') }}</small></label>
                            <select id="departmentSelect" class="form-select @error('department_id') is-invalid @enderror" data-placeholder="{{ __('visits.search_dept_placeholder') }}" style="width:100%">
                                <option value="">{{ __('visits.select_department') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('visits.assign_doctor_label') }} <small class="text-muted">{{ __('visits.optional_label') }}</small></label>
                            <select id="doctorSelect" class="form-select" data-placeholder="{{ __('visits.search_doctor_placeholder') }}" style="width:100%" disabled>
                                <option value="">{{ __('visits.select_dept_first') }}</option>
                            </select>
                            {{-- <div id="doctorSelectHelp" class="form-text">Doctors load from specialties linked to the selected department.</div> --}}
                        </div>
                    </div>

                    <!-- Service Selection -->
                    <div class="mb-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                            <label class="form-label mb-0">{{ __('visits.available_services_label') }}</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="showExtraServices">
                                <label class="form-check-label small text-muted" for="showExtraServices">{{ __('visits.show_other_services') }}</label>
                            </div>
                        </div>
                        <div id="servicesList" class="border rounded p-3 bg-light">
                            <div class="text-muted text-center py-3" id="servicesPlaceholder">
                                <i class="ti ti-list-search me-1"></i>{{ __('visits.select_dept_load_services') }}
                            </div>
                            <div id="servicesContent" class="d-none">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" id="serviceFilter" class="form-control" placeholder="{{ __('visits.filter_services') }}">
                                </div>
                                <div id="servicesItems" style="max-height: 280px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Services (Department Sessions + Billing Lines) -->
                    <div id="selectedServicesCard" class="d-none">
                        <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>{{ __('visits.selected_services_label') }}</label>
                        <div id="routeDoctorSummary" class="small text-muted mb-2"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="billingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('visits.service_name') }}</th>
                                        <th class="text-end" style="width: 120px;">{{ __('visits.price_col') }}</th>
                                        <th class="text-center" style="width: 50px;">{{ __('visits.action_col') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="billingBody"></tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td class="text-end text-primary">{{ __('visits.overall_total_label') }}</td>
                                        <td class="text-end text-primary" id="totalAmount">&#8373;0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card bg-light" id="walkInInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="ti ti-info-circle me-1"></i>{{ __('visits.what_happens_next') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i>{{ __('visits.visit_registered') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i>{{ __('visits.patient_moves_waiting') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-warning me-1"></i>{{ __('visits.staff_pushes_triage') }}</li>
                        <li><i class="ti ti-check text-primary me-1"></i>{{ __('visits.billing_lines_created') }}</li>
                    </ul>
                </div>
            </div>
            <div class="card bg-light d-none" id="scheduledInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="ti ti-calendar-event me-1 text-primary"></i>{{ __('visits.scheduling_future') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('visits.visit_scheduled') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('visits.patient_notified') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('visits.checkin_visit_day') }}</li>
                        <li><i class="ti ti-check text-primary me-1"></i>{{ __('visits.auto_transitions') }}</li>
                    </ul>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="ti ti-plus me-1"></i><span id="submitBtnText">{{ __('visits.create_visit_btn') }}</span>
                </button>
            </div>
        </div>
    </div>
</form>

{{-- ──────────────────────────────────────────────────────────────────────
     Add / Edit / Renew Patient Insurance Modal — SPA: no full reload
──────────────────────────────────────────────────────────────────────── --}}
@can('patients.edit')
@include('patients.partials.insurance-add-modal', [
    'patient' => null,
    'insuranceProviders' => $insuranceProviders,
    'formAction' => '#',
])
@include('patients.partials.insurance-edit-modal', [
    'patient' => null,
    'formAction' => '#',
])

<div class="modal fade" id="insuranceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="insuranceForm" autocomplete="off">
                @csrf
                <input type="hidden" id="insuranceFormPatientId" name="_patient_id">
                <input type="hidden" id="insuranceFormInsuranceId" name="_insurance_id">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-shield-plus me-1"></i><span id="insuranceModalTitle">{{ __('visits.add_insurance_title') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="insuranceFormFeedback" class="alert d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.insurance_provider_label') }} <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="insuranceProviderSelect" class="form-select" required>
                                <option value="">{{ __('visits.select_provider_opt') }}</option>
                                @foreach($insuranceProviders as $prov)
                                    <option value="{{ $prov->id }}"
                                            data-type="{{ $prov->type?->value }}"
                                            data-tiers='@json($prov->tiers->map(fn($t) => ["id"=>$t->id, "name"=>$t->name]))'>
                                        {{ $prov->name }} ({{ $prov->type?->label() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.tier_label') }}</label>
                            <select name="insurance_tier_id" id="insuranceTierSelect" class="form-select">
                                <option value="">{{ __('visits.tier_default') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.membership_number_label') }}</label>
                            <input type="text" name="membership_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.policy_number_label') }}</label>
                            <input type="text" name="policy_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.ccc_code_label') }} <small class="text-muted">{{ __('visits.optional_label') }}</small></label>
                            <input type="text" name="ccc_code" class="form-control" maxlength="64">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.member_type_label') }}</label>
                            <select name="member_type" class="form-select">
                                <option value="holder" selected>{{ __('visits.card_holder_opt') }}</option>
                                <option value="beneficiary">{{ __('visits.beneficiary_opt') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('visits.expiry_date_label') }}</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="insIsPrimary">
                                <label class="form-check-label" for="insIsPrimary">{{ __('visits.set_primary_label') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('visits.cancel_btn') }}</button>
                    <button type="submit" class="btn btn-primary" id="insuranceFormSaveBtn">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('visits.save_insurance_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@php
$visitI18nData = [
    'search_patient_placeholder' => __('visits.search_patient_placeholder'),
    'search_dept_placeholder'    => __('visits.search_dept_placeholder'),
    'search_doctor_placeholder'  => __('visits.search_doctor_placeholder'),
    'select_dept_load_services'  => __('visits.select_dept_load_services'),
    'loading_services_doctors'   => __('visits.loading_services_doctors'),
    'no_services_dept'           => __('visits.no_services_dept'),
    'no_consult_services'        => __('visits.no_consult_services'),
    'failed_load_dept'           => __('visits.failed_load_dept'),
    'loading_insurances'         => __('visits.loading_insurances'),
    'failed_load_insurances'     => __('visits.failed_load_insurances'),
    'no_insurances_cash'         => __('visits.no_insurances_cash'),
    'no_phone'                   => __('visits.no_phone'),
    'last_visit_label'           => __('visits.last_visit_label'),
    'member_label'               => __('visits.member_label'),
    'expires_label'              => __('visits.expires_label'),
    'expires_today'              => __('visits.expires_today'),
    'no_expiry'                  => __('visits.no_expiry'),
    'valid_status'               => __('visits.valid_status'),
    'expired_status'             => __('visits.expired_status'),
    'inactive_status'            => __('visits.inactive_status'),
    'renew_btn'                  => __('visits.renew_btn'),
    'edit_btn'                   => __('visits.edit_btn'),
    'beneficiary_label'          => __('visits.beneficiary_opt'),
    'card_holder_label'          => __('visits.card_holder_opt'),
    'admission_prefix'           => __('visits.admission_prefix'),
    'bed_prefix'                 => __('visits.bed_prefix'),
    'filter_services'            => __('visits.filter_services'),
    'dept_session_label'         => __('visits.dept_session_label'),
    'dept_total_label'           => __('visits.dept_total_label'),
    'doctor_prefix'              => __('visits.doctor_prefix'),
    'doctor_unassigned'          => __('visits.doctor_unassigned'),
    'assign_doctor_opt'          => __('visits.assign_doctor_opt'),
    'select_dept_first'          => __('visits.select_dept_first'),
    'consult_sessions_note'      => __('visits.consult_sessions_note'),
    'unassigned_doctor'          => __('visits.unassigned_doctor'),
    'created_successfully'       => __('visits.created_successfully'),
    'open_visit'                 => __('visits.open_visit'),
    'create_another'             => __('visits.create_another'),
    'current_status'             => __('visits.current_status'),
    'correct_fields'             => __('visits.correct_fields'),
    'select_patient_first'       => __('visits.select_patient_first'),
    'saving'                     => __('visits.saving'),
    'verifying'                  => __('visits.verifying'),
    'schedule_visit_btn'         => __('visits.schedule_visit_btn'),
    'create_visit_btn'           => __('visits.create_visit_btn'),
    'tier_default'               => __('visits.tier_default'),
    'add_insurance_title'        => __('visits.add_insurance_title'),
    'failed_create_visit'        => __('visits.failed_create_visit'),
    'network_error_visit'        => __('visits.network_error_visit'),
    'verification_failed'        => __('visits.verification_failed'),
    'select_patient_ins_update'  => __('visits.select_patient_ins_update'),
    'insurance_saved'            => __('visits.insurance_saved'),
    'insurance_updated'          => __('visits.insurance_updated'),
    'correct_fields_short'       => __('visits.correct_fields_short'),
    'failed_save_insurance'      => __('visits.failed_save_insurance'),
    'failed_update_insurance'    => __('visits.failed_update_insurance'),
    'network_error_insurance'    => __('visits.network_error_insurance'),
    'network_error_ins_update'   => __('visits.network_error_ins_update'),
    'select_type_first'          => __('visits.select_type_first'),
    'select_provider_first'      => __('visits.select_provider_first'),
];
@endphp
<script>const visitI18n = @json($visitI18nData);</script>
@push('scripts')
@include('patients.partials.insurance-add-modal-scripts')
@include('patients.partials.insurance-edit-modal-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearch');
    const patientIdInput = document.getElementById('patientId');
    const patientInfo = document.getElementById('patientInfo');
    const visitDateInput = document.getElementById('visitDate');
    const schedulingFields = document.getElementById('schedulingFields');
    const walkInInfo = document.getElementById('walkInInfo');
    const scheduledInfo = document.getElementById('scheduledInfo');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtn = document.getElementById('submitBtn');
    const visitForm = document.getElementById('visitForm');
    const formFeedback = document.getElementById('visitFormFeedback');
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const doctorSelectHelp = document.getElementById('doctorSelectHelp');
    const insuranceCard = document.getElementById('insuranceCard');
    const selectedServicesCard = document.getElementById('selectedServicesCard');
    const serviceFilterInput = document.getElementById('serviceFilter');
    const showExtraServicesInput = document.getElementById('showExtraServices');
    const defaultVisitDate = new Date().toISOString().split('T')[0];
    const visitOptionsUrlTemplate = @json(route('admin.departments.visit-options', ['department' => '__DEPARTMENT__']));
    const canManagePatientInsurance = @json(auth()->check() && auth()->user()->can('patients.edit'));

    let patientInsurances = [];
    let selectedInsurance = null;
    let availableServices = [];
    let availableDoctors = [];
    let selectedServices = []; // [{service_catalog_id, department_id, doctor_id, name, price, quantity}]
    let lastHandledDepartmentValue = departmentSelect.value;
    let lastHandledDepartmentAt = 0;

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function refreshVisitSelect2(select) {
        if (!hasSelect2()) return;

        const $select = jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.prop('disabled', select.disabled).trigger('change.select2');
        }
    }

    function initSearchableVisitSelects() {
        if (!hasSelect2()) return;

        const searchableOptions = function(select, fallbackPlaceholder) {
            return {
                placeholder: select.dataset.placeholder || fallbackPlaceholder,
                allowClear: true,
                minimumResultsForSearch: 0,
                width: '100%',
            };
        };

        const $department = jQuery(departmentSelect);
        if (!$department.hasClass('select2-hidden-accessible')) {
            $department.select2(searchableOptions(departmentSelect, 'Search department...'));
            $department.on('select2:select select2:clear', function() {
                window.setTimeout(handleDepartmentChange, 0);
            });
        }

        const $doctor = jQuery(doctorSelect);
        if (!$doctor.hasClass('select2-hidden-accessible')) {
            $doctor.select2(searchableOptions(doctorSelect, 'Search doctor/staff...'));
        }
    }

    // ==========================================
    // Scheduling toggle based on date
    // ==========================================
    function checkScheduling() {
        const today = new Date().toISOString().split('T')[0];
        const selectedDate = visitDateInput.value;
        const isFuture = selectedDate > today;

        schedulingFields.style.display = isFuture ? '' : 'none';
        walkInInfo.classList.toggle('d-none', isFuture);
        scheduledInfo.classList.toggle('d-none', !isFuture);
        submitBtnText.textContent = isFuture ? visitI18n.schedule_visit_btn : visitI18n.create_visit_btn;
    }
    visitDateInput.addEventListener('change', checkScheduling);
    checkScheduling();

    function showFormFeedback(type, html) {
        formFeedback.className = 'alert alert-' + type;
        formFeedback.innerHTML = html;
        formFeedback.classList.remove('d-none');
        window.scrollTo({ top: formFeedback.offsetTop - 100, behavior: 'smooth' });
    }

    function clearFormFeedback() {
        formFeedback.className = 'alert d-none';
        formFeedback.innerHTML = '';
    }

    function clearValidationErrors() {
        visitForm.querySelectorAll('.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        visitForm.querySelectorAll('.select2-selection.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        visitForm.querySelectorAll('.dynamic-invalid-feedback').forEach(function(element) {
            element.remove();
        });

        selectedServicesCard.classList.remove('border', 'border-danger');
    }

    function inputNameFromDot(field) {
        return field.split('.').reduce(function(name, part, index) {
            return index === 0 ? part : name + '[' + part + ']';
        }, '');
    }

    function resolveFieldElement(field) {
        if (field === 'patient_id') {
            return searchInput;
        }

        return visitForm.querySelector('[name="' + inputNameFromDot(field) + '"]');
    }

    function appendFieldError(field, message) {
        if (field.startsWith('services.')) {
            selectedServicesCard.classList.add('border', 'border-danger');
            return false;
        }

        const fieldElement = resolveFieldElement(field);

        if (!fieldElement || fieldElement.type === 'hidden') {
            return false;
        }

        fieldElement.classList.add('is-invalid');

        let anchor = fieldElement.closest('.input-group') || fieldElement;
        if (fieldElement.classList.contains('select2-hidden-accessible')) {
            const container = fieldElement.nextElementSibling;
            if (container && container.classList.contains('select2-container')) {
                anchor = container;
                const selection = container.querySelector('.select2-selection');
                if (selection) selection.classList.add('is-invalid');
            }
        }

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block dynamic-invalid-feedback';
        feedback.textContent = message;
        anchor.insertAdjacentElement('afterend', feedback);

        return true;
    }

    function applyValidationErrors(errors) {
        const generalErrors = [];

        Object.entries(errors).forEach(function(entry) {
            const field = entry[0];
            const messages = entry[1];
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (!appendFieldError(field, message)) {
                generalErrors.push(message);
            }
        });

        if (generalErrors.length > 0) {
            showFormFeedback('danger', generalErrors.map(function(message) {
                return '<div>' + escapeHtml(message) + '</div>';
            }).join(''));
            return;
        }

        showFormFeedback('danger', visitI18n.correct_fields);
    }

    function setSubmitting(isSubmitting) {
        submitBtn.disabled = isSubmitting;
        submitBtnText.textContent = isSubmitting ? visitI18n.saving : '';

        if (!isSubmitting) {
            checkScheduling();
        }
    }

    function resetVisitFormState() {
        visitForm.reset();
        clearPatient();

        selectedServices = [];
        availableServices = [];
        patientInsurances = [];
        selectedInsurance = null;

        document.getElementById('insuranceList').innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>' + visitI18n.loading_insurances + '</div>';
        document.getElementById('insuranceFallbackBadge').style.display = 'none';
        document.getElementById('visitInsuranceId').value = '';

        departmentSelect.value = '';
        lastHandledDepartmentValue = departmentSelect.value;
        refreshVisitSelect2(departmentSelect);
        availableDoctors = [];
        repopulateDoctorSelect([]);
        if (showExtraServicesInput) {
            showExtraServicesInput.checked = false;
        }
        showServicesPlaceholder();
        renderBillingTable();

        if (serviceFilterInput) {
            serviceFilterInput.value = '';
        }

        visitDateInput.value = defaultVisitDate;
        checkScheduling();
    }

    visitForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        clearFormFeedback();
        clearValidationErrors();
        setSubmitting(true);

        try {
            const response = await fetch(visitForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(visitForm),
            });

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            const payload = isJson ? await response.json() : {};

            if (response.ok) {
                resetVisitFormState();
                showFormFeedback(
                    'success',
                    '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">'
                        + '<div><div class="fw-bold">' + escapeHtml(payload.message || visitI18n.created_successfully) + '</div>'
                        + '<div class="small text-muted">' + visitI18n.current_status + ' ' + escapeHtml(payload.status_label || '') + '</div></div>'
                        + '<div class="d-flex gap-2">'
                        + '<a href="' + escapeHtml(payload.redirect_url || '#') + '" class="btn btn-sm btn-success">' + visitI18n.open_visit + '</a>'
                        + '<button type="button" class="btn btn-sm btn-outline-success" id="createAnotherVisitBtn">' + visitI18n.create_another + '</button>'
                        + '</div></div>'
                );

                const createAnotherBtn = document.getElementById('createAnotherVisitBtn');
                if (createAnotherBtn) {
                    createAnotherBtn.addEventListener('click', function() {
                        clearFormFeedback();
                        if (hasSelect2() && jQuery(searchInput).hasClass('select2-hidden-accessible')) {
                            jQuery(searchInput).select2('open');
                        } else {
                            searchInput.focus();
                        }
                    });
                }

                return;
            }

            if (response.status === 422 && payload.errors) {
                applyValidationErrors(payload.errors);
                return;
            }

            const message = payload.message || visitI18n.failed_create_visit;
            showFormFeedback('danger', escapeHtml(message));
        } catch (error) {
            showFormFeedback('danger', visitI18n.network_error_visit);
        } finally {
            setSubmitting(false);
        }
    });

    // ==========================================
    // Patient search
    // ==========================================
    function patientDisplayText(patient) {
        if (!patient) return '';
        return [patient.patient_number, patient.full_name].filter(Boolean).join(' - ') || patient.text || '';
    }

    function syncPatientSelectOption(patient) {
        if (!patient || !patient.id) return;

        const id = String(patient.id);
        const text = patientDisplayText(patient);
        let option = Array.from(searchInput.options).find(function(opt) {
            return String(opt.value) === id;
        });

        if (!option) {
            option = new Option(text, id, true, true);
            searchInput.appendChild(option);
        } else {
            option.textContent = text;
            option.selected = true;
        }

        if (hasSelect2()) {
            jQuery(searchInput).trigger('change.select2');
        }
    }

    function initPatientSearchSelect2() {
        if (!hasSelect2()) return;

        const $patient = jQuery(searchInput);
        if ($patient.hasClass('select2-hidden-accessible')) return;

        $patient.select2({
            placeholder: searchInput.dataset.placeholder || 'Type patient name, ID, phone, or Ghana Card number...',
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '{{ route("admin.visits.patient-search") }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: (data || []).map(function(patient) {
                            patient.id = String(patient.id);
                            patient.text = patientDisplayText(patient);
                            return patient;
                        }),
                    };
                },
                cache: true,
            },
            templateResult: function(patient) {
                if (patient.loading) return patient.text;

                const meta = [
                    patient.patient_number || '',
                    patient.phone || visitI18n.no_phone,
                ].filter(Boolean);
                if (patient.last_visit_date) {
                    meta.push(visitI18n.last_visit_label + ' ' + patient.last_visit_date);
                }

                return jQuery('<span>').html(
                    '<span class="fw-medium">' + escapeHtml(patient.full_name || patient.text || '') + '</span>' +
                    '<small class="text-muted d-block">' + meta.map(escapeHtml).join(' &bull; ') + '</small>'
                );
            },
            templateSelection: function(patient) {
                return patientDisplayText(patient);
            },
        }).on('select2:select', function(event) {
            selectPatient(event.params.data);
        }).on('select2:clear', function() {
            clearPatient({ keepSelect: true });
        });
    }

    initPatientSearchSelect2();

    window.selectPatient = function(patient) {
        patientIdInput.value = patient.id;
        syncPatientSelectOption(patient);
        document.getElementById('patientInitial').textContent = patient.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent = patient.full_name;
        document.getElementById('patientNumber').textContent = patient.patient_number;
        document.getElementById('patientPhone').textContent = patient.phone || visitI18n.no_phone;

        const lastVisitEl = document.getElementById('patientLastVisit');
        if (patient.last_visit_date) {
            lastVisitEl.innerHTML = '&bull; ' + visitI18n.last_visit_label + ' <strong>' + escapeHtml(patient.last_visit_date) + '</strong>';
            lastVisitEl.classList.remove('d-none');
        } else {
            lastVisitEl.classList.add('d-none');
        }

        // Show deceased warning if applicable
        const deceasedWarning = document.getElementById('deceasedWarning');
        if (patient.is_deceased) {
            deceasedWarning.classList.remove('d-none');
        } else {
            deceasedWarning.classList.add('d-none');
        }

        const activeAdmissionWarning = document.getElementById('activeAdmissionWarning');
        const activeAdmissionText = document.getElementById('activeAdmissionText');
        if (patient.active_admission) {
            const admission = patient.active_admission;
            let text = visitI18n.admission_prefix + ' ' + admission.admission_number;
            if (admission.ward || admission.bed) {
                text += ' - ' + [admission.ward, admission.bed ? visitI18n.bed_prefix + ' ' + admission.bed : null].filter(Boolean).join(' / ');
            }
            activeAdmissionText.textContent = text + '.';
            activeAdmissionWarning.classList.remove('d-none');
        } else {
            activeAdmissionText.textContent = '';
            activeAdmissionWarning.classList.add('d-none');
            const overrideReason = document.getElementById('admissionOverrideReason');
            if (overrideReason) {
                overrideReason.value = '';
            }
        }

        patientInfo.classList.remove('d-none');

        loadPatientInsurances(patient.id);
    };

    window.clearPatient = function(options) {
        options = options || {};
        patientIdInput.value = '';
        if (!options.keepSelect) {
            searchInput.value = '';
            if (hasSelect2()) {
                jQuery(searchInput).val(null).trigger('change.select2');
            }
        }
        patientInfo.classList.add('d-none');
        document.getElementById('activeAdmissionWarning').classList.add('d-none');
        const overrideReason = document.getElementById('admissionOverrideReason');
        if (overrideReason) {
            overrideReason.value = '';
        }
        insuranceCard.classList.add('d-none');
        document.getElementById('visitInsuranceId').value = '';
        patientInsurances = [];
        selectedInsurance = null;
        recalculateBilling();
    };

    // ==========================================
    // Insurance Loading & Selection
    // ==========================================
    function loadPatientInsurances(patientId) {
        insuranceCard.classList.remove('d-none');
        document.getElementById('insuranceList').innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>' + visitI18n.loading_insurances + '</div>';

        fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            patientInsurances = data.insurances || [];
            const defaultId = data.default_insurance_id;
            const isFallback = data.is_fallback;

            document.getElementById('insuranceFallbackBadge').style.display = isFallback ? '' : 'none';

            let html = '';
            if (patientInsurances.length === 0) {
                html = '<div class="text-muted text-center py-2">' + visitI18n.no_insurances_cash + '</div>';
            } else {
                html = '<div class="list-group">';
                patientInsurances.forEach(function(ins) {
                    const isDefault = ins.id == defaultId;
                    const isDisabled = !ins.is_valid && !ins.is_default;
                    const badgeClass = ins.is_valid ? 'bg-success' : (ins.is_expired ? 'bg-danger' : 'bg-secondary');
                    const statusText = ins.is_valid ? visitI18n.valid_status : (ins.is_expired ? visitI18n.expired_status : visitI18n.inactive_status);

                    html += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-3 ' + (isDisabled ? 'opacity-50' : '') + '">';
                    html += '<input type="radio" name="_insurance_radio" class="form-check-input insurance-radio" value="' + ins.id + '" data-ins-id="' + ins.id + '"';
                    if (isDefault) html += ' checked';
                    if (isDisabled) html += ' disabled';
                    html += '>';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="fw-medium">' + escapeHtml(ins.provider_name) + ' <span class="badge bg-' + ins.type_color + ' ms-1">' + escapeHtml(ins.type_label) + '</span>';
                    if (ins.tier_name) html += ' <span class="badge bg-primary bg-opacity-75 ms-1">' + escapeHtml(ins.tier_name) + '</span>';
                    const memberBadge = ins.member_type === 'beneficiary' ? 'bg-warning text-dark' : 'bg-info';
                    const memberLabel = ins.member_type === 'beneficiary' ? visitI18n.beneficiary_label : visitI18n.card_holder_label;
                    html += ' <span class="badge ' + memberBadge + ' ms-1">' + memberLabel + '</span>';
                    html += '</div>';
                    html += '<small class="text-muted">';
                    if (ins.membership_number) html += visitI18n.member_label + ' ' + escapeHtml(ins.membership_number) + ' &bull; ';
                    if (ins.expiry_date) {
                        const expiry = new Date(ins.expiry_date);
                        const today = new Date(); today.setHours(0, 0, 0, 0);
                        const daysLeft = Math.ceil((expiry - today) / 86400000);
                        let daysText, badgeClass;
                        if (daysLeft > 0) {
                            daysText = daysLeft + ' day' + (daysLeft === 1 ? '' : 's') + ' left';
                            badgeClass = daysLeft <= 30 ? 'bg-warning text-dark' : 'bg-light text-muted border';
                        } else if (daysLeft === 0) {
                            daysText = visitI18n.expires_today;
                            badgeClass = 'bg-warning text-dark';
                        } else {
                            daysText = Math.abs(daysLeft) + ' day' + (Math.abs(daysLeft) === 1 ? '' : 's') + ' ago';
                            badgeClass = 'bg-danger text-white';
                        }
                        html += visitI18n.expires_label + ' ' + ins.expiry_date + ' <span class="badge ' + badgeClass + '">' + daysText + '</span>';
                    } else html += visitI18n.no_expiry;
                    html += '</small>';
                    html += '</div>';
                    html += '<div class="text-end">';
                    html += '<span class="badge ' + badgeClass + '">' + statusText + '</span>';
                    if (ins.coverage_percentage != null) {
                        html += '<div class="small text-muted mt-1">' + ins.coverage_percentage + '%</div>';
                    }
                    if (!ins.is_default && canManagePatientInsurance) {
                        const editLabel = ins.is_expired ? visitI18n.renew_btn : visitI18n.edit_btn;
                        const editIcon  = ins.is_expired ? 'ti-refresh' : 'ti-pencil';
                        html += '<button type="button" class="btn btn-link btn-sm p-0 mt-1 edit-insurance-btn"'
                              + ' data-id="' + ins.id + '"'
                              + ' data-ins-id="' + ins.id + '"'
                              + ' data-patient-id="' + patientId + '"'
                              + ' data-provider="' + (ins.provider_id || '') + '"'
                              + ' data-provider-name="' + escapeAttribute(ins.provider_name || '') + '"'
                              + ' data-tier="' + (ins.tier_id || '') + '"'
                              + ' data-tier-name="' + escapeAttribute(ins.tier_name || '') + '"'
                              + ' data-member-type="' + escapeAttribute(ins.member_type || 'holder') + '"'
                              + ' data-card-holder="' + (ins.card_holder_insurance_id || '') + '"'
                              + ' data-membership="' + escapeAttribute(ins.membership_number || '') + '"'
                              + ' data-policy="' + escapeAttribute(ins.policy_number || '') + '"'
                              + ' data-expiry="' + escapeAttribute(ins.expiry_date || '') + '"'
                              + ' data-active="' + (ins.is_active ? '1' : '0') + '">'
                              + '<i class="ti ' + editIcon + ' me-1"></i>' + editLabel
                              + '</button>';
                    }
                    html += '</div>';
                    html += '</label>';
                });
                html += '</div>';
            }

            document.getElementById('insuranceList').innerHTML = html;

            // Attach radio change handlers
            document.querySelectorAll('.insurance-radio').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    selectInsurance(parseInt(this.dataset.insId));
                });
            });

            // Attach edit/renew handlers
            document.querySelectorAll('.edit-insurance-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.openPatientInsuranceEditModal) {
                        window.openPatientInsuranceEditModal(this);
                    } else {
                        openInsuranceModal('edit', parseInt(this.dataset.insId));
                    }
                });
            });

            // Auto-select the default
            if (defaultId) {
                selectInsurance(defaultId);
            }
        })
        .catch(function() {
            document.getElementById('insuranceList').innerHTML = '<div class="text-danger text-center py-2">' + visitI18n.failed_load_insurances + '</div>';
        });
    }

    function selectInsurance(insId) {
        selectedInsurance = patientInsurances.find(i => i.id === insId) || null;
        document.getElementById('visitInsuranceId').value = insId || '';

        // Re-price all already-selected services for the new insurance
        selectedServices.forEach(function(svc) {
            svc.price = resolveServicePrice(svc.originalService);
        });

        recalculateBilling();
        renderBillingTable();

        // Update displayed prices in the available services list
        availableServices.forEach(function(svc) {
            const el = document.querySelector('.svc-price-display[data-svc-id="' + svc.id + '"]');
            if (el) {
                el.textContent = '\u20B5' + formatNumber(resolveServicePrice(svc));
            }
        });

        // Hook: notify the verification panel that the insurance changed.
        if (typeof onInsuranceSelectionChanged === 'function') {
            onInsuranceSelectionChanged(insId);
        }
    }

    /**
     * Resolve the applicable unit price for a service object
     * given the currently selectedInsurance.
     * Priority: provider-specific override -> type default -> base price
     */
    function resolveServicePrice(svc) {
        if (!svc) return 0;
        const insType = selectedInsurance ? selectedInsurance.type : null;
        const insProviderId = selectedInsurance ? selectedInsurance.provider_id : null; // numeric

        // Provider-specific override
        if (insType && insProviderId && svc.provider_prices
            && svc.provider_prices[insProviderId]
            && svc.provider_prices[insProviderId][insType] !== undefined) {
            return svc.provider_prices[insProviderId][insType];
        }
        // Type default
        if (insType && svc.type_prices && svc.type_prices[insType] !== undefined) {
            return svc.type_prices[insType];
        }
        // Base price fallback
        return svc.price;
    }

    // ==========================================
    // Department â†’ Services loading
    // ==========================================
    function handleDepartmentChange() {
        const deptId = departmentSelect.value;
        const now = Date.now();
        if (deptId === lastHandledDepartmentValue && now - lastHandledDepartmentAt < 100) {
            return;
        }
        lastHandledDepartmentValue = deptId;
        lastHandledDepartmentAt = now;

        if (!deptId) {
            availableServices = [];
            availableDoctors = [];
            repopulateDoctorSelect([]);
            if (showExtraServicesInput) {
                showExtraServicesInput.checked = false;
            }
            showServicesPlaceholder();
            return;
        }
        if (showExtraServicesInput) {
            showExtraServicesInput.checked = false;
        }
        loadVisitOptionsForDepartment(deptId);
    }

    departmentSelect.addEventListener('change', handleDepartmentChange);
    initSearchableVisitSelects();

    function loadVisitOptionsForDepartment(deptId) {
        showVisitOptionsLoading();
        repopulateDoctorSelect([]);

        fetch(visitOptionsUrlTemplate.replace('__DEPARTMENT__', encodeURIComponent(deptId)), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            availableServices = data.services || [];
            availableDoctors = data.doctors || [];
            repopulateDoctorSelect(availableDoctors);
            renderServicesList();
        })
        .catch(() => {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-alert-circle me-1 text-danger"></i>' + visitI18n.failed_load_dept;
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            repopulateDoctorSelect([]);
        });
    }

    // ==========================================
    // Services rendering and selection
    // ==========================================
    function isConsultationService(svc) {
        return String(svc?.category || '').toLowerCase() === 'consultation';
    }

    function getVisibleAvailableServices() {
        if (showExtraServicesInput && showExtraServicesInput.checked) {
            return availableServices;
        }

        return availableServices.filter(isConsultationService);
    }

    function renderServicesList() {
        if (availableServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-info-circle me-1 text-muted"></i>' + visitI18n.no_services_dept;
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none');
            document.getElementById('servicesItems').innerHTML = '';
            return;
        }

        const visibleServices = getVisibleAvailableServices();
        const extraServicesCount = availableServices.filter(function(svc) {
            return !isConsultationService(svc);
        }).length;

        if (visibleServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-info-circle me-1 text-muted"></i>' + visitI18n.no_consult_services + (extraServicesCount ? '.' : '.');
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none');
            document.getElementById('servicesItems').innerHTML = '';
            return;
        }

        document.getElementById('servicesPlaceholder').classList.add('d-none');
        document.getElementById('servicesContent').classList.remove('d-none');

        const container = document.getElementById('servicesItems');
        let html = '';
        visibleServices.forEach(function(svc) {
            const displayPrice = resolveServicePrice(svc);
            const fmtPrice = '\u20B5' + formatNumber(displayPrice);

            html += '<div class="service-item d-flex align-items-center justify-content-between py-2 px-2 border-bottom bg-white rounded mb-1" data-name="' + escapeHtml(svc.name.toLowerCase()) + '">';
            html += '<div>';
            html += '<span class="fw-medium">' + escapeHtml(svc.name) + '</span>';
            html += ' <span class="badge bg-light text-dark ms-1">' + escapeHtml(svc.code) + '</span>';
            html += '<div class="small text-muted">' + escapeHtml(svc.category) + '</div>';
            html += '</div>';
            html += '<div class="d-flex align-items-center gap-2">';
            html += '<span class="fw-bold text-success svc-price-display" data-svc-id="' + svc.id + '">' + fmtPrice + '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-primary add-service-btn"'
                + ' data-id="' + svc.id + '"'
                + ' data-name="' + escapeHtml(svc.name) + '"'
                + ' title="Add to billing">';
            html += '<i class="ti ti-plus"></i></button>';
            html += '</div>';
            html += '</div>';
        });
        container.innerHTML = html;

        container.querySelectorAll('.add-service-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const svcId = parseInt(this.dataset.id);
                const svcObj = availableServices.find(s => s.id === svcId);
                addServiceToBilling(svcId, this.dataset.name, svcObj);
            });
        });
    }

    document.getElementById('serviceFilter').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.service-item').forEach(function(item) {
            item.style.display = item.dataset.name.includes(filter) ? '' : 'none';
        });
    });

    if (showExtraServicesInput) {
        showExtraServicesInput.addEventListener('change', function() {
            if (serviceFilterInput) {
                serviceFilterInput.value = '';
            }
            renderServicesList();
        });
    }

    function showServicesPlaceholder() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-list-search me-1"></i>' + visitI18n.select_dept_load_services;
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    function showVisitOptionsLoading() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-loader me-1"></i>' + visitI18n.loading_services_doctors;
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    // ==========================================
    // Billing line management
    // ==========================================
    function addServiceToBilling(serviceId, serviceName, svcObj) {
        const resolvedPrice = resolveServicePrice(svcObj);
        const existing = selectedServices.find(s => s.service_catalog_id === serviceId);
        if (existing) {
            existing.quantity++;
            existing.price = resolvedPrice; // refresh price in case insurance changed
        } else {
            const selectedDeptOption = departmentSelect.options[departmentSelect.selectedIndex];
            const isConsultation = (svcObj?.department_type || '').toString() === 'consultation';
            const departmentId = svcObj?.department_id || departmentSelect.value || null;
            const existingDepartmentRoute = isConsultation
                ? selectedServices.find(s => String(s.department_id) === String(departmentId) && s.department_type === 'consultation')
                : null;
            const selectedDoctor = isConsultation && !existingDepartmentRoute && doctorSelect.value
                ? availableDoctors.find(doc => String(doc.id) === String(doctorSelect.value))
                : null;

            selectedServices.push({
                service_catalog_id: serviceId,
                department_id: departmentId,
                department_name: selectedDeptOption ? selectedDeptOption.textContent : '',
                department_type: svcObj?.department_type || null,
                doctor_id: existingDepartmentRoute ? existingDepartmentRoute.doctor_id : (selectedDoctor ? selectedDoctor.id : null),
                doctor_name: existingDepartmentRoute ? existingDepartmentRoute.doctor_name : (selectedDoctor ? selectedDoctor.name : null),
                name: serviceName,
                price: resolvedPrice,
                quantity: 1,
                originalService: svcObj,
            });
        }

        renderBillingTable();
    }

    function removeServiceFromBilling(index) {
        selectedServices.splice(index, 1);
        renderBillingTable();
    }

    function updateServiceQuantity(index, newQty) {
        if (newQty < 1) { removeServiceFromBilling(index); return; }
        selectedServices[index].quantity = newQty;
        renderBillingTable();
    }

    function renderBillingTable() {
        const card = document.getElementById('selectedServicesCard');
        const tbody = document.getElementById('billingBody');

        if (selectedServices.length === 0) {
            card.classList.add('d-none');
            tbody.innerHTML = '';
            recalculateBilling();
            return;
        }

        card.classList.remove('d-none');
        let html = '';
        const groupsByKey = {};
        groupSelectedServicesByDepartment().forEach(function(group) {
            groupsByKey[group.key] = group;
        });
        const renderedDepartments = {};

        selectedServices.forEach(function(svc, idx) {
            const groupKey = String(svc.department_id || 'none');
            const group = groupsByKey[groupKey];
            if (group && !renderedDepartments[groupKey]) {
                const groupTotal = group.services.reduce(function(sum, item) {
                    return sum + (item.service.price * (item.service.quantity || 1));
                }, 0);

                html += '<tr class="table-light">';
                html += '<td colspan="3">';
                html += '<div class="d-flex flex-wrap justify-content-between gap-2">';
                html += '<span class="fw-semibold text-dark">' + escapeHtml(group.departmentName || '') + ' ' + visitI18n.dept_session_label + '</span>';
                html += '<span class="text-muted small">';
                html += group.doctorName ? visitI18n.doctor_prefix + ' ' + escapeHtml(group.doctorName) : visitI18n.doctor_unassigned;
                html += ' &middot; ' + visitI18n.dept_total_label + ' ₵' + formatNumber(groupTotal);
                html += '</span></div></td></tr>';
                renderedDepartments[groupKey] = true;
            }

            // Quantity is always 1 — UI no longer exposes a quantity selector.
            const qty = svc.quantity || 1;
            const lineTotal = svc.price * qty;

            html += '<tr>';
            html += '<td>' + escapeHtml(svc.name);
            html += '<input type="hidden" name="services[' + idx + '][service_catalog_id]" value="' + svc.service_catalog_id + '">';
            html += '<input type="hidden" name="services[' + idx + '][department_id]" value="' + (svc.department_id || '') + '">';
            html += '<input type="hidden" name="services[' + idx + '][quantity]" value="' + qty + '">';
            html += '<input type="hidden" name="services[' + idx + '][doctor_id]" value="' + (svc.doctor_id || '') + '">';
            html += '</td>';
            html += '<td class="text-end fw-medium">\u20B5' + formatNumber(lineTotal) + '</td>';
            html += '<td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-service-btn" data-index="' + idx + '"><i class="ti ti-trash"></i></button></td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        tbody.querySelectorAll('.remove-service-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                removeServiceFromBilling(parseInt(this.dataset.index));
            });
        });

        updateRouteDoctorSummary();
        recalculateBilling();
    }

    function calculateInsuranceForLine(svc) {
        if (!selectedInsurance || selectedInsurance.is_default) return 0;
        if (!selectedInsurance.is_valid) return 0;

        const coverageRate = (selectedInsurance.coverage_percentage || 0) / 100;
        return Math.round(svc.price * svc.quantity * coverageRate * 100) / 100;
    }

    function recalculateBilling() {
        let totalAmount = 0;

        selectedServices.forEach(function(svc) {
            totalAmount += svc.price * svc.quantity;
        });

        document.getElementById('totalAmount').textContent = '\u20B5' + formatNumber(totalAmount);
    }

    function updateRouteDoctorSummary() {
        const summary = document.getElementById('routeDoctorSummary');
        if (!summary) return;

        const consultationGroups = groupSelectedServicesByDepartment()
            .filter(group => group.departmentType === 'consultation');

        if (consultationGroups.length === 0) {
            summary.textContent = visitI18n.consult_sessions_note;
            return;
        }

        summary.textContent = consultationGroups.map(function(group) {
            return (group.departmentName || '') + ': ' + (group.doctorName || visitI18n.unassigned_doctor);
        }).join(' | ');
    }

    function groupSelectedServicesByDepartment() {
        const groups = [];
        selectedServices.forEach(function(svc, index) {
            const key = String(svc.department_id || 'none');
            let group = groups.find(item => item.key === key);
            if (!group) {
                group = {
                    key,
                    departmentName: svc.department_name || '',
                    departmentType: svc.department_type || '',
                    doctorId: svc.doctor_id || null,
                    doctorName: svc.doctor_name || null,
                    services: [],
                };
                groups.push(group);
            }
            group.services.push({ service: svc, index });
        });
        return groups;
    }

    function repopulateDoctorSelect(doctors) {
        const currentVal = doctorSelect.value;
        doctorSelect.innerHTML = '';

        if (!departmentSelect.value) {
            doctorSelect.disabled = true;
            doctorSelect.innerHTML = '<option value="">' + visitI18n.select_dept_first + '</option>';
            // doctorSelectHelp.textContent = 'Doctors load from specialties linked to the selected department.';
            refreshVisitSelect2(doctorSelect);
            return;
        }

        doctorSelect.disabled = false;
        doctorSelect.innerHTML = '<option value="">' + visitI18n.assign_doctor_opt + '</option>';
        doctors.forEach(function(doc) {
            const opt = document.createElement('option');
            opt.value = doc.id;
            opt.textContent = doc.name;
            if (doc.specialties && doc.specialties.length > 0) {
                opt.textContent += ' (' + doc.specialties.join(', ') + ')';
            }
            if (doc.id == currentVal) opt.selected = true;
            doctorSelect.appendChild(opt);
        });

        // doctorSelectHelp.textContent = doctors.length
        //     ? 'Doctor is stored on the department consultation session when you add a consultation service.'
        //     : 'No doctor linked to this department through specialty.';
        refreshVisitSelect2(doctorSelect);
    }

    // ==========================================
    // Auto-load if patient is pre-selected
    // ==========================================
    @if($selectedPatient)
        loadPatientInsurances({{ $selectedPatient->id }});
    @endif

    // ==========================================
    // Utility functions
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ==========================================
    // GENERIC INSURANCE VERIFICATION (provider-agnostic)
    // ==========================================
    const verifyUrl = "{{ route('admin.insurance.verify') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function resetVerificationPanel() {
        const panel = document.getElementById('verificationPanel');
        if (!panel) return;
        panel.classList.add('d-none');
        document.getElementById('insuranceVerificationId').value = '';
        document.getElementById('verificationFeedback').innerHTML = '';
        document.getElementById('verificationCodeRow').style.display = 'none';
        document.getElementById('verificationManualRow').style.display = 'none';
        const refInput = document.getElementById('verificationReferenceInput');
        if (refInput) refInput.value = '';
    }

    function renderVerificationStatus(data) {
        const badge = document.getElementById('verificationStatusBadge');
        badge.className = 'badge bg-' + (data.status_color || 'secondary');
        badge.textContent = data.status_label || data.status || 'Unknown';

        const meta = [];
        if (data.provider?.name)    meta.push(data.provider.name);
        if (data.provider?.method)  meta.push('method: ' + data.provider.method);
        if (data.provider?.channel) meta.push('via ' + data.provider.channel);
        if (data.driver)            meta.push('driver: ' + data.driver);
        document.getElementById('verificationProviderMeta').textContent = meta.join(' \u2022 ') || '\u2014';

        const parts = [];
        if (data.message)        parts.push('<div>' + escapeHtml(data.message) + '</div>');
        // if (data.member_name)    parts.push('<div><strong>Member:</strong> ' + escapeHtml(data.member_name) + '</div>');
        if (data.reference_code) parts.push('<div><strong>Reference:</strong> <code>' + escapeHtml(data.reference_code) + '</code></div>');
        // if (data.expires_at)     parts.push('<div><strong>Expires:</strong> ' + escapeHtml(data.expires_at) + '</div>');
        document.getElementById('verificationFeedback').innerHTML = parts.join('');

        if (data.acceptable && data.verification_id) {
            document.getElementById('insuranceVerificationId').value = data.verification_id;
        } else {
            document.getElementById('insuranceVerificationId').value = '';
        }

        const codeRow   = document.getElementById('verificationCodeRow');
        const manualRow = document.getElementById('verificationManualRow');
        if (data.requires_reference_code) {
            codeRow.style.display = '';
            manualRow.style.display = 'none';
        } else if (data.acceptable) {
            codeRow.style.display = 'none';
            manualRow.style.display = 'none';
        } else {
            codeRow.style.display = 'none';
            manualRow.style.display = '';
        }
    }

    async function runVerification(refCode) {
        const piId = document.getElementById('visitInsuranceId').value;
        if (!piId) return;
        const fb = document.getElementById('verificationFeedback');
        fb.innerHTML = '<i class="ti ti-loader me-1"></i>' + visitI18n.verifying;
        try {
            const resp = await fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    patient_insurance_id: piId,
                    reference_code: refCode || null,
                }),
            });
            const data = await resp.json();
            if (!resp.ok) {
                fb.innerHTML = '<div class="text-danger">' + escapeHtml(data.message || visitI18n.verification_failed) + '</div>';
                return;
            }
            renderVerificationStatus(data);
        } catch (e) {
            fb.innerHTML = '<div class="text-danger">' + visitI18n.verification_failed + ': ' + escapeHtml(e.message) + '</div>';
        }
    }

    // Hook called from selectInsurance() whenever the user changes selection.
    function onInsuranceSelectionChanged(insId) {
        resetVerificationPanel();
        if (!insId) return;
        document.getElementById('verificationPanel').classList.remove('d-none');
        runVerification(null);
    }

    document.getElementById('runVerificationBtn').addEventListener('click', () => {
        const code = document.getElementById('verificationReferenceInput').value.trim();
        runVerification(code || null);
    });
    document.getElementById('runVerificationBtn2').addEventListener('click', () => {
        runVerification(null);
    });

    // ==========================================
    // Insurance Add/Edit/Renew Modal (SPA)
    // ==========================================
    const sharedAddInsuranceModalEl = document.getElementById('addInsuranceModal');
    const sharedAddInsuranceModal = sharedAddInsuranceModalEl ? new bootstrap.Modal(sharedAddInsuranceModalEl) : null;
    const sharedAddInsuranceForm = document.getElementById('addInsuranceForm');
    const sharedAddInsuranceFeedback = document.getElementById('addInsuranceFormFeedback');
    const sharedAddInsuranceSubmit = document.getElementById('addInsuranceSubmitBtn');
    const sharedEditInsuranceModalEl = document.getElementById('editInsuranceModal');
    const sharedEditInsuranceForm = document.getElementById('editInsuranceForm');
    const sharedEditInsuranceFeedback = document.getElementById('editInsuranceFormFeedback');
    const sharedEditInsuranceSubmit = document.getElementById('editInsuranceSubmitBtn');

    function resetSharedAddInsuranceForm() {
        if (!sharedAddInsuranceForm) return;

        sharedAddInsuranceForm.reset();
        sharedAddInsuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        sharedAddInsuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());

        if (sharedAddInsuranceFeedback) {
            sharedAddInsuranceFeedback.className = 'alert d-none';
            sharedAddInsuranceFeedback.innerHTML = '';
        }

        const provider = document.getElementById('addInsProvider');
        const tier = document.getElementById('addInsTier');
        const tierInfo = document.getElementById('addInsTierInfo');

        if (provider) {
            provider.innerHTML = '<option value="">' + visitI18n.select_type_first + '</option>';
            provider.disabled = true;
        }

        if (tier) {
            tier.innerHTML = '<option value="">' + visitI18n.select_provider_first + '</option>';
            tier.disabled = true;
        }

        if (tierInfo) {
            tierInfo.textContent = "If no tier is chosen, the provider's default tier will be used.";
        }
    }

    function showSharedAddInsuranceFeedback(type, text) {
        if (!sharedAddInsuranceFeedback) return;

        sharedAddInsuranceFeedback.className = 'alert alert-' + type;
        sharedAddInsuranceFeedback.innerHTML = text;
        sharedAddInsuranceFeedback.classList.remove('d-none');
    }

    function applySharedAddInsuranceErrors(errors) {
        Object.entries(errors || {}).forEach(([field, msgs]) => {
            const el = sharedAddInsuranceForm.querySelector('[name="' + field + '"]');
            if (!el) return;

            el.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block dynamic-invalid-feedback';
            fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            el.insertAdjacentElement('afterend', fb);
        });
    }

    function openSharedAddInsuranceModal() {
        if (!sharedAddInsuranceModal || !sharedAddInsuranceForm) return;

        const patientId = patientIdInput.value;
        if (!patientId) {
            alert(visitI18n.select_patient_first);
            return;
        }

        resetSharedAddInsuranceForm();
        sharedAddInsuranceForm.action = '{{ url("/admin/patients") }}/' + patientId + '/insurances';
        sharedAddInsuranceModal.show();
    }

    if (sharedAddInsuranceForm) {
        sharedAddInsuranceForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const patientId = patientIdInput.value;
            if (!patientId) {
                showSharedAddInsuranceFeedback('danger', visitI18n.select_patient_first);
                return;
            }

            sharedAddInsuranceForm.action = '{{ url("/admin/patients") }}/' + patientId + '/insurances';
            sharedAddInsuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            sharedAddInsuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
            if (sharedAddInsuranceFeedback) {
                sharedAddInsuranceFeedback.classList.add('d-none');
                sharedAddInsuranceFeedback.innerHTML = '';
            }

            const originalLabel = sharedAddInsuranceSubmit ? sharedAddInsuranceSubmit.innerHTML : '';
            if (sharedAddInsuranceSubmit) {
                sharedAddInsuranceSubmit.disabled = true;
                sharedAddInsuranceSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + visitI18n.saving + '';
            }

            try {
                const resp = await fetch(sharedAddInsuranceForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(sharedAddInsuranceForm),
                });

                const ct = resp.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await resp.json() : {};

                if (resp.ok) {
                    showSharedAddInsuranceFeedback('success', data.message || visitI18n.insurance_saved);
                    await reloadInsuranceListAndSelect(data.insurance_id || null);
                    setTimeout(() => sharedAddInsuranceModal.hide(), 600);
                    return;
                }

                if (resp.status === 422 && data.errors) {
                    applySharedAddInsuranceErrors(data.errors);
                    showSharedAddInsuranceFeedback('danger', data.message || visitI18n.correct_fields_short);
                    return;
                }

                showSharedAddInsuranceFeedback('danger', data.message || visitI18n.failed_save_insurance);
            } catch (err) {
                showSharedAddInsuranceFeedback('danger', visitI18n.network_error_insurance);
            } finally {
                if (sharedAddInsuranceSubmit) {
                    sharedAddInsuranceSubmit.disabled = false;
                    sharedAddInsuranceSubmit.innerHTML = originalLabel;
                }
            }
        });
    }

    function showSharedEditInsuranceFeedback(type, text) {
        if (!sharedEditInsuranceFeedback) return;

        sharedEditInsuranceFeedback.className = 'alert alert-' + type;
        sharedEditInsuranceFeedback.innerHTML = text;
        sharedEditInsuranceFeedback.classList.remove('d-none');
    }

    function applySharedEditInsuranceErrors(errors) {
        Object.entries(errors || {}).forEach(([field, msgs]) => {
            const el = sharedEditInsuranceForm.querySelector('[name="' + field + '"]');
            if (!el) return;

            el.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block dynamic-invalid-feedback';
            fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            el.insertAdjacentElement('afterend', fb);
        });
    }

    if (sharedEditInsuranceForm) {
        sharedEditInsuranceForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const patientId = patientIdInput.value || sharedEditInsuranceForm.dataset.patientId;
            const insuranceId = sharedEditInsuranceForm.dataset.insuranceId;
            if (!patientId || !insuranceId) {
                showSharedEditInsuranceFeedback('danger', visitI18n.select_patient_ins_update);
                return;
            }

            if (!sharedEditInsuranceForm.action || sharedEditInsuranceForm.action.endsWith('#')) {
                sharedEditInsuranceForm.action = '{{ url("/admin/patients") }}/' + patientId + '/insurances/' + insuranceId;
            }

            sharedEditInsuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            sharedEditInsuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
            if (sharedEditInsuranceFeedback) {
                sharedEditInsuranceFeedback.classList.add('d-none');
                sharedEditInsuranceFeedback.innerHTML = '';
            }

            const originalLabel = sharedEditInsuranceSubmit ? sharedEditInsuranceSubmit.innerHTML : '';
            if (sharedEditInsuranceSubmit) {
                sharedEditInsuranceSubmit.disabled = true;
                sharedEditInsuranceSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + visitI18n.saving + '';
            }

            try {
                const resp = await fetch(sharedEditInsuranceForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(sharedEditInsuranceForm),
                });

                const ct = resp.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await resp.json() : {};

                if (resp.ok) {
                    showSharedEditInsuranceFeedback('success', data.message || visitI18n.insurance_updated);
                    await reloadInsuranceListAndSelect(data.insurance_id || parseInt(insuranceId));
                    if (sharedEditInsuranceModalEl) {
                        setTimeout(() => bootstrap.Modal.getOrCreateInstance(sharedEditInsuranceModalEl).hide(), 600);
                    }
                    return;
                }

                if (resp.status === 422 && data.errors) {
                    applySharedEditInsuranceErrors(data.errors);
                    showSharedEditInsuranceFeedback('danger', data.message || visitI18n.correct_fields_short);
                    return;
                }

                showSharedEditInsuranceFeedback('danger', data.message || visitI18n.failed_update_insurance);
            } catch (err) {
                showSharedEditInsuranceFeedback('danger', visitI18n.network_error_ins_update);
            } finally {
                if (sharedEditInsuranceSubmit) {
                    sharedEditInsuranceSubmit.disabled = false;
                    sharedEditInsuranceSubmit.innerHTML = originalLabel;
                }
            }
        });
    }

    const insuranceModalEl   = document.getElementById('insuranceModal');
    const insuranceModal     = insuranceModalEl ? new bootstrap.Modal(insuranceModalEl) : null;
    const insuranceForm      = document.getElementById('insuranceForm');
    const insuranceFormFb    = document.getElementById('insuranceFormFeedback');
    const providerSelect     = document.getElementById('insuranceProviderSelect');
    const tierSelect         = document.getElementById('insuranceTierSelect');
    const insModalTitle      = document.getElementById('insuranceModalTitle');
    const insSaveBtn         = document.getElementById('insuranceFormSaveBtn');
    const insIdInput         = document.getElementById('insuranceFormInsuranceId');
    const insPatientIdInput  = document.getElementById('insuranceFormPatientId');

    function clearInsuranceForm() {
        insuranceForm.reset();
        insIdInput.value = '';
        insPatientIdInput.value = '';
        tierSelect.innerHTML = '<option value="">' + visitI18n.tier_default + '</option>';
        insuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        insuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
        insuranceFormFb.classList.add('d-none');
        insuranceFormFb.innerHTML = '';
    }

    function repopulateTiers(providerOption) {
        tierSelect.innerHTML = '<option value="">' + visitI18n.tier_default + '</option>';
        if (!providerOption) return;
        let tiers = [];
        try { tiers = JSON.parse(providerOption.dataset.tiers || '[]'); } catch (e) {}
        tiers.forEach(function(t) {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            tierSelect.appendChild(opt);
        });
    }

    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            repopulateTiers(opt);
        });
    }

    function openInsuranceModal(mode, insuranceId) {
        if (!insuranceModal) return;
        const patientId = patientIdInput.value;
        if (!patientId) {
            alert(visitI18n.select_patient_first);
            return;
        }
        clearInsuranceForm();
        insPatientIdInput.value = patientId;
        insModalTitle.textContent = (mode === 'edit') ? visitI18n.edit_btn + ' / ' + visitI18n.renew_btn + ' Insurance' : visitI18n.add_insurance_title;

        if (mode === 'edit' && insuranceId) {
            const ins = patientInsurances.find(i => i.id === insuranceId);
            if (ins) {
                insIdInput.value = ins.id;
                providerSelect.value = ins.provider_id;
                repopulateTiers(providerSelect.options[providerSelect.selectedIndex]);
                if (ins.tier_id) tierSelect.value = ins.tier_id;
                insuranceForm.elements['membership_number'].value = ins.membership_number || '';
                insuranceForm.elements['policy_number'].value = ins.policy_number || '';
                insuranceForm.elements['member_type'].value = ins.member_type || 'holder';
                insuranceForm.elements['expiry_date'].value = ins.expiry_date || '';
                insuranceForm.elements['is_primary'].checked = !!ins.is_primary;
            }
        }

        insuranceModal.show();
    }

    const addInsBtn = document.getElementById('addInsuranceBtn');
    if (addInsBtn) {
        addInsBtn.addEventListener('click', openSharedAddInsuranceModal);
    }

    function showInsuranceFb(type, text) {
        insuranceFormFb.className = 'alert alert-' + type;
        insuranceFormFb.innerHTML = text;
        insuranceFormFb.classList.remove('d-none');
    }

    function applyInsuranceErrors(errors) {
        Object.entries(errors || {}).forEach(([field, msgs]) => {
            const el = insuranceForm.querySelector('[name="' + field + '"]');
            if (!el) return;
            el.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block dynamic-invalid-feedback';
            fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            el.parentNode.insertBefore(fb, el.nextSibling);
        });
    }

    if (insuranceForm) {
        insuranceForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            insuranceFormFb.classList.add('d-none');
            insuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            insuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());

            const patientId = insPatientIdInput.value;
            const insId     = insIdInput.value;
            const isEdit    = !!insId;

            const baseUrl = '{{ url("/admin/patients") }}/' + patientId + '/insurances' + (isEdit ? ('/' + insId) : '');
            const fd = new FormData(insuranceForm);
            // Strip our private form-only fields and force HTTP method.
            fd.delete('_patient_id');
            fd.delete('_insurance_id');
            if (isEdit) fd.append('_method', 'PUT');

            insSaveBtn.disabled = true;
            const originalLabel = insSaveBtn.innerHTML;
            insSaveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + visitI18n.saving + '';

            try {
                const resp = await fetch(baseUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                const ct = resp.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await resp.json() : {};

                if (resp.ok) {
                    showInsuranceFb('success', data.message || visitI18n.insurance_saved);
                    // Reload insurance list and auto-select the new/edited one
                    await reloadInsuranceListAndSelect(data.insurance_id || (isEdit ? parseInt(insId) : null));
                    setTimeout(() => insuranceModal.hide(), 600);
                    return;
                }

                if (resp.status === 422 && data.errors) {
                    applyInsuranceErrors(data.errors);
                    showInsuranceFb('danger', data.message || visitI18n.correct_fields_short);
                    return;
                }

                showInsuranceFb('danger', data.message || visitI18n.failed_save_insurance);
            } catch (err) {
                showInsuranceFb('danger', visitI18n.network_error_insurance);
            } finally {
                insSaveBtn.disabled = false;
                insSaveBtn.innerHTML = originalLabel;
            }
        });
    }

    function reloadInsuranceListAndSelect(targetInsuranceId) {
        return new Promise(function(resolve) {
            const patientId = patientIdInput.value;
            if (!patientId) { resolve(); return; }
            fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                patientInsurances = data.insurances || [];
                // Re-render list using the same rendering routine
                loadPatientInsurances(patientId);
                if (targetInsuranceId) {
                    setTimeout(() => selectInsurance(targetInsuranceId), 200);
                }
                resolve();
            })
            .catch(() => resolve());
        });
    }
});
</script>
@endpush
