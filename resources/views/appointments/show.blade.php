@extends('layouts.app')

@section('title', __('appointments.show_title'))

@section('content')
    <x-page-header-back
        :title="__('appointments.title') . ' - ' . $appointment->appointment_number"
        :href="route('admin.appointments.index')"
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
                    <form method="POST" action="{{ route('admin.appointments.transition', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-primary btn-md">
                            <i class="ti ti-check me-1"></i>{{ __('appointments.confirm') }}
                        </button>
                    </form>
                    @endif

                    @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                        @can('appointments.create')
                        <form method="POST" action="{{ route('admin.appointments.check-in', $appointment) }}" class="js-appointment-action-form" data-follow-up="visit">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-md">
                                <i class="ti ti-login me-1"></i>{{ __('appointments.check_in_patient') }}
                            </button>
                        </form>
                        @endcan
                        <form method="POST" action="{{ route('admin.appointments.no-show', $appointment) }}">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-md">
                                <i class="ti ti-user-off me-1"></i>{{ __('appointments.no_show_action') }}
                            </button>
                        </form>
                    @endif

                    @if($appointment->is_active && $appointment->status !== \App\Enums\AppointmentStatus::CHECKED_IN)
                        @can('appointments.edit')
                        <a href="{{ route('admin.appointments.edit', $appointment) }}" class="btn btn-outline-primary btn-md">
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

            @can('appointments.create')
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('admin.appointments.create', ['patient_id' => $appointment->patient_id]) }}" class="btn btn-outline-primary w-100">
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
                                $lineTotal = (float) $service->price * $quantity;
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
                            $servicesTotal = $appointment->services->sum(fn ($service) => (float) $service->price * (int) ($service->pivot->quantity ?? 1));
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedback = document.getElementById('appointmentActionFeedback');
    const forms = document.querySelectorAll('.js-appointment-action-form');
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

    forms.forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

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
            } catch (error) {
                showFeedback('danger', @json(__('appointments.network_error_action')));
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalHtml;
                }
            }
        });
    });
});
</script>
@endpush
