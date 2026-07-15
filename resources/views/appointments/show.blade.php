@extends('layouts.app')

@section('title', __('appointments.show_title'))

@section('content')
    <x-page-header-back
        :title="__('appointments.title') . ' - ' . $appointment->appointment_number"
        :href="$workspaceRoutes->route('admin.appointments.index')"
    />

<!-- <div class="content"> -->
    <div id="appointmentActionFeedback" class="alert d-none" role="alert"></div>


    @php
        $allStatuses = [
            \App\Enums\AppointmentStatus::SCHEDULED,
            \App\Enums\AppointmentStatus::CONFIRMED,
            \App\Enums\AppointmentStatus::CHECKED_IN,
            \App\Enums\AppointmentStatus::IN_PROGRESS,
            \App\Enums\AppointmentStatus::COMPLETED,
        ];
        $currentStatusIndex = array_search($appointment->status, $allStatuses);
        $isCancelled = $appointment->status === \App\Enums\AppointmentStatus::CANCELLED;
        $isNoShow = $appointment->status === \App\Enums\AppointmentStatus::NO_SHOW;
    @endphp

    <div class="row">
        {{-- Appointment Details --}}
        <div class="col-lg-8">
            {{-- Status Bar --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-2"></i>{{ __('appointments.status_flow') }}</h6>
                        <x-status-badge :status="$appointment->status" class="js-appointment-status-badge fs-14 px-3 py-2" />
                    </div>

                    @if($isCancelled || $isNoShow)
                    <!-- <div class="text-center py-3">
                        <span class="badge bg-{{ $appointment->status->color() }} fs-6 px-4 py-2">
                            <i class="ti ti-{{ $isCancelled ? 'x' : 'user-off' }} me-1"></i>
                            {{ $appointment->status->translatedLabel() }}
                        </span>
                    </div> -->
                    @else
                    <div class="d-flex justify-content-between align-items-center">
                        @foreach($allStatuses as $index => $status)
                        <div class="text-center flex-fill">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $currentStatusIndex !== false && $index <= $currentStatusIndex ? 'bg-' . $status->color() . ' text-white' : 'bg-light text-muted' }}"
                                 style="width: 40px; height: 40px;">
                                @if($currentStatusIndex !== false && $index < $currentStatusIndex)
                                    <i class="ti ti-check"></i>
                                @elseif($currentStatusIndex !== false && $index === $currentStatusIndex)
                                    <i class="ti ti-point-filled"></i>
                                @else
                                    <small>{{ $index + 1 }}</small>
                                @endif
                            </div>
                            <div class="mt-1 small {{ $currentStatusIndex !== false && $index <= $currentStatusIndex ? 'fw-medium' : 'text-muted' }}">
                                {{ $status->translatedLabel() }}
                            </div>
                        </div>
                        @if(!$loop->last)
                        <div class="flex-fill border-top {{ $currentStatusIndex !== false && $index < $currentStatusIndex ? 'border-success' : 'border-light' }}" style="height: 2px; margin-top: -15px;"></div>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Appointment Information --}}
            <x-visit-summary-card :appointment="$appointment">
                <x-slot:actions>
                    @if($appointment->status === \App\Enums\AppointmentStatus::SCHEDULED)
                    <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.transition', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-primary btn-md">
                            <i class="ti ti-check me-1"></i>{{ __('appointments.confirm') }}
                        </button>
                    </form>
                    @endif

                    @if(($attendanceClass ?? null) && ! $appointment->visit)
                        <span class="badge bg-light text-dark border align-self-center" title="{{ __('visit_flow.ui.attendance_label') }}">
                            <i class="ti ti-user-check me-1"></i>{{ __('visit_flow.attendance_class.'.$attendanceClass) }}
                        </span>
                    @endif

                    @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                        @can('appointments.create')
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.check-in', $appointment) }}" class="js-appointment-action-form" data-follow-up="visit" data-requires-insurance-verification="1">
                            @csrf
                            <input type="hidden" name="visit_insurance_id" id="checkInVisitInsuranceId" value="{{ $appointment->visit_insurance_id }}">
                            <input type="hidden" name="insurance_verification_id" id="checkInInsuranceVerificationId" value="">
                            <input type="hidden" name="verification_reference_code" id="checkInVerificationReferenceCode" value="">
                            <button type="submit" class="btn btn-primary btn-md">
                                <i class="ti ti-login me-1"></i>{{ __('appointments.check_in_patient') }}
                            </button>
                        </form>
                        @endcan
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.no-show', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-md">
                                <i class="ti ti-user-off me-1"></i>{{ __('appointments.no_show_action') }}
                            </button>
                        </form>
                    @endif

                    @if($appointment->is_active && $appointment->status !== \App\Enums\AppointmentStatus::CHECKED_IN)
                        @can('appointments.edit')
                        <a href="{{ $workspaceRoutes->route('admin.appointments.edit', $appointment) }}" class="btn btn-outline-primary btn-md">
                            <i class="ti ti-pencil me-1"></i>{{ __('common.reschedule') }}
                        </a>
                        @endcan
                    @endif
                </x-slot:actions>
            </x-visit-summary-card>
        </div>

        {{-- Patient Sidebar --}}
        <div class="col-lg-4">
            <x-patient-card
                :patient="$appointment->patient"
                :visit="$appointment->visit"
                :visit-insurance="$appointment->visitInsurance"
            />
            @if($appointment->visit)
            <x-visit-information-card :visit="$appointment->visit" />
            @endif

            @can('appointments.create')
            <div class="card">
                <div class="card-body">
                    <a href="{{ $workspaceRoutes->route('admin.appointments.create', ['patient_id' => $appointment->patient_id]) }}" class="btn btn-outline-primary w-100">
                        <i class="ti ti-calendar-plus me-1"></i> {{ __('appointments.schedule_another') }}
                    </a>
                </div>
            </div>
            @endcan
            {{-- Selected Services --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('appointments.selected_services') }}</h6>
                </div>
                <div class="card-body p-0">
                    @if($appointment->services->isNotEmpty())
                    <div class="px-3 py-2">
                        <div class="d-flex flex-column gap-2">
                            @foreach($appointment->services as $service)
                            @php
                                $quantity = (int) ($service->pivot->quantity ?? 1);
                                $unitPrice = (float) ($service->pivot->unit_price ?? $service->price);
                                $lineTotal = (float) ($service->pivot->total_price ?? ($unitPrice * $quantity));
                            @endphp
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <span class="small fw-medium">{{ $service->name }}</span>
                                    @if($quantity > 1)
                                        <span class="badge bg-secondary-subtle text-secondary ms-1">×{{ $quantity }}</span>
                                    @endif
                                    @if($service->department)
                                        <span class="badge bg-light text-dark border ms-1 small">{{ $service->department->name }}</span>
                                    @endif
                                </div>
                                <div class="text-end small text-muted text-nowrap">
                                    &#8373;{{ number_format($lineTotal, 2) }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @php
                            $servicesTotal = $appointment->services->sum(function ($service) {
                                $quantity = (int) ($service->pivot->quantity ?? 1);
                                $unitPrice = (float) ($service->pivot->unit_price ?? $service->price);

                                return (float) ($service->pivot->total_price ?? ($unitPrice * $quantity));
                            });
                        @endphp
                        <div class="d-flex justify-content-between border-top mt-2 pt-2 fw-bold">
                            <span>{{ __('appointments.estimated_total') }}</span>
                            <span>&#8373;{{ number_format($servicesTotal, 2) }}</span>
                        </div>
                    </div>
                    @else
                    <div class="px-3 py-3">
                        <p class="text-muted small mb-0 text-center">{{ __('appointments.no_services_selected') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
<!-- </div> -->

@can('appointments.create')
    @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
    <div class="modal fade" id="appointmentCheckInInsuranceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-shield-check me-1"></i>{{ __('appointments.insurance') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <x-insurance-selection-card
                        :hidden="false"
                        :can-add-insurance="false"
                        :title="__('appointments.insurance')"
                        :fallback-label="__('appointments.insurance_fallback_badge')"
                        :loading-label="__('appointments.loading_patient_insurances')"
                        :selected-insurance-id="$appointment->visit_insurance_id"
                        class="border-0 shadow-none mb-0"
                    />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="continueAppointmentCheckInBtn" disabled>
                        <i class="ti ti-login me-1"></i>{{ __('appointments.check_in_patient') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedback = document.getElementById('appointmentActionFeedback');
    const forms = document.querySelectorAll('.js-appointment-action-form');
    const checkInModalEl = document.getElementById('appointmentCheckInInsuranceModal');
    const checkInModal = checkInModalEl ? bootstrap.Modal.getOrCreateInstance(checkInModalEl) : null;
    const continueCheckInBtn = document.getElementById('continueAppointmentCheckInBtn');
    const verifyUrl = @json(route('admin.insurance.verify'));
    const patientInsurancesUrl = @json($workspaceRoutes->route('admin.visits.patient-insurances'));
    const patientId = @json($appointment->patient_id);
    const selectedAppointmentInsuranceId = @json($appointment->visit_insurance_id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let pendingCheckInForm = null;
    let patientInsurances = [];
    let selectedInsurance = null;
    let verificationAccepted = false;
    let insuranceModalLoaded = false;
    const statusColors = {
        scheduled: 'secondary',
        confirmed: 'info',
        checked_in: 'primary',
        in_progress: 'warning',
        completed: 'success',
        no_show: 'dark',
        cancelled: 'danger',
    };

    function showFeedback(type, html) {
        feedback.className = 'alert alert-' + type;
        feedback.innerHTML = html;
        feedback.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(str) {
        return str == null ? '' : String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatNumber(value) {
        return parseFloat(value || 0).toLocaleString('en-GH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function updateStatusBadges(payload) {
        const statusValue = payload.appointment_status;
        const statusLabel = payload.appointment_status_label;
        const statusColor = payload.status_color || statusColors[statusValue] || 'secondary';

        if (!statusLabel) {
            return;
        }

        document.querySelectorAll('.js-appointment-status-badge').forEach(function (badge) {
            badge.classList.remove('bg-secondary', 'bg-info', 'bg-primary', 'bg-warning', 'bg-success', 'bg-dark', 'bg-danger');
            badge.classList.add('bg-' + statusColor);
            badge.textContent = statusLabel;
        });
    }

    function removeMatchingForms(form) {
        document.querySelectorAll('.js-appointment-action-form').forEach(function (candidate) {
            if (candidate.action === form.action) {
                candidate.remove();
            }
        });
    }

    function refreshAppointmentPage(url) {
        const targetUrl = url || window.location.href;

        window.setTimeout(function () {
            if (window.UhmsInertia && typeof window.UhmsInertia.visit === 'function') {
                window.UhmsInertia.visit(targetUrl, { preserveScroll: true });
            }
        }, 350);
    }

    function resetVerificationPanel() {
        verificationAccepted = false;
        if (continueCheckInBtn) continueCheckInBtn.disabled = true;

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
        if (data.provider?.name) meta.push(data.provider.name);
        if (data.provider?.method) meta.push('method: ' + data.provider.method);
        if (data.provider?.channel) meta.push('via ' + data.provider.channel);
        if (data.driver) meta.push('driver: ' + data.driver);
        document.getElementById('verificationProviderMeta').textContent = meta.join(' • ') || '—';

        const parts = [];
        if (data.message) parts.push('<div>' + escapeHtml(data.message) + '</div>');
        if (data.reference_code) {
            parts.push('<div><strong>' + @json(__('visits.reference_label')) + '</strong> <code>' + escapeHtml(data.reference_code) + '</code></div>');
        }
        document.getElementById('verificationFeedback').innerHTML = parts.join('');

        verificationAccepted = !!(data.acceptable && data.verification_id);
        document.getElementById('insuranceVerificationId').value = verificationAccepted ? data.verification_id : '';
        if (continueCheckInBtn) continueCheckInBtn.disabled = !verificationAccepted;

        const codeRow = document.getElementById('verificationCodeRow');
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

    async function runVerification(referenceCode) {
        const insuranceId = document.getElementById('visitInsuranceId')?.value;
        const feedbackEl = document.getElementById('verificationFeedback');
        if (!insuranceId || !feedbackEl) return;

        verificationAccepted = false;
        if (continueCheckInBtn) continueCheckInBtn.disabled = true;
        feedbackEl.innerHTML = '<i class="ti ti-loader me-1"></i>{{ __('appointments.working') }}';

        try {
            const response = await fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    patient_insurance_id: insuranceId,
                    reference_code: referenceCode || null,
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                feedbackEl.innerHTML = '<div class="text-danger">' + escapeHtml(data.message || @json(__('visits.verification_failed'))) + '</div>';
                return;
            }

            renderVerificationStatus(data);
        } catch (error) {
            feedbackEl.innerHTML = '<div class="text-danger">' + @json(__('visits.verification_failed')) + '</div>';
        }
    }

    function selectInsurance(insuranceId) {
        selectedInsurance = patientInsurances.find(function (insurance) {
            return String(insurance.id) === String(insuranceId);
        }) || null;

        document.getElementById('visitInsuranceId').value = insuranceId || '';
        resetVerificationPanel();

        if (!insuranceId) return;

        const panel = document.getElementById('verificationPanel');
        if (panel) panel.classList.remove('d-none');
        runVerification(null);
    }

    async function loadPatientInsurances() {
        const insuranceList = document.getElementById('insuranceList');
        const fallbackBadge = document.getElementById('insuranceFallbackBadge');
        if (!insuranceList) return;

        insuranceList.innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>{{ __('appointments.loading_patient_insurances') }}</div>';

        try {
            const response = await fetch(patientInsurancesUrl + '?patient_id=' + encodeURIComponent(patientId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            patientInsurances = data.insurances || [];
            const defaultId = selectedAppointmentInsuranceId || data.default_insurance_id;
            if (fallbackBadge) fallbackBadge.style.display = data.is_fallback && !selectedAppointmentInsuranceId ? '' : 'none';

            if (patientInsurances.length === 0) {
                insuranceList.innerHTML = '<div class="text-muted text-center py-2">' + @json(__('appointments.no_insurances_cash')) + '</div>';
                return;
            }

            const lockedInsurance = patientInsurances.find(function (insurance) {
                return String(insurance.id) === String(defaultId);
            }) || patientInsurances[0];

            let html = '<div class="list-group">';
            [lockedInsurance].forEach(function (insurance) {
                const badgeClass = insurance.is_valid ? 'bg-success' : (insurance.is_expired ? 'bg-danger' : 'bg-secondary');

                html += '<div class="list-group-item d-flex align-items-center gap-3">';
                html += '<input type="radio" name="_insurance_radio" class="form-check-input" value="' + insurance.id + '" checked disabled>';
                html += '<div class="flex-grow-1">';
                html += '<div class="fw-medium">' + escapeHtml(insurance.provider_name) + ' <span class="badge bg-' + insurance.type_color + ' ms-1">' + escapeHtml(insurance.type_label) + '</span>';
                if (insurance.tier_name) html += ' <span class="badge bg-primary bg-opacity-75 ms-1">' + escapeHtml(insurance.tier_name) + '</span>';
                html += '</div>';
                html += '<small class="text-muted">';
                if (insurance.membership_number) html += @json(__('appointments.member_label')) + ' ' + escapeHtml(insurance.membership_number) + ' &bull; ';
                html += insurance.expiry_date ? @json(__('appointments.expires_label')) + ' ' + escapeHtml(insurance.expiry_date) : @json(__('appointments.no_expiry'));
                html += '</small></div>';
                html += '<div class="text-end"><span class="badge ' + badgeClass + '">' + (insurance.is_valid ? @json(__('appointments.valid_status')) : (insurance.is_expired ? @json(__('appointments.expired_status')) : @json(__('appointments.inactive_status')))) + '</span>';
                if (insurance.coverage_percentage != null) html += '<div class="small text-muted mt-1">' + insurance.coverage_percentage + '% ' + @json(__('appointments.coverage')) + '</div>';
                if (insurance.remaining_annual_limit != null) html += '<div class="small text-muted">₵' + formatNumber(insurance.remaining_annual_limit) + '</div>';
                html += '</div></div>';
            });
            html += '</div>';

            insuranceList.innerHTML = html;
            selectInsurance(lockedInsurance.id);
        } catch (error) {
            insuranceList.innerHTML = '<div class="text-danger text-center py-2">' + @json(__('appointments.failed_load_insurances')) + '</div>';
        }
    }

    function openCheckInInsuranceModal(form) {
        pendingCheckInForm = form;
        if (!checkInModal) return false;

        resetVerificationPanel();
        checkInModal.show();

        if (!insuranceModalLoaded) {
            insuranceModalLoaded = true;
            loadPatientInsurances();
        } else {
            const currentInsuranceId = document.getElementById('visitInsuranceId')?.value;
            if (currentInsuranceId) selectInsurance(currentInsuranceId);
        }

        return true;
    }

    async function submitAppointmentAction(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalHtml = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="ti ti-loader me-1"></i>{{ __('appointments.working') }}';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const payload = (response.headers.get('content-type') || '').includes('application/json')
                ? await response.json()
                : {};

            if (!response.ok) {
                showFeedback('danger', payload.message || @json(__('appointments.unable_complete_action')));
                return;
            }

            const followUp = form.dataset.followUp === 'visit'
                ? (payload.visit_redirect_url || payload.redirect_url)
                : payload.redirect_url;

            updateStatusBadges(payload);
            removeMatchingForms(form);

            showFeedback(
                'success',
                '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">'
                    + '<div><strong>' + (payload.message || @json(__('appointments.updated_successfully'))) + '</strong></div>'
                    + (followUp ? '<div><a href="' + followUp + '" class="btn btn-sm btn-success">{{ __('appointments.open') }}</a></div>' : '')
                    + '</div>'
            );

            refreshAppointmentPage(payload.redirect_url || window.location.href);
        } catch (error) {
            showFeedback('danger', @json(__('appointments.network_error_action')));
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalHtml;
            }
        }
    }

    document.getElementById('runVerificationBtn')?.addEventListener('click', function () {
        const code = document.getElementById('verificationReferenceInput')?.value.trim();
        runVerification(code || null);
    });

    document.getElementById('runVerificationBtn2')?.addEventListener('click', function () {
        runVerification(null);
    });

    continueCheckInBtn?.addEventListener('click', function () {
        if (!pendingCheckInForm || !verificationAccepted) return;

        pendingCheckInForm.querySelector('#checkInVisitInsuranceId').value = document.getElementById('visitInsuranceId')?.value || '';
        pendingCheckInForm.querySelector('#checkInInsuranceVerificationId').value = document.getElementById('insuranceVerificationId')?.value || '';
        pendingCheckInForm.querySelector('#checkInVerificationReferenceCode').value = document.getElementById('verificationReferenceInput')?.value || '';
        checkInModal?.hide();
        submitAppointmentAction(pendingCheckInForm);
    });

    forms.forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (form.dataset.requiresInsuranceVerification === '1') {
                if (openCheckInInsuranceModal(form)) {
                    return;
                }
            }

            submitAppointmentAction(form);
        });
    });
});
</script>
@endpush
