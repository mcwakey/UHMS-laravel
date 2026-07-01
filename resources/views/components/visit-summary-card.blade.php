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

{{-- Patient Journey widget — self-contained. Pass a $visit; it derives everything
     from existing records via the journey services. No new navigation. --}}
@php
    $journeyService = app(\App\Services\Journey\PatientJourneyService::class);
    $delayService = app(\App\Services\Journey\JourneyDelayService::class);
    $timelineBuilder = app(\App\Services\Journey\JourneyTimelineBuilder::class);

    $snapshot = $journeyService->snapshot($visit);
    $delay = $delayService->currentDelay($visit, $snapshot);
    $timeline = $timelineBuilder->build($visit, $snapshot);
    $durations = $delayService->durations($visit);

    $delayVariant = ['normal' => 'success', 'delayed' => 'warning', 'critical' => 'danger'][$delay['status']] ?? 'secondary';
    $elapsedH = intdiv($delay['minutes'], 60);
    $elapsedM = $delay['minutes'] % 60;
    $totalH = intdiv($durations['total_minutes'], 60);
    $totalM = $durations['total_minutes'] % 60;

    $stepStyle = [
        'completed' => ['variant' => 'success', 'icon' => 'ti-circle-check-filled'],
        'active'    => ['variant' => 'primary', 'icon' => 'ti-player-play-filled'],
        'waiting'   => ['variant' => 'secondary', 'icon' => 'ti-circle'],
        'skipped'   => ['variant' => 'light', 'icon' => 'ti-circle-minus'],
    ];

    // Why is the patient delayed + where to act? Only resolve when delayed/critical.
    $action = in_array($delay['status'], ['delayed', 'critical'], true)
        ? app(\App\Services\Journey\JourneyActionResolver::class)->resolve($visit, auth()->user())
        : null;

    // Who owns it? (Phase 9.5) Build the handoff from the resolved action (no re-resolve)
    // and attach any persisted assignment + derived escalation.
    $handoff = $action ? app(\App\Services\Journey\JourneyHandoffResolver::class)->fromAction($visit, $action) : null;
    $handoffAssignment = null;
    $canActHandoff = false;
    $escVariant = 'secondary';
    $asgVariant = 'secondary';
    if ($handoff) {
        $handoffAssignment = app(\App\Services\Journey\JourneyHandoffAssignmentService::class)->findFor($handoff);
        $handoff->attachAssignment($handoffAssignment, app(\App\Services\Journey\JourneyEscalationService::class)->levelFor($handoff, $handoffAssignment));
        $destCap = match ($handoff->toDepartmentType) {
            'investigation', 'radiology', 'blood_bank' => 'investigation_access',
            'pharmacy' => 'pharmacy_access',
            'inpatient', 'maternity' => 'ward_access',
            'finance', 'administrative' => 'financial_access',
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => 'consultation_access',
            default => null,
        };
        $canActHandoff = $destCap === null || app(\App\Services\Department\DepartmentDashboardCapabilityService::class)->can(auth()->user(), $destCap);
        $escVariant = ['critical' => 'danger', 'supervisor' => 'danger', 'warning' => 'warning'][$handoff->escalationLevel] ?? 'secondary';
        $asgVariant = ['assigned' => 'info', 'acknowledged' => 'success'][$handoff->assignmentStatus] ?? 'secondary';
    }
@endphp

<div {{ $attributes->merge(['class' => 'card mb-3']) }}>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">
            <i class="ti {{ $isAppointment ? 'ti-calendar' : 'ti-clipboard-text' }} me-1"></i>{{ $title }}
        </h6>

        @if($snapshot['current_stage'] !== null && ! $snapshot['is_terminal'] && ! $snapshot['is_completed'])
            <span class="badge bg-{{ $delayVariant }}"><i class="ti ti-alert-triangle me-1"></i>{{ __('journey.status.'.$delay['status']) }}</span>
        @endif

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

        <hr />

    <!-- <div class="card-body"> -->
        @if($snapshot['is_terminal'])
            <div class="text-muted"><i class="ti ti-door-exit me-1"></i>{{ __('journey.widget.exited') }}</div>
        @elseif($snapshot['is_completed'])
            <div class="text-success fw-medium"><i class="ti ti-circle-check me-1"></i>{{ __('journey.widget.completed') }}</div>
        @elseif($snapshot['current_stage'] !== null)
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase">{{ __('journey.widget.current_stage') }}</div>
                    <div class="fw-bold"><i class="ti {{ $snapshot['current_stage']->icon() }} me-1 text-{{ $delayVariant }}"></i>{{ $snapshot['current_stage']->translatedLabel() }}</div>
                    @if($snapshot['next_stage'] !== null)
                        <div class="small text-muted"><i class="ti ti-arrow-right me-1"></i>{{ __('journey.widget.next_step') }}: <strong>{{ $snapshot['next_stage']->translatedLabel() }}</strong></div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase">{{ __('journey.widget.elapsed') }}</div>
                    <div class="fw-bold text-{{ $delayVariant }}">{{ $elapsedH }}h {{ $elapsedM }}m</div>
                    @if($durations['total_minutes'] > 0)
                        <div class="small text-muted"><i class="ti ti-clock me-1"></i>{{ __('journey.widget.total_duration') }}: {{ $totalH }}h {{ $totalM }}m</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase">{{ __('journey.widget.currently_in') }}</div>
                    <div class="fw-bold">{{ $snapshot['location']['department'] ?? '—' }}</div>
                    @if($snapshot['location']['since'])
                        <div class="small text-muted">{{ __('journey.widget.waiting_since') }} {{ $snapshot['location']['since']->isoFormat('HH:mm') }}</div>
                    @endif
                </div>
            </div>

            {{-- Root cause + next action + deep link (only when delayed/critical). --}}
            @if($action)
                <div class="row alert alert-{{ $delayVariant }}">
                    <div class="col-md-4">
                        <div><i class="ti {{ $action->cause->icon() }} me-1"></i><strong>{{ __('journey.widget.delay_reason') }}:</strong> {{ $action->cause->translatedLabel() }}</div>
                        <div class="small"><i class="ti ti-building-hospital me-1"></i>{{ __('journey.widget.responsible') }}: {{ $action->ownerDepartmentName ?? \Illuminate\Support\Str::headline((string) $action->ownerType) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small d-flex flex-wrap align-items-center gap-2">
                            <span><i class="ti ti-arrow-right me-1"></i><strong>{{ __('journey.widget.next_action') }}:</strong> {{ $action->actionLabel }}</span>

                        </div>
                    </div>
                    <div class="col-md-4">
                        @if($action->actionUrl)
                            <a href="{{ $action->actionUrl }}" class="btn btn-sm btn-{{ $delayVariant }}"><i class="ti ti-external-link me-1"></i>{{ __('journey.worklist.open_action') }}</a>
                        @endif
                    </div>
                </div>

                {{-- Who owns it? Assignment + escalation (Phase 9.5). --}}
                @if($handoff)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
                        <span class="text-muted">{{ __('journey.assignment.assigned_to') }}:</span>
                        @if($handoff->assignedToName)
                            <strong>{{ $handoff->assignedToName }}</strong>
                            <span class="badge bg-{{ $asgVariant }}-subtle text-{{ $asgVariant }}">{{ __('journey.assignment.status.'.$handoff->assignmentStatus) }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">{{ __('journey.assignment.unassigned') }}</span>
                        @endif
                        @if($handoff->escalationLevel !== 'none')
                            <span class="badge bg-{{ $escVariant }}"><i class="ti ti-alert-triangle me-1"></i>{{ __('journey.escalation.label') }}: {{ __('journey.escalation.'.$handoff->escalationLevel) }}</span>
                        @endif
                        @if($canActHandoff)
                            @if($handoff->isUnassigned())
                                <form method="POST" action="{{ route('admin.journey.handoffs.claim') }}" class="d-inline">@csrf
                                    <input type="hidden" name="visit_id" value="{{ $visit->id }}"><input type="hidden" name="cause" value="{{ $handoff->cause->value }}">
                                    <button class="btn btn-sm btn-primary py-0">{{ __('journey.assignment.claim') }}</button>
                                </form>
                            @elseif($handoff->assignmentId)
                                <form method="POST" action="{{ route('admin.journey.handoffs.resolve', $handoff->assignmentId) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-success py-0">{{ __('journey.assignment.resolve') }}</button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endif
            @endif
        @endif

        {{-- Stage timeline --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            @foreach($timeline as $step)
                @php $style = $stepStyle[$step['status']] ?? $stepStyle['waiting']; @endphp
                <span class="badge bg-{{ $style['variant'] }}{{ $style['variant'] === 'light' ? ' text-muted text-decoration-line-through' : ($style['variant'] === 'secondary' ? '-subtle text-secondary' : '') }} d-inline-flex align-items-center gap-1">
                    <i class="ti {{ $style['icon'] }}"></i>{{ $step['label'] }}@if(! is_null($step['minutes']) && $step['minutes'] > 0) <span class="opacity-75">· {{ $step['minutes'] }}m</span>@endif
                </span>
                @if(! $loop->last)<i class="ti ti-chevron-right text-muted fs-12"></i>@endif
            @endforeach
        </div>
    <!-- </div> -->

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





<!-- <div class="card shadow-sm mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti ti-route me-1"></i>{{ __('journey.widget.title') }}</h6>
    </div>
</div> -->
