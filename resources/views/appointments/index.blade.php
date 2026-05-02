@extends('layouts.app')

@section('title', 'Appointments')

@section('content')
<div class="content">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="page-title">Appointments <span class="badge bg-primary ms-2">{{ $appointments->total() }}</span></h3>
            </div>
            <div class="col-sm-6 text-sm-end">
                @can('appointments.view')
                <a href="{{ route('admin.appointments.calendar') }}" class="btn btn-outline-info me-2">
                    <i class="ti ti-calendar me-1"></i> Calendar
                </a>
                @endcan
                @can('appointments.create')
                <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Appointment
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['total_today'] }}</h3>
                    <p class="text-muted mb-0">Today Total</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-secondary">{{ $stats['scheduled_today'] }}</h3>
                    <p class="text-muted mb-0">Scheduled</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-info">{{ $stats['confirmed_today'] }}</h3>
                    <p class="text-muted mb-0">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-primary">{{ $stats['checked_in_today'] }}</h3>
                    <p class="text-muted mb-0">Checked In</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-success">{{ $stats['completed_today'] }}</h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-dark">{{ $stats['no_show_today'] }}</h3>
                    <p class="text-muted mb-0">No Show</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.appointments.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search patient or apt#..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}" placeholder="Date">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Appointments Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="p-3 pb-0">
                <div id="appointmentIndexActionFeedback" class="alert d-none mb-0" role="alert"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Apt #</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                        <tr data-appointment-row="{{ $appointment->id }}">
                            <td>
                                <a href="{{ route('admin.appointments.show', $appointment) }}" class="fw-medium">
                                    {{ $appointment->appointment_number }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $appointment->patient) }}">
                                    {{ $appointment->patient->full_name ?? $appointment->patient->first_name . ' ' . $appointment->patient->last_name }}
                                </a>
                            </td>
                            <td>{{ $appointment->doctor?->name ?? '—' }}</td>
                            <td>{{ $appointment->department->name }}</td>
                            <td>{{ $appointment->appointment_date->format('d M Y') }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}
                                @if($appointment->end_time)
                                    – {{ \Carbon\Carbon::parse($appointment->end_time)->format('h:i A') }}
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-outline-primary">{{ $appointment->visit_type->label() }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $appointment->status->color() }} js-appointment-status-badge">
                                    {{ $appointment->status->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.appointments.show', $appointment) }}">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        @can('appointments.edit')
                                        @if($appointment->is_active)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.appointments.edit', $appointment) }}">
                                                <i class="ti ti-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                        @if($appointment->status === \App\Enums\AppointmentStatus::SCHEDULED)
                                        <li>
                                                <form method="POST" action="{{ route('admin.appointments.transition', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-check me-2"></i>Confirm
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
                                        @can('appointments.create')
                                        <li>
                                                <form method="POST" action="{{ route('admin.appointments.check-in', $appointment) }}" class="js-appointment-action-form" data-follow-up="visit">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-login me-2"></i>Check In
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        <li>
                                            <form method="POST" action="{{ route('admin.appointments.no-show', $appointment) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-user-off me-2"></i>No Show
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($appointment->is_active && $appointment->status !== \App\Enums\AppointmentStatus::CHECKED_IN)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $appointment->id }}">
                                                <i class="ti ti-x me-2"></i>Cancel
                                            </button>
                                        </li>
                                        @endif
                                    </ul>
                                </div>

                                {{-- Cancel Modal --}}
                                @if($appointment->is_active)
                                <div class="modal fade" id="cancelModal{{ $appointment->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.appointments.cancel', $appointment) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Appointment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel appointment <strong>{{ $appointment->appointment_number }}</strong>?</p>
                                                    <div class="mb-3">
                                                        <label class="form-label">Cancellation Reason</label>
                                                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Optional reason for cancellation..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                                No appointments found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($appointments->hasPages())
    <div class="d-flex justify-content-end mt-3">
        {{ $appointments->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const feedback = document.getElementById('appointmentIndexActionFeedback');
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

    if (!feedback || !forms.length) {
        return;
    }

    function showFeedback(type, html) {
        feedback.className = 'alert alert-' + type;
        feedback.innerHTML = html;
        feedback.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateRowState(form, payload) {
        const row = form.closest('[data-appointment-row]');
        const statusBadge = row ? row.querySelector('.js-appointment-status-badge') : null;
        const statusValue = payload.appointment_status;
        const statusLabel = payload.appointment_status_label;
        const statusColor = payload.status_color || statusColors[statusValue] || 'secondary';

        if (statusBadge && statusLabel) {
            statusBadge.className = 'badge bg-' + statusColor + ' js-appointment-status-badge';
            statusBadge.textContent = statusLabel;
        }

        const actionItem = form.closest('li');

        if (actionItem) {
            actionItem.remove();
        }
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

                updateRowState(form, payload);

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
