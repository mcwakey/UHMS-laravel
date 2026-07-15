@extends('layouts.app')
@section('title', __('patients.merge_patients'))

@section('content')
<x-page-header-back
    :title="__('patients.merge_patients')"
    :href="$workspaceRoutes->route('admin.patients.index')"
>
    <x-slot:actions>
        <a href="{{ $workspaceRoutes->route('admin.patients.merge.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-history me-1"></i>{{ __('patients.audit_logs') }}
        </a>
    </x-slot:actions>
</x-page-header-back>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card mb-3">
    <form method="GET" action="{{ $workspaceRoutes->route('admin.patients.merge.compare') }}" id="mergeCompareForm">
        <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">{{ __('patients.selected_folders') }}</h5>
            <button class="btn btn-primary" type="submit" id="compareBtn" disabled>
                <i class="ti ti-git-compare me-1"></i>{{ __('patients.preview') }}
            </button>
        </div>
        <div class="card-body">
            <input type="hidden" name="main_patient_number" id="mainPatientNumberField">
            <input type="hidden" name="duplicate_patient_number" id="duplicatePatientNumberField">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-patient-selection-card
                        title="{{ __('patients.main_patient_number') }}"
                        search-label="{{ __('patients.patient_search') }}"
                        search-placeholder="{{ __('patients.merge_search_ph') }}"
                        :show-active-admission-warning="false"
                        patient-field-name="main_patient_id"
                        patient-search-id="mainPatientSearchInput"
                        patient-id-id="mainPatientId"
                        patient-info-id="mainPatientInfo"
                        deceased-warning-id="mainPatientDeceasedWarning"
                        patient-initial-id="mainPatientInitial"
                        patient-name-id="mainPatientName"
                        patient-number-id="mainPatientNumber"
                        patient-phone-id="mainPatientPhone"
                        patient-last-visit-id="mainPatientLastVisit"
                        clear-patient-handler="clearMergeMain()"
                        :required="false"
                        class="border-success h-100 mb-0"
                    />
                </div>
                <div class="col-lg-6">
                    <x-patient-selection-card
                        title="{{ __('patients.duplicate_patient_number') }}"
                        search-label="{{ __('patients.patient_search') }}"
                        search-placeholder="{{ __('patients.merge_search_ph') }}"
                        :show-active-admission-warning="false"
                        patient-field-name="duplicate_patient_id"
                        patient-search-id="duplicatePatientSearchInput"
                        patient-id-id="duplicatePatientId"
                        patient-info-id="duplicatePatientInfo"
                        deceased-warning-id="duplicatePatientDeceasedWarning"
                        patient-initial-id="duplicatePatientInitial"
                        patient-name-id="duplicatePatientName"
                        patient-number-id="duplicatePatientNumber"
                        patient-phone-id="duplicatePatientPhone"
                        patient-last-visit-id="duplicatePatientLastVisit"
                        clear-patient-handler="clearMergeDuplicate()"
                        :required="false"
                        class="border-warning h-100 mb-0"
                    />
                </div>
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
                        <td class="text-end"><a href="{{ $workspaceRoutes->route('admin.patients.merge.requests.show', $mergeRequest) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
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
    const compareBtn = document.getElementById('compareBtn');

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

    function initPatientSearchSelect2(config) {
        if (!hasSelect2()) return;

        const searchInput = document.getElementById(config.searchId);
        const $patient = jQuery(searchInput);
        if ($patient.hasClass('select2-hidden-accessible')) return;

        $patient.select2({
            placeholder: searchInput.dataset.placeholder,
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '{{ $workspaceRoutes->route("admin.patients.merge.search") }}',
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
                html += '<small class="text-muted d-block">' + meta.map(escapeHtml).join(' &bull; ') + '</small>';

                return jQuery('<span>').html(html);
            },
            templateSelection: function (patient) {
                return patientDisplayText(patient);
            },
        }).on('select2:select', function (event) {
            selectMergePatient(config.role, event.params.data);
        }).on('select2:clear', function () {
            clearMergePatient(config.role, { keepSelect: true });
        });
    }

    function rolePrefix(role) {
        return role === 'main' ? 'mainPatient' : 'duplicatePatient';
    }

    function renderSelectedPatient(role, patient) {
        const prefix = rolePrefix(role);
        const numberField = document.getElementById(role + 'PatientNumberField');
        const idInput = document.getElementById(prefix + 'Id');
        const info = document.getElementById(prefix + 'Info');

        if (!patient) {
            idInput.value = '';
            numberField.value = '';
            info.classList.add('d-none');
            return;
        }

        idInput.value = patient.id;
        numberField.value = patient.patient_number || '';
        document.getElementById(prefix + 'Initial').textContent = (patient.full_name || '').charAt(0).toUpperCase();
        document.getElementById(prefix + 'Name').textContent = patient.full_name || '';
        document.getElementById(prefix + 'Number').textContent = patient.patient_number || '';
        document.getElementById(prefix + 'Phone').textContent = patient.phone || mergeI18n.no_phone;
        document.getElementById(prefix + 'LastVisit').classList.add('d-none');
        document.getElementById(prefix + 'DeceasedWarning').classList.toggle('d-none', !patient.is_deceased);
        info.classList.remove('d-none');
    }

    function updateCompareBtn() {
        compareBtn.disabled = !(mainPatient && duplicatePatient && mainPatient.id !== duplicatePatient.id);
    }

    function selectMergePatient(role, patient) {
        if (role === 'main') {
            mainPatient = patient;
            renderSelectedPatient('main', mainPatient);
        } else {
            duplicatePatient = patient;
            renderSelectedPatient('duplicate', duplicatePatient);
        }

        updateCompareBtn();
    }

    function clearMergePatient(role, options) {
        options = options || {};
        const prefix = rolePrefix(role);
        const searchInput = document.getElementById(prefix + 'SearchInput');

        if (role === 'main') {
            mainPatient = null;
        } else {
            duplicatePatient = null;
        }

        renderSelectedPatient(role, null);
        if (!options.keepSelect) {
            if (hasSelect2()) {
                jQuery(searchInput).val(null).trigger('change.select2');
            } else {
                searchInput.value = '';
            }
        }
        updateCompareBtn();
    }

    window.clearMergeMain = function (options) {
        clearMergePatient('main', options);
    };

    window.clearMergeDuplicate = function (options) {
        clearMergePatient('duplicate', options);
    };

    initPatientSearchSelect2({ role: 'main', searchId: 'mainPatientSearchInput' });
    initPatientSearchSelect2({ role: 'duplicate', searchId: 'duplicatePatientSearchInput' });

    document.getElementById('mergeCompareForm').addEventListener('submit', function (event) {
        if (!mainPatient || !duplicatePatient || mainPatient.id === duplicatePatient.id) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
