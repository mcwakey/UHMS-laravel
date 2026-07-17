@extends('layouts.app')

@section('title', __('appointments.title'))

@section('content')
<x-page-header :title="__('appointments.title')" :description="__('appointments.description')" icon="ti-calendar-event">
    <!-- <span class="badge bg-primary ms-2">{{ $appointments->total() }}</span> -->
    <x-slot:actions>
        @include('appointments.partials.view-switch', ['active' => 'list'])
        @can('appointments.create')
        <a href="{{ $workspaceRoutes->route('admin.appointments.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i> {{ __('appointments.new_appointment') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

    {{-- Stats Cards --}}
    <div class="row mb-0">
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
    <x-filter-bar
        :action="$workspaceRoutes->route('admin.appointments.index')"
        :reset-url="$workspaceRoutes->route('admin.appointments.index')"
        class="mb-0"
        ajax
        ajax-target="#appointmentsIndexResults"
    >
        <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $appointments->perPage() }}" data-filter-per-page-input>

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
        @if($isDoctorWorkspace)
        <div class="col-md-2">
            <label class="form-label small d-block">&nbsp;</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" role="switch" id="appointmentMyPatientsOnly" name="my_patients_only" value="1" @checked(! empty($filters['my_patients_only']))>
                <label class="form-check-label" for="appointmentMyPatientsOnly">
                    {{ __('appointments.my_patients_only') }}
                </label>
            </div>
        </div>
        @else
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
        @endif
        <div class="col-md-2">
            @include('partials.date-range-filter', [
                'id' => 'appointmentIndexDateRangePicker',
                'value' => $filters['date_range'] ?? '',
                'labelClass' => 'small',
                'submitOnApply' => true,
            ])
        </div>
        <x-slot:actions>
            <a href="{{ $workspaceRoutes->route('admin.appointments.index') }}" class="btn btn-outline-secondary btn-icon" aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" data-filter-reset>
                <i class="ti ti-x"></i>
            </a>
        </x-slot:actions>
    </x-filter-bar>

    <div id="appointmentsIndexResults">
        <div class=" pb-0">
            <div id="appointmentIndexActionFeedback" class="alert d-none mb-0" role="alert"></div>
        </div>

        <x-data-table
            id="appointmentsDataTable"
            :paginator="$appointments"
            show-summary
            show-per-page
            :current-per-page="$filters['per_page'] ?? $appointments->perPage()"
            :per-page-options="[10, 25, 50, 100, 'all']"
        >
            <x-slot:head>
                <tr>
                    <th>{{ __('appointments.appointment_no') }}</th>
                    <th>{{ __('patients.patient_name') }}</th>
                    <th>{{ __('common.phone') }}</th>
                    <th>{{ __('common.doctor') }}</th>
                    <th>{{ __('common.date') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </x-slot:head>

                        @forelse($appointments as $appointment)
                        <tr data-appointment-row="{{ $appointment->id }}">
                            <td>
                                <a href="{{ $workspaceRoutes->route('admin.appointments.show', $appointment) }}" class="fw-medium text-primary">
                                    {{ $appointment->appointment_number }}
                                </a>
                                <div class="small text-muted">{{ $appointment->patient->patient_number }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-md rounded-circle bg-light text-dark me-2 flex-shrink-0">
                                        @if($appointment->patient->avatar)
                                            <img src="{{ Storage::url($appointment->patient->avatar) }}" alt="{{ $appointment->patient->full_name }}" class="rounded-circle">
                                        @else
                                            {{ strtoupper(substr($appointment->patient->first_name, 0, 1) . substr($appointment->patient->last_name, 0, 1)) }}
                                        @endif
                                    </span>
                                    <div>
                                        <a href="{{ $workspaceRoutes->route('admin.patients.show', $appointment->patient) }}">
                                            {{ $appointment->patient->full_name ?? $appointment->patient->first_name . ' ' . $appointment->patient->last_name }}
                                        </a>
                                        <br><small class="text-muted">{{ $appointment->patient->gender?->label() }} · {{ $appointment->patient->age }} yrs</small>
                                    <!-- <div class="small text-muted">{{ $appointment->patient->patient_number }}</div> -->
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $patientPhones = collect([
                                        app(\App\Services\PatientPrivacyService::class)->display('phone', $appointment->patient?->phone),
                                        app(\App\Services\PatientPrivacyService::class)->display('phone_secondary', $appointment->patient?->phone_secondary),
                                    ])->filter()->unique();
                                @endphp
                                @if($patientPhones->isNotEmpty())
                                    <div class="small text-muted">
                                        <i class="ti ti-phone me-1"></i>{{ $patientPhones->implode(' / ') }}
                                    </div>
                                @endif
                                @if($appointment->patient->email)
                                <small class="text-muted"><x-patient-protected-field field="email" :value="$appointment->patient->email" /></small>
                                @endif
                            </td>
                            {{-- <td>
                                <x-patient-protected-field field="phone" :value="$appointment->patient->phone" />
                                @if($appointment->patient->email)
                                <br><small class="text-muted"><x-patient-protected-field field="email" :value="$appointment->patient->email" /></small>
                                @endif
                            </td> --}}
                            <td>{{ $appointment->doctor?->name ?? '—' }}
                                <div class="small text-muted">{{ $appointment->department->name }}</div>
                            </td>
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
                                            <a class="dropdown-item" href="{{ $workspaceRoutes->route('admin.appointments.show', $appointment) }}">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        @can('appointments.edit')
                                        @if($appointment->is_active)
                                        <li>
                                            <a class="dropdown-item" href="{{ $workspaceRoutes->route('admin.appointments.edit', $appointment) }}">
                                                <i class="ti ti-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                        @if($appointment->status === \App\Enums\AppointmentStatus::SCHEDULED)
                                        <li>
                                                <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.transition', $appointment) }}" class="js-appointment-action-form" data-follow-up="appointment">
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
                                                <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.check-in', $appointment) }}" class="js-appointment-action-form" data-follow-up="visit">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-login me-2"></i>{{ __('appointments.check_in_patient') }}
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        <li>
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.no-show', $appointment) }}">
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
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.cancel', $appointment) }}">
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
        </x-data-table>
    </div>
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
        const feedback = document.getElementById('appointmentIndexActionFeedback');
        if (!feedback) return;

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

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('.js-appointment-action-form');
        if (!form) return;

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
</script>
@endpush
