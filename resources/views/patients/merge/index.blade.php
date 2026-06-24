@extends('layouts.app')
@section('title', __('patients.merge_patients'))

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between">
<x-page-header-back
    :title="__('patients.merge_patients')"
    :href="route('admin.patients.index')"
/>
    <a href="{{ route('admin.patients.merge.logs') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-history me-1"></i>{{ __('patients.audit_logs') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<!-- <p class="text-muted">{{ __('patients.merge_subtitle') }}</p> -->
<div class="row">
        <!-- Left Column - Patient, Insurance, Visit Details -->
        <div class="col-lg-8">
<x-patient-selection-card
    title="{{ __('patients.patient_search') }}"
    search-label="{{ __('patients.patient_search') }}"
    search-placeholder="{{ __('patients.merge_search_ph') }}"
    :show-active-admission-warning="false"
    patient-field-name="merge_search_patient_id"
    patient-search-id="mergeSearchInput"
    patient-id-id="mergeSearchPatientId"
    patient-info-id="mergeSearchPatientInfo"
    deceased-warning-id="mergeSearchDeceasedWarning"
    patient-initial-id="mergeSearchInitial"
    patient-name-id="mergeSearchName"
    patient-number-id="mergeSearchNumber"
    patient-phone-id="mergeSearchPhone"
    patient-last-visit-id="mergeSearchLastVisit"
    clear-patient-handler="clearMergeSearch()"
    :required="false"
/>
</div>
<div class="col-lg-3">
    <div class="card">
        <div class="card-body d-grid gap-2">
            <button type="button" id="assignAsMainBtn" class="btn btn-outline-success w-100 mb-3">
                <i class="ti ti-check me-1"></i>{{ __('patients.use_as_main') }}
            </button>
            <button type="button" id="assignAsDuplicateBtn" class="btn btn-outline-warning w-100">
                <i class="ti ti-copy me-1"></i>{{ __('patients.use_as_duplicate') }}
            </button>
        </div>
    </div>
</div>
</div>

<div class="card mb-3">
        <form method="GET" action="{{ route('admin.patients.merge.compare') }}" id="mergeCompareForm">

    <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0">{{ __('patients.selected_folders') }}</h5>
        <button class="btn btn-primary" type="submit" id="compareBtn" disabled><i class="ti ti-git-compare me-1"></i>{{ __('patients.preview') }}</button>
    </div>
    <div class="card-body">
            <input type="hidden" name="main_patient_number" id="mainPatientNumberField">
            <input type="hidden" name="duplicate_patient_number" id="duplicatePatientNumberField">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-success"><i class="ti ti-check me-1"></i>{{ __('patients.main_patient_number') }}</label>
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between text-muted small" id="mainSlotEmpty">
                        {{ __('patients.not_selected_yet') }}
                    </div>
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-2 d-none" id="mainSlotFilled">
                        <div class="flex-grow-1 text-truncate">
                            <div class="fw-semibold text-truncate" id="mainSlotName"></div>
                            <small class="text-muted text-truncate d-block" id="mainSlotMeta"></small>
                        </div>
                        <button aria-label="{{ __('common.clear') }}" title="{{ __('common.clear') }}" type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" id="mainSlotClear"><i class="ti ti-x"></i></button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-warning"><i class="ti ti-copy me-1"></i>{{ __('patients.duplicate_patient_number') }}</label>
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between text-muted small" id="duplicateSlotEmpty">
                        {{ __('patients.not_selected_yet') }}
                    </div>
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-2 d-none" id="duplicateSlotFilled">
                        <div class="flex-grow-1 text-truncate">
                            <div class="fw-semibold text-truncate" id="duplicateSlotName"></div>
                            <small class="text-muted text-truncate d-block" id="duplicateSlotMeta"></small>
                        </div>
                        <button aria-label="{{ __('common.clear') }}" title="{{ __('common.clear') }}" type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" id="duplicateSlotClear"><i class="ti ti-x"></i></button>
                    </div>
                </div>
                <!-- <div class="col-md-2 d-flex align-items-end">
                </div> -->
            </div>
    </div>
        </form>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('patients.recent_merge_requests') }}</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>{{ __('patients.col_request') }}</th>
                    <th>{{ __('patients.col_main_folder') }}</th>
                    <th>{{ __('patients.col_duplicate_folder') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('patients.col_requested') }}</th>
                    <th class="text-end">{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRequests as $mergeRequest)
                    <tr>
                        <td>{{ $mergeRequest->request_number }}</td>
                        <td>{{ $mergeRequest->mainPatient?->patient_number }}<br><small class="text-muted">{{ $mergeRequest->mainPatient?->full_name }}</small></td>
                        <td>{{ $mergeRequest->duplicatePatient?->patient_number }}<br><small class="text-muted">{{ $mergeRequest->duplicatePatient?->full_name }}</small></td>
                        <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $mergeRequest->status) }}</span></td>
                        <td>{{ $mergeRequest->created_at?->format('d M Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('admin.patients.merge.requests.show', $mergeRequest) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state message="{{ __('patients.no_merge_requests') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
@php
    $mergeI18n = [
        'no_phone' => __('patients.no_phone'),
        'deceased' => __('patients.deceased'),
        'merged' => __('patients.merged'),
    ];
@endphp
<script>const mergeI18n = @json($mergeI18n);</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('mergeSearchInput');
    const searchPatientIdInput = document.getElementById('mergeSearchPatientId');
    const searchPatientInfo = document.getElementById('mergeSearchPatientInfo');
    const assignActions = document.getElementById('mergeAssignActions');
    const compareBtn = document.getElementById('compareBtn');

    let currentPatient = null;
    let mainPatient = null;
    let duplicatePatient = null;

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function patientDisplayText(patient) {
        if (!patient) return '';
        return [patient.patient_number, patient.full_name].filter(Boolean).join(' - ') || patient.text || '';
    }

    function initPatientSearchSelect2() {
        if (!hasSelect2()) return;

        const $patient = jQuery(searchInput);
        if ($patient.hasClass('select2-hidden-accessible')) return;

        $patient.select2({
            placeholder: searchInput.dataset.placeholder,
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '{{ route("admin.patients.merge.search") }}',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: (data || []).map(function (patient) {
                            patient.id = String(patient.id);
                            patient.text = patientDisplayText(patient);
                            return patient;
                        }),
                    };
                },
                cache: true,
            },
            templateResult: function (patient) {
                if (patient.loading) return patient.text;

                const meta = [patient.patient_number || '', patient.phone || mergeI18n.no_phone].filter(Boolean);
                let html = '<span class="fw-medium">' + escapeHtml(patient.full_name || patient.text || '') + '</span>';
                if (patient.is_merged) {
                    html += ' <span class="badge bg-dark ms-1">' + mergeI18n.merged + '</span>';
                }
                if (patient.is_deceased) {
                    html += ' <span class="badge bg-danger ms-1">' + mergeI18n.deceased + '</span>';
                }
                html += '<small class="text-muted d-block">' + meta.map(escapeHtml).join(' • ') + '</small>';

                return jQuery('<span>').html(html);
            },
            templateSelection: function (patient) {
                return patientDisplayText(patient);
            },
        }).on('select2:select', function (event) {
            selectMergeSearchPatient(event.params.data);
        }).on('select2:clear', function () {
            clearMergeSearch({ keepSelect: true });
        });
    }
    initPatientSearchSelect2();

    function selectMergeSearchPatient(patient) {
        currentPatient = patient;
        searchPatientIdInput.value = patient.id;

        document.getElementById('mergeSearchInitial').textContent = (patient.full_name || '').charAt(0).toUpperCase();
        document.getElementById('mergeSearchName').textContent = patient.full_name;
        document.getElementById('mergeSearchNumber').textContent = patient.patient_number;
        document.getElementById('mergeSearchPhone').textContent = patient.phone || mergeI18n.no_phone;
        document.getElementById('mergeSearchLastVisit').classList.add('d-none');

        document.getElementById('mergeSearchDeceasedWarning').classList.toggle('d-none', !patient.is_deceased);

        searchPatientInfo.classList.remove('d-none');
        assignActions.classList.remove('d-none');
    }

    window.clearMergeSearch = function (options) {
        options = options || {};
        currentPatient = null;
        searchPatientIdInput.value = '';
        if (!options.keepSelect) {
            searchInput.value = '';
            if (hasSelect2()) {
                jQuery(searchInput).val(null).trigger('change.select2');
            }
        }
        searchPatientInfo.classList.add('d-none');
        assignActions.classList.add('d-none');
    };

    function renderSlot(role, patient) {
        const emptyEl = document.getElementById(role + 'SlotEmpty');
        const filledEl = document.getElementById(role + 'SlotFilled');
        const field = document.getElementById(role + 'PatientNumberField');

        if (!patient) {
            emptyEl.classList.remove('d-none');
            filledEl.classList.add('d-none');
            field.value = '';
            return;
        }

        emptyEl.classList.add('d-none');
        filledEl.classList.remove('d-none');
        document.getElementById(role + 'SlotName').textContent = patient.full_name;
        document.getElementById(role + 'SlotMeta').textContent = [patient.patient_number, patient.phone || mergeI18n.no_phone].filter(Boolean).join(' • ');
        field.value = patient.patient_number;
    }

    function updateCompareBtn() {
        compareBtn.disabled = !(mainPatient && duplicatePatient && mainPatient.id !== duplicatePatient.id);
    }

    document.getElementById('assignAsMainBtn').addEventListener('click', function () {
        if (!currentPatient) return;
        if (duplicatePatient && duplicatePatient.id === currentPatient.id) {
            duplicatePatient = null;
            renderSlot('duplicate', null);
        }
        mainPatient = currentPatient;
        renderSlot('main', mainPatient);
        updateCompareBtn();
        clearMergeSearch();
    });

    document.getElementById('assignAsDuplicateBtn').addEventListener('click', function () {
        if (!currentPatient) return;
        if (mainPatient && mainPatient.id === currentPatient.id) {
            mainPatient = null;
            renderSlot('main', null);
        }
        duplicatePatient = currentPatient;
        renderSlot('duplicate', duplicatePatient);
        updateCompareBtn();
        clearMergeSearch();
    });

    document.getElementById('mainSlotClear').addEventListener('click', function () {
        mainPatient = null;
        renderSlot('main', null);
        updateCompareBtn();
    });

    document.getElementById('duplicateSlotClear').addEventListener('click', function () {
        duplicatePatient = null;
        renderSlot('duplicate', null);
        updateCompareBtn();
    });
});
</script>
@endpush
