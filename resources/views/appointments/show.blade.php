@extends('layouts.app')

@section('title', 'Appointment Details')

@section('content')
<div class="content">
    <div id="appointmentActionFeedback" class="alert d-none" role="alert"></div>

    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">
                    Appointment {{ $appointment->appointment_number }}
                    <span class="badge bg-{{ $appointment->status->color() }} ms-2 js-appointment-status-badge">{{ $appointment->status->label() }}</span>
                </h3>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    @if($appointment->status === \App\Enums\AppointmentStatus::SCHEDULED)
                    <form method="POST" action="{{ route('admin.appointments.transition', $appointment) }}" class="d-inline js-appointment-action-form" data-follow-up="appointment">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Confirm
                        </button>
                    </form>
                    @endif
                    @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                    @can('appointments.create')
                    <form method="POST" action="{{ route('admin.appointments.check-in', $appointment) }}" class="d-inline js-appointment-action-form" data-follow-up="visit">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-login me-1"></i> Check In Patient
                        </button>
                    </form>
                    @endcan
                    <form method="POST" action="{{ route('admin.appointments.no-show', $appointment) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-dark">
                            <i class="ti ti-user-off me-1"></i> No Show
                        </button>
                    </form>
                    @endif
                    @if($appointment->is_active && $appointment->status !== \App\Enums\AppointmentStatus::CHECKED_IN)
                    @can('appointments.edit')
                    <a href="{{ route('admin.appointments.edit', $appointment) }}" class="btn btn-outline-primary">
                        <i class="ti ti-pencil me-1"></i> Edit
                    </a>
                    @endcan
                    @endif
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Appointment Details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-calendar me-2"></i>Appointment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-responsive"><table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Appointment #</td>
                                    <td class="fw-medium">{{ $appointment->appointment_number }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Date</td>
                                    <td>{{ $appointment->appointment_date->format('l, d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Time</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}
                                        @if($appointment->end_time)
                                            – {{ \Carbon\Carbon::parse($appointment->end_time)->format('h:i A') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Type</td>
                                    <td><span class="badge bg-outline-primary">{{ $appointment->visit_type->label() }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status</td>
                                    <td><span class="badge bg-{{ $appointment->status->color() }} js-appointment-status-badge">{{ $appointment->status->label() }}</span></td>
                                </tr>
                            </table></div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive"><table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted" width="40%">Department</td>
                                    <td>{{ $appointment->department->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Doctor</td>
                                    <td>{{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created By</td>
                                    <td>{{ $appointment->createdByUser->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created</td>
                                    <td>{{ $appointment->created_at->format('d M Y, h:i A') }}</td>
                                </tr>
                                @if($appointment->visit)
                                <tr>
                                    <td class="text-muted">Linked Visit</td>
                                    <td>
                                        <a href="{{ route('admin.visits.show', $appointment->visit) }}">
                                            {{ $appointment->visit->visit_number }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                            </table></div>
                        </div>
                    </div>

                    @if($appointment->reason)
                    <div class="mt-3">
                        <h6 class="text-muted">Reason for Visit</h6>
                        <p class="mb-0">{{ $appointment->reason }}</p>
                    </div>
                    @endif

                    @if($appointment->notes)
                    <div class="mt-3">
                        <h6 class="text-muted">Notes</h6>
                        <p class="mb-0">{{ $appointment->notes }}</p>
                    </div>
                    @endif

                    @if($appointment->status === \App\Enums\AppointmentStatus::CANCELLED)
                    <div class="mt-3">
                        <div class="alert alert-danger mb-0">
                            <h6 class="alert-heading"><i class="ti ti-x me-1"></i>Cancelled</h6>
                            @if($appointment->cancelledByUser)
                            <p class="mb-1"><strong>Cancelled by:</strong> {{ $appointment->cancelledByUser->name }}</p>
                            @endif
                            @if($appointment->cancellation_reason)
                            <p class="mb-0"><strong>Reason:</strong> {{ $appointment->cancellation_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Status Timeline --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-timeline me-2"></i>Status Flow</h5>
                </div>
                <div class="card-body">
                    @php
                        $allStatuses = [
                            \App\Enums\AppointmentStatus::SCHEDULED,
                            \App\Enums\AppointmentStatus::CONFIRMED,
                            \App\Enums\AppointmentStatus::CHECKED_IN,
                            \App\Enums\AppointmentStatus::IN_PROGRESS,
                            \App\Enums\AppointmentStatus::COMPLETED,
                        ];
                        $currentIndex = array_search($appointment->status, $allStatuses);
                        $isCancelled = $appointment->status === \App\Enums\AppointmentStatus::CANCELLED;
                        $isNoShow = $appointment->status === \App\Enums\AppointmentStatus::NO_SHOW;
                    @endphp

                    @if($isCancelled || $isNoShow)
                    <div class="text-center py-3">
                        <span class="badge bg-{{ $appointment->status->color() }} fs-6 px-4 py-2">
                            <i class="ti ti-{{ $isCancelled ? 'x' : 'user-off' }} me-1"></i>
                            {{ $appointment->status->label() }}
                        </span>
                    </div>
                    @else
                    <div class="d-flex justify-content-between align-items-center">
                        @foreach($allStatuses as $index => $status)
                        <div class="text-center flex-fill">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $currentIndex !== false && $index <= $currentIndex ? 'bg-' . $status->color() . ' text-white' : 'bg-light text-muted' }}"
                                 style="width: 40px; height: 40px;">
                                @if($currentIndex !== false && $index < $currentIndex)
                                    <i class="ti ti-check"></i>
                                @elseif($currentIndex !== false && $index === $currentIndex)
                                    <i class="ti ti-point-filled"></i>
                                @else
                                    <small>{{ $index + 1 }}</small>
                                @endif
                            </div>
                            <div class="mt-1 small {{ $currentIndex !== false && $index <= $currentIndex ? 'fw-medium' : 'text-muted' }}">
                                {{ $status->label() }}
                            </div>
                        </div>
                        @if(!$loop->last)
                        <div class="flex-fill border-top {{ $currentIndex !== false && $index < $currentIndex ? 'border-success' : 'border-light' }}" style="height: 2px; margin-top: -15px;"></div>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Patient Sidebar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-user me-2"></i>Patient</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar avatar-lg bg-primary rounded-circle d-inline-flex align-items-center justify-content-center">
                            <span class="text-white fs-4">{{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}{{ strtoupper(substr($appointment->patient->last_name, 0, 1)) }}</span>
                        </div>
                        <h5 class="mt-2 mb-0">{{ $appointment->patient->first_name }} {{ $appointment->patient->last_name }}</h5>
                        <small class="text-muted">{{ $appointment->patient->patient_number }}</small>
                    </div>
                    <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Phone</td>
                            <td>{{ $appointment->patient->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gender</td>
                            <td>{{ $appointment->patient->gender?->label() ?? '—' }}</td>
                        </tr>
                        @if($appointment->patient->date_of_birth)
                        <tr>
                            <td class="text-muted">Age</td>
                            <td>{{ $appointment->patient->date_of_birth->age }} years</td>
                        </tr>
                        @endif
                    </table></div>
                    <div class="mt-3">
                        <a href="{{ route('admin.patients.show', $appointment->patient) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="ti ti-external-link me-1"></i> View Patient Profile
                        </a>
                    </div>
                </div>
            </div>

            {{-- Actions Card --}}
            @if($appointment->is_active)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($appointment->status === \App\Enums\AppointmentStatus::SCHEDULED)
                        <form method="POST" action="{{ route('admin.appointments.transition', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-check me-1"></i> Confirm Appointment
                            </button>
                        </form>
                        @endif

                        @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                        @can('appointments.create')
                        <form method="POST" action="{{ route('admin.appointments.check-in', $appointment) }}" class="js-appointment-action-form" data-follow-up="visit">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-login me-1"></i> Check In & Create Visit
                            </button>
                        </form>
                        @endcan
                        @endif

                        @can('appointments.create')
                        <a href="{{ route('admin.appointments.create', ['patient_id' => $appointment->patient_id]) }}" class="btn btn-outline-primary">
                            <i class="ti ti-calendar-plus me-1"></i> Schedule Another
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
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
                submitButton.innerHTML = '<i class="ti ti-loader me-1"></i>Working...';
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
                    showFeedback('danger', payload.message || 'Unable to complete appointment action.');
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
                        + '<div><strong>' + (payload.message || 'Appointment updated successfully.') + '</strong></div>'
                        + (followUp ? '<div><a href="' + followUp + '" class="btn btn-sm btn-success">Open</a></div>' : '')
                        + '</div>'
                );
            } catch (error) {
                showFeedback('danger', 'Network error while processing the appointment action.');
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
