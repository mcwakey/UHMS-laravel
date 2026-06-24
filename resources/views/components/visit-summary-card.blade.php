@props([
    'visit' => null,
    'appointment' => null,
    'title' => null,
    'showPreviewAction' => true,
])

@php
    $record = $visit ?? $appointment;
    $isAppointment = $appointment !== null;
    $visitType = $record?->visit_type;
    $priority = $record?->priority;
    $date = $isAppointment ? $appointment?->appointment_date : $visit?->visit_date;
    $dateLabel = $isAppointment ? __('common.date') : __('visits.visit_date_label');
    $title ??= $isAppointment ? __('appointments.appointment_information') : __('visits.visit_details');

    $timeText = null;
    if ($isAppointment && $appointment?->start_time) {
        $timeText = \Carbon\Carbon::parse($appointment->start_time)->format('h:i A');
        if ($appointment->end_time) {
            $timeText .= ' - ' . \Carbon\Carbon::parse($appointment->end_time)->format('h:i A');
        }
    }

    $complaintText = $isAppointment
        ? ($appointment?->chief_complaint ?: $appointment?->reason)
        : $visit?->chief_complaint;
    $complaintLabel = $isAppointment && ! $appointment?->chief_complaint && $appointment?->reason
        ? __('appointments.reason_for_visit')
        : ($isAppointment ? __('appointments.chief_complaint') : __('visits.chief_complaint_label'));
    $notesText = $isAppointment ? $appointment?->notes : $visit?->notes;
    $notesLabel = $isAppointment ? __('appointments.notes') : __('visits.notes_label');
@endphp

<div {{ $attributes->merge(['class' => 'card mb-3']) }}>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">
            <i class="ti {{ $isAppointment ? 'ti-calendar' : 'ti-clipboard-text' }} me-1"></i>{{ $title }}
        </h6>

        @isset($actions)
            <div class="d-flex align-items-center gap-2 flex-wrap">{{ $actions }}</div>
        @elseif($showPreviewAction && $visit)
            @can('visits.preview')
            <a href="{{ route('admin.visits.preview', $visit) }}" class="btn btn-outline-info btn-md">
                <i class="ti ti-eye-search me-1"></i>{{ __('visits.preview_visit_btn') }}
            </a>
            @endcan
        @endif

                    @if($isAppointment && $appointment->visit)
            <!-- <div class="col-md-3"> -->
                <!-- <label class="text-muted small mb-1">{{ __('appointments.linked_visit') }}</label> -->
                <div class="fw-medium">
                    <a href="{{ route('admin.visits.show', $appointment->visit) }}">
                        {{ $appointment->visit->visit_number }}
                    </a>
                </div>
            <!-- </div> -->
                    @endif
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('visits.visit_type_label') }}</label>
                <div>
                    <span class="badge bg-{{ $visitType === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visitType === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                        {{ $visitType?->translatedLabel() ?? '—' }}
                    </span>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('visits.priority_label') }}</label>
                <div>
                    @if($priority)
                        <x-status-badge :status="$priority" />
                    @else
                        <span class="fw-medium">—</span>
                    @endif
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ $dateLabel }}</label>
                <div class="fw-medium">{{ $date ? $date->format($isAppointment ? 'l, d M Y' : 'd M Y') : '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ $isAppointment ? __('appointments.time') : __('visits.duration_label') }}</label>
                <div class="fw-medium">{{ $isAppointment ? ($timeText ?? '—') : ($visit?->duration ?? '—') }}</div>
            </div>
        </div>

        @if($isAppointment)
        <div class="row mb-3">
            <!-- <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.appointment_number') }}</label>
                <div class="fw-medium">{{ $appointment->appointment_number }}</div>
            </div> -->
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.department') }}</label>
                <div class="fw-medium">{{ $appointment->department->name }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.doctor') }}</label>
                <div class="fw-medium">{{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : '—' }}</div>
            </div>

            <!-- <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created_by') }}</label>
                <div class="fw-medium">{{ $appointment->createdByUser ? $appointment->createdByUser->name : '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created') }}</label>
                <div class="fw-medium">{{ $appointment->created_at->format('d M Y, h:i A') }}</div>
            </div> -->
        <!-- </div>

        <div class="row mb-3"> -->
            <!-- <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.appointment_number') }}</label>
                <div class="fw-medium">{{ $appointment->appointment_number }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.department') }}</label>
                <div class="fw-medium">{{ $appointment->department->name }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.doctor') }}</label>
                <div class="fw-medium">{{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : '—' }}</div>
            </div> -->

            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created_by') }}</label>
                <div class="fw-medium">{{ $appointment->createdByUser ? $appointment->createdByUser->name : '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created') }}</label>
                <div class="fw-medium">{{ $appointment->created_at->format('d M Y, h:i A') }}</div>
            </div>
                    <!-- @if($appointment->visit)
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.linked_visit') }}</label>
                <div class="fw-medium">
                    <a href="{{ route('admin.visits.show', $appointment->visit) }}">
                        {{ $appointment->visit->visit_number }}
                    </a>
                </div>
            </div>
                    @endif -->
        </div>

        <div class="row mb-3">
            <!-- <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.appointment_number') }}</label>
                <div class="fw-medium">{{ $appointment->appointment_number }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.department') }}</label>
                <div class="fw-medium">{{ $appointment->department->name }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('common.doctor') }}</label>
                <div class="fw-medium">{{ $appointment->doctor ? 'Dr. ' . $appointment->doctor->name : '—' }}</div>
            </div> -->

            <!-- <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created_by') }}</label>
                <div class="fw-medium">{{ $appointment->createdByUser ? $appointment->createdByUser->name : '—' }}</div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="text-muted small mb-1">{{ __('appointments.created') }}</label>
                <div class="fw-medium">{{ $appointment->created_at->format('d M Y, h:i A') }}</div>
            </div> -->
            @if($complaintText)
            <div class="col-md-12">
                <label class="text-muted small mb-1">{{ $complaintLabel }}</label>
                <div class="bg-light rounded p-2">{{ $complaintText }}</div>
            </div>
            @endif

            @if($notesText)
            <div class="col-md-12">
                <label class="text-muted small mb-1">{{ $notesLabel }}</label>
            <div class="bg-light rounded p-2">{{ $notesText }}</div>
            </div>
            @endif
        </div>
        @endif

        @if($isAppointment && $appointment->status === \App\Enums\AppointmentStatus::CANCELLED)
        <div class="mt-3">
            <div class="alert alert-danger mb-0">
                <h6 class="alert-heading"><i class="ti ti-x me-1"></i>{{ __('appointments.cancelled') }}</h6>
                @if($appointment->cancelledByUser)
                <p class="mb-1"><strong>{{ __('appointments.cancelled_by') }}</strong> {{ $appointment->cancelledByUser->name }}</p>
                @endif
                @if($appointment->cancellation_reason)
                <p class="mb-0"><strong>{{ __('appointments.reason') }}</strong> {{ $appointment->cancellation_reason }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
