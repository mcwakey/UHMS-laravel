@extends('layouts.app')
@section('title', __('triage.edit_triage') . ' — ' . $visit->patient->full_name)

@section('content')
<x-page-header-back
        :title="__('triage.edit_triage')"
        :href="$workspaceRoutes->route('admin.triage.show', $visit)"
    />

<!-- Page Header -->
<!-- <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2 text-info"></i>{{ __('triage.edit_triage') }}</h4>
        <small class="text-muted">{{ $visit->patient->full_name }} &bull; {{ $visit->visit_number }}</small>
    </div>
    <a href="{{ $workspaceRoutes->route('admin.triage.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>{{ __('triage.back_to_visit') }}
    </a>
</div> -->

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div id="triageFormFeedback" class="alert d-none" role="alert"></div>

<div class="row">
    <!-- Left: Triage Form -->
    <div class="col-lg-8">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.triage.update', $visit) }}" id="triageForm">
            @csrf
            @method('PUT')

            <!-- Vitals Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="ti ti-heart-rate-monitor me-1"></i>{{ __('triage.vital_signs') }}</h6>
                </div>
                <div class="card-body">
                    <!-- Blood Pressure -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('triage.blood_pressure_mmhg') }}</label>
                            <div class="input-group">
                                <input type="number" name="blood_pressure_systolic" class="form-control @error('blood_pressure_systolic') is-invalid @enderror"
                                       placeholder="{{ __('triage.systolic_placeholder') }}" min="40" max="300"
                                       value="{{ old('blood_pressure_systolic', $visit->triage?->blood_pressure_systolic) }}"
                                       id="inp_sbp">
                                <span class="input-group-text">/</span>
                                <input type="number" name="blood_pressure_diastolic" class="form-control @error('blood_pressure_diastolic') is-invalid @enderror"
                                       placeholder="{{ __('triage.diastolic_placeholder') }}" min="20" max="200"
                                       value="{{ old('blood_pressure_diastolic', $visit->triage?->blood_pressure_diastolic) }}"
                                       id="inp_dbp">
                                <span class="input-group-text">mmHg</span>
                            </div>
                            @error('blood_pressure_systolic')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('triage.heart_rate_pulse') }}</label>
                            <div class="input-group">
                                <input type="number" name="heart_rate" class="form-control @error('heart_rate') is-invalid @enderror"
                                       placeholder="bpm" min="20" max="300"
                                       value="{{ old('heart_rate', $visit->triage?->heart_rate) }}"
                                       id="inp_hr">
                                <span class="input-group-text">bpm</span>
                            </div>
                            @error('heart_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.temperature') }}</label>
                            <div class="input-group">
                                <input type="number" name="temperature" class="form-control @error('temperature') is-invalid @enderror"
                                       placeholder="e.g. 37.0" min="30" max="45" step="0.1"
                                       value="{{ old('temperature', $visit->triage?->temperature) }}"
                                       id="inp_temp">
                                <span class="input-group-text">°C</span>
                            </div>
                            @error('temperature')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.respiratory_rate') }}</label>
                            <div class="input-group">
                                <input type="number" name="respiratory_rate" class="form-control @error('respiratory_rate') is-invalid @enderror"
                                       placeholder="breaths/min" min="4" max="60"
                                       value="{{ old('respiratory_rate', $visit->triage?->respiratory_rate) }}"
                                       id="inp_rr">
                                <span class="input-group-text">/min</span>
                            </div>
                            @error('respiratory_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.spo2_label') }}</label>
                            <div class="input-group">
                                <input type="number" name="spo2" class="form-control @error('spo2') is-invalid @enderror"
                                       placeholder="%" min="50" max="100"
                                       value="{{ old('spo2', $visit->triage?->spo2) }}"
                                       id="inp_spo2">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('spo2')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.weight') }} <span class="text-muted">({{ __('common.optional') }})</span></label>
                            <div class="input-group">
                                <input type="number" name="weight" class="form-control"
                                       placeholder="kg" min="0.5" max="500" step="0.1"
                                       value="{{ old('weight', $visit->triage?->weight) }}"
                                       id="inp_weight">
                                <span class="input-group-text">kg</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.height') }} <span class="text-muted">({{ __('common.optional') }})</span></label>
                            <div class="input-group">
                                <input type="number" name="height" class="form-control"
                                       placeholder="cm" min="20" max="250" step="0.1"
                                       value="{{ old('height', $visit->triage?->height) }}"
                                       id="inp_height">
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('triage.bmi') }} <span class="text-muted">({{ __('triage.bmi_auto_hint') }})</span></label>
                            <div class="input-group">
                                <input type="text" id="bmi_display" class="form-control" readonly placeholder="—">
                                <span class="input-group-text">kg/m²</span>
                            </div>
                        </div>
                    </div>

                    <!-- Live Triage Score Preview -->
                    <div id="triageScorePreview" class="alert alert-secondary d-none">
                        <strong>{{ __('triage.estimated_score') }}</strong>
                        <span id="triageScoreLabel" class="badge ms-2"></span>
                        <span id="triageScoreReason" class="text-muted ms-2 small"></span>
                    </div>
                </div>
            </div>

            <!-- Outcome Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('triage.triage_notes') }}</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="{{ __('triage.notes_placeholder') }}">{{ old('notes', $visit->triage?->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4" id="triageSubmitBtn">
                    <i class="ti ti-device-floppy me-1"></i><span id="triageSubmitLabel">{{ __('triage.update_triage') }}</span>
                </button>
                <a href="{{ $workspaceRoutes->route('admin.triage.show', $visit) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>

    <!-- Right: Patient Card -->
    <div class="col-lg-4">

        <x-patient-card :patient="$visit->patient" :visit="$visit" />

        <x-visit-information-card :visit="$visit" />

        <!-- <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>{{ __('triage.patient_card') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.2rem;font-weight:700;color:var(--bs-primary)">
                        {{ strtoupper(substr($visit->patient->first_name, 0, 1)) }}{{ strtoupper(substr($visit->patient->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold">{{ $visit->patient->full_name }}</div>
                        <div class="text-muted small">{{ $visit->patient->patient_number }}</div>
                    </div>
                </div>
                <div class="row g-2 text-sm">
                    <div class="col-6">
                        <div class="text-muted small">{{ __('common.age') }}</div>
                        <div>{{ $visit->patient->age ?? '—' }} yrs</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('common.gender') }}</div>
                        <div>{{ $visit->patient->gender?->label() ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('common.blood_group') }}</div>
                        <div>{{ $visit->patient->blood_group?->label() ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">{{ __('triage.priority') }}</div>
                        <div><x-status-badge :status="$visit->priority" /></div>
                    </div>
                </div>
            </div>
        </div> -->

        @if ($visit->chief_complaint)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('triage.chief_complaint') }}</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $visit->chief_complaint ?? '—' }}</p>
            </div>
        </div>
        @endif

        @if ($visit->notes)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('triage.notes') }}</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $visit->notes ?? '—' }}</p>
            </div>
        </div>
        @endif

        <!-- Triage Score Guide -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('triage.score_guide') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('triage.spo2') }}</th>
                            <th>{{ __('triage.temperature') }}</th>
                            <th>{{ __('triage.heart_rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-success">{{ __('triage.score_routine') }}</span></td>
                            <td>≥95%</td>
                            <td>36–38.5°C</td>
                            <td>50–110</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark">{{ __('triage.urgent') }}</span></td>
                            <td>92–94%</td>
                            <td>36–38.5°C</td>
                            <td>50–110</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">{{ __('triage.emergency') }}</span></td>
                            <td>&lt;92%</td>
                            <td>&lt;35 / &gt;39.5°C</td>
                            <td>&lt;40 / &gt;130</td>
                        </tr>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Blade-rendered translatable strings used in JS
    const i18n = {
        completeTriage:         @json(__('triage.update_triage')),
        saving:                 @json(__('triage.saving')),
        completed:              @json(__('triage.triage_completed_label')),
        correctFields:          @json(__('common.something_went_wrong')),
        completedSuccessfully:  @json(__('triage.js_updated_successfully')),
        triageScoreLabel:       @json(__('triage.js_triage_score_label')),
        departmentLabel:        @json(__('triage.js_department_label')),
        viewVisit:              @json(__('triage.js_view_visit')),
        openConsultationQueue:  @json(__('triage.js_open_consultation_queue')),
        openVisit:              @json(__('triage.js_open_visit')),
        failedToComplete:       @json(__('triage.js_failed_to_update')),
        networkError:           @json(__('triage.js_network_error')),
    };

    const triageForm = document.getElementById('triageForm');
    const triageSubmitBtn = document.getElementById('triageSubmitBtn');
    const triageSubmitLabel = document.getElementById('triageSubmitLabel');
    const triageFormFeedback = document.getElementById('triageFormFeedback');

    // Live BMI calculation
    const wt = document.getElementById('inp_weight');
    const ht = document.getElementById('inp_height');
    const bmiEl = document.getElementById('bmi_display');

    function calcBmi() {
        const w = parseFloat(wt.value);
        const h = parseFloat(ht.value) / 100;
        if (w > 0 && h > 0) {
            bmiEl.value = (w / (h * h)).toFixed(1);
        } else {
            bmiEl.value = '';
        }
    }

    wt.addEventListener('input', calcBmi);
    ht.addEventListener('input', calcBmi);

    // Live triage score preview
    const inputs = ['inp_sbp', 'inp_hr', 'inp_temp', 'inp_rr', 'inp_spo2'];
    const preview = document.getElementById('triageScorePreview');
    const label = document.getElementById('triageScoreLabel');
    const reason = document.getElementById('triageScoreReason');

    const EMERGENCY = {
        color: 'bg-danger', label: 'EMERGENCY',
        reason: 'Critical vitals detected — immediate intervention required'
    };
    const URGENT = {
        color: 'bg-warning text-dark', label: 'URGENT',
        reason: 'Abnormal vitals — needs prompt attention'
    };
    const ROUTINE = {
        color: 'bg-success', label: 'ROUTINE',
        reason: 'Vitals within acceptable range'
    };

    function showFormFeedback(type, html) {
        triageFormFeedback.className = 'alert alert-' + type;
        triageFormFeedback.innerHTML = html;
        triageFormFeedback.classList.remove('d-none');
        window.scrollTo({ top: triageFormFeedback.offsetTop - 100, behavior: 'smooth' });
    }

    function clearFormFeedback() {
        triageFormFeedback.className = 'alert d-none';
        triageFormFeedback.innerHTML = '';
    }

    function clearValidationErrors() {
        triageForm.querySelectorAll('.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        triageForm.querySelectorAll('.dynamic-invalid-feedback').forEach(function(element) {
            element.remove();
        });
    }

    function resolveFieldElement(field) {
        const mappedIds = {
            blood_pressure_systolic: 'inp_sbp',
            blood_pressure_diastolic: 'inp_dbp',
            heart_rate: 'inp_hr',
            temperature: 'inp_temp',
            respiratory_rate: 'inp_rr',
            spo2: 'inp_spo2',
            weight: 'inp_weight',
            height: 'inp_height',
        };

        if (mappedIds[field]) {
            return document.getElementById(mappedIds[field]);
        }

        return triageForm.querySelector('[name="' + field + '"]');
    }

    function appendFieldError(field, message) {
        const fieldElement = resolveFieldElement(field);

        if (!fieldElement) {
            return false;
        }

        fieldElement.classList.add('is-invalid');

        const anchor = fieldElement.closest('.input-group') || fieldElement;
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
                return '<div>' + message + '</div>';
            }).join(''));
            return;
        }

        showFormFeedback('danger', i18n.correctFields);
    }

    function setSubmitting(isSubmitting) {
        triageSubmitBtn.disabled = isSubmitting;
        triageSubmitLabel.textContent = isSubmitting ? i18n.saving : i18n.completeTriage;
    }

    function lockFormAfterSuccess() {
        triageForm.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(function(element) {
            element.disabled = true;
        });

        triageSubmitLabel.textContent = i18n.completed;
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }

        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function computeScore() {
        const sbp  = parseInt(document.getElementById('inp_sbp')?.value);
        const hr   = parseInt(document.getElementById('inp_hr')?.value);
        const temp = parseFloat(document.getElementById('inp_temp')?.value);
        const rr   = parseInt(document.getElementById('inp_rr')?.value);
        const spo2 = parseInt(document.getElementById('inp_spo2')?.value);

        const hasAny = [sbp, hr, temp, rr, spo2].some(v => !isNaN(v));
        if (!hasAny) { preview.classList.add('d-none'); return; }

        let score = ROUTINE;

        // Emergency thresholds
        if ((!isNaN(spo2) && spo2 < 92) ||
            (!isNaN(temp) && (temp < 35.0 || temp > 39.5)) ||
            (!isNaN(hr)   && (hr < 40 || hr > 130)) ||
            (!isNaN(rr)   && (rr < 8  || rr > 25)) ||
            (!isNaN(sbp)  && (sbp < 80 || sbp > 200))) {
            score = EMERGENCY;
        // Urgent thresholds
        } else if ((!isNaN(spo2) && spo2 < 95) ||
                   (!isNaN(temp) && (temp < 36.0 || temp > 38.5)) ||
                   (!isNaN(hr)   && (hr < 50 || hr > 110)) ||
                   (!isNaN(rr)   && (rr < 12 || rr > 20)) ||
                   (!isNaN(sbp)  && (sbp < 90 || sbp > 180))) {
            score = URGENT;
        }

        preview.classList.remove('d-none', 'alert-secondary', 'alert-danger', 'alert-warning', 'alert-success');
        const alertMap = { 'bg-danger': 'alert-danger', 'bg-warning text-dark': 'alert-warning', 'bg-success': 'alert-success' };
        preview.classList.add(alertMap[score.color] ?? 'alert-secondary');
        label.className = 'badge ms-2 ' + score.color;
        label.textContent = score.label;
        reason.textContent = score.reason;
    }

    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', computeScore);
    });

    triageForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        clearFormFeedback();
        clearValidationErrors();
        setSubmitting(true);

        try {
            const response = await fetch(triageForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(triageForm),
            });

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            const payload = isJson ? await response.json() : {};

            if (response.ok) {
                lockFormAfterSuccess();
                showFormFeedback(
                    'success',
                    '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">'
                        + '<div>'
                        + '<div class="fw-bold">' + escapeHtml(payload.message || i18n.completedSuccessfully) + '</div>'
                        + '<div class="small text-muted">'
                        + i18n.triageScoreLabel + ' ' + escapeHtml(payload.triage_score_label || '')
                        + (payload.department ? ' | ' + i18n.departmentLabel + ' ' + escapeHtml(payload.department) : '')
                        + '</div>'
                        + '</div>'
                        + '<div class="d-flex gap-2">'
                        + '<a href="' + escapeHtml(payload.redirect_url || '#') + '" class="btn btn-sm btn-success">' + i18n.viewVisit + '</a>'
                        + '<a href="' + escapeHtml(payload.queue_url || '#') + '" class="btn btn-sm btn-outline-success">' + i18n.openConsultationQueue + '</a>'
                        + '</div>'
                        + '</div>'
                );
                return;
            }

            if (response.status === 422 && payload.errors) {
                applyValidationErrors(payload.errors);
                return;
            }

            const message = payload.message || i18n.failedToComplete;
            const link = payload.redirect_url
                ? ' <a href="' + escapeHtml(payload.redirect_url) + '" class="alert-link">' + i18n.openVisit + '</a>'
                : '';
            showFormFeedback('danger', escapeHtml(message) + link);
        } catch (error) {
            showFormFeedback('danger', i18n.networkError);
        } finally {
            if (!triageSubmitBtn.disabled) {
                setSubmitting(false);
            }
        }
    });

    calcBmi();
    computeScore();
})();
</script>
@endpush
