@extends('layouts.app')

@section('title', __('appointments.title'))

@section('content')
<x-page-header :title="__('appointments.title')" icon="ti-calendar-event">
    <!-- <span class="badge bg-primary ms-2">{{ $appointments->total() }}</span> -->
    <x-slot:actions>
        @include('appointments.partials.view-switch', ['active' => 'list'])
        @can('appointments.create')
        <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> {{ __('appointments.new_appointment') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['total_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.range_total') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-secondary">{{ $stats['scheduled_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.scheduled') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-info">{{ $stats['confirmed_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.confirmed') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-primary">{{ $stats['checked_in_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.checked_in') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-success">{{ $stats['completed_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.completed') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-1 text-dark">{{ $stats['no_show_cancelled_today'] }}</h3>
                    <p class="text-muted mb-0">{{ __('appointments.no_show_cancelled') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.appointments.index') }}" class="row g-3 align-items-end" data-auto-filter-form="appointments-index">
                <div class="col-md-3">
                    <label class="form-label small">{{ __('common.search') }}</label>
                    <input type="text" name="search" class="form-control" placeholder="{{ __('appointments.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('common.all_statuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->translatedLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.doctor') }}</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">{{ __('appointments.all_doctors') }}</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ ($filters['doctor_id'] ?? '') == $doctor->id ? 'selected' : '' }}>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('common.department') }}</label>
                    <select name="department_id" class="form-select">
                        <option value="">{{ __('common.all_departments') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @include('partials.date-range-filter', [
                        'id' => 'appointmentIndexDateRangePicker',
                        'value' => $filters['date_range'] ?? '',
                        'labelClass' => 'small',
                        'submitOnApply' => true,
                    ])
                </div>
                <div class="col-md-auto">
                    <!-- <button type="submit" class="btn btn-primary me-2">
                        <i class="ti ti-filter me-1"></i> {{ __('common.filter') }}
                    </button> -->
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>
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
                            <th>{{ __('appointments.appointment_no') }}</th>
                            <th>{{ __('common.patient') }}</th>
                            <th>{{ __('common.doctor') }}</th>
                            <th>{{ __('common.department') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
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
                                <div class="small text-muted">{{ $appointment->patient->patient_number }}</div>
                                @php
                                    $patientPhones = collect([
                                        $appointment->patient?->phone,
                                        $appointment->patient?->phone_secondary,
                                    ])->filter()->unique();
                                @endphp
                                @if($patientPhones->isNotEmpty())
                                    <div class="small text-muted">
                                        <i class="ti ti-phone me-1"></i>{{ $patientPhones->implode(' / ') }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $appointment->doctor?->name ?? '—' }}</td>
                            <td>{{ $appointment->department->name }}</td>
                            <td>
                                {{ $appointment->appointment_date->format('d M Y') }}
                                <div class="small text-muted">
                                    <i class="ti ti-clock me-1"></i>{{ \Carbon\Carbon::parse($appointment->start_time)->format('h:i A') }}
                                    @if($appointment->end_time)
                                        - {{ \Carbon\Carbon::parse($appointment->end_time)->format('h:i A') }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $appointment->status->color() }} js-appointment-status-badge">
                                    {{ $appointment->status->translatedLabel() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button aria-label="{{ __('common.actions') }}" title="{{ __('common.actions') }}" type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
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
                                                    <i class="ti ti-login me-2"></i>{{ __('appointments.check_in_patient') }}
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        <li>
                                            <form method="POST" action="{{ route('admin.appointments.no-show', $appointment) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-user-off me-2"></i>{{ __('appointments.no_show_action') }}
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @if($appointment->is_active && $appointment->status !== \App\Enums\AppointmentStatus::CHECKED_IN)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $appointment->id }}">
                                                <i class="ti ti-x me-2"></i>{{ __('appointments.cancel_action') }}
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
                                                    <h5 class="modal-title">{{ __('appointments.cancel_appointment') }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>{{ __('appointments.cancel_appointment_question', ['number' => $appointment->appointment_number]) }}</p>
                                                    <div class="mb-3">
                                                        <label class="form-label">{{ __('appointments.cancellation_reason') }}</label>
                                                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="{{ __('appointments.cancellation_reason_placeholder') }}"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                                                    <button type="submit" class="btn btn-danger">{{ __('appointments.cancel_appointment') }}</button>
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
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                                {{ __('appointments.no_appointments_found') }}
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
@endsection

@push('scripts')
@include('partials.date-range-filter-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @php
        $appointmentIndexI18nData = [
            'working' => __('appointments.working'),
            'unableCompleteAction' => __('appointments.unable_complete_action'),
            'updatedSuccessfully' => __('appointments.updated_successfully'),
            'networkErrorAction' => __('appointments.network_error_action'),
            'open' => __('appointments.open'),
        ];
    @endphp
    const i18n = @json($appointmentIndexI18nData);
    const filterForm = document.querySelector('[data-auto-filter-form="appointments-index"]');
    if (filterForm) {
        let filterTimer = null;
        const searchInput = filterForm.querySelector('input[name="search"]');

        filterForm.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                filterForm.requestSubmit();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(function () {
                    filterForm.requestSubmit();
                }, 400);
            });
        }
    }

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
                submitButton.innerHTML = '<i class="ti ti-loader me-1"></i>' + i18n.working;
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
                    showFeedback('danger', payload.message || i18n.unableCompleteAction);
                    return;
                }

                const followUp = form.dataset.followUp === 'visit'
                    ? (payload.visit_redirect_url || payload.redirect_url)
                    : payload.redirect_url;

                updateRowState(form, payload);

                showFeedback(
                    'success',
                    '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">'
                        + '<div><strong>' + (payload.message || i18n.updatedSuccessfully) + '</strong></div>'
                        + (followUp ? '<div><a href="' + followUp + '" class="btn btn-sm btn-success">' + i18n.open + '</a></div>' : '')
                        + '</div>'
                );
            } catch (error) {
                showFeedback('danger', i18n.networkErrorAction);
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
