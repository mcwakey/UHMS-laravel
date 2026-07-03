{{-- ============================================================ --}}
{{-- CONSULTATION SESSIONS --}}
{{-- ============================================================ --}}
<div id="sessionsDrawer">
    <div id="sessionsDrawerHandle" role="button" aria-expanded="true" aria-controls="sessionsDrawerBody" data-consultation-action="toggle-sessions-drawer">
        <i class="ti ti-route fs-5"></i>
        <span class="fw-semibold small">{{ __('consultations.workspace.sessions_for_visit') }}</span>
        <span class="badge bg-white text-primary rounded-pill ms-1">{{ $sessions->count() }}</span>
        <i class="ti ti-chevron-up ms-auto fs-5"></i>
    </div>
    <div id="sessionsDrawerBody">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('consultations.linked_services') }}</th>
                        <th>{{ __('consultations.workspace.contributors') }}</th>
                        <!-- <th>{{ __('common.status') }}</th> -->
                        <!-- <th>{{ __('consultations.started') }}</th> -->
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    @php
                        $rowClass = $selectedRoute && $selectedRoute->id === $session->id ? 'is-current' : '';
                        $rowClass .= $session->status === \App\Models\VisitConsultationRoute::STATUS_COMPLETED ? ' is-completed' : '';
                        $rowClass .= $session->status === \App\Models\VisitConsultationRoute::STATUS_CANCELLED ? ' is-cancelled' : '';
                        $sessionServiceNames = $routeServiceNames($session);
                        $sessionLabel = $session->isEmergencySession() ? __('consultations.emergency_department_session') : ($session->department?->name ?? '-');
                    @endphp
                    <tr class="session-route-row {{ trim($rowClass) }}">
                        <td class="fw-medium">
                            {{ $sessionLabel }}
                            @if($session->isEmergencySession())
                                <span class="badge bg-danger ms-1">{{ __('consultations.workspace.emergency') }}</span>
                            @endif
                        </td>
                        <td>
                            {{ $sessionServiceNames->implode(', ') ?: '-' }}
                            <x-status-badge :status="$session->status" domain="consultation_session" />
                            @if($selectedRoute && $selectedRoute->id === $session->id)
                                <span class="badge bg-primary ms-1">{{ __('consultations.workspace.current') }}</span>
                            @endif
                        </td>
                        <!-- <td>{{ $session->doctor ? 'Dr. ' . $session->doctor->full_name : __('consultations.unassigned') }}</td> -->
                        <td>{{ $contributors->isNotEmpty() ? $contributors->implode(', ') : __('consultations.workspace.no_contributors_yet') }}</td>
                        <!-- <td><x-status-badge :status="$session->status" domain="consultation_session" /></td> -->
                        <!-- <td>
                            <div class="small">{{ $session->started_at?->format('d M, h:i A') ?? '—' }}</div>
                            @if($session->completed_at)<div class="small text-muted">{{ $session->completed_at->format('d M, h:i A') }}</div>@endif
                        </td> -->
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $session]) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="ti ti-eye"></i> Open
                                </a>
                                @if($session->status !== \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                                @endif
                                @can('consultations.create')
                                @if(in_array($session->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-primary" type="submit">Start</button>
                                    </form>
                                @endif
                                @if($session->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-success" type="submit" data-confirm="{{ __('consultations.workspace.complete_current_session') }}">{{ __('consultations.workspace.complete_current_session') }}</button>
                                    </form>
                                @endif
                                @if(in_array($session->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                    <form method="POST" action="{{ route('admin.consultations.routes.cancel', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-outline-danger" type="submit" data-confirm="Cancel this queued session?">Cancel</button>
                                    </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6"><x-empty-state message="No consultation sessions routed for this visit." /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CURRENT SESSION HEADER --}}
{{-- ============================================================ --}}
<!-- <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-primary"></i>{{ __('consultations.workspace.current_session') }}</h6>
            {{-- <small class="text-muted">Visit {{ $visit->visit_number }} · {{ $visit->patient->full_name }}</small> --}}
            <div class="fw-semibold ms-2">
                {{ $selectedRouteLabel }}
                @if($isEmergencyRoute)
                    <span class="badge bg-danger ms-1">{{ __('consultations.workspace.emergency') }}</span>
                @endif
            </div>
        </div>
            {{-- <div class="session-summary-item">
                <div class="text-muted small">Department</div>
                <div class="fw-semibold">{{ $selectedRoute?->department?->name ?? 'No active session' }}</div>
            </div> --}}
            <div>
                <div class="text-muted small">{{ __('consultations.linked_services') }}</div>
                <div class="fw-semibold">{{ $selectedRouteServiceNames->implode(', ') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-muted small">{{ __('consultations.workspace.contributors') }}</div>
                {{-- <div class="fw-semibold">{{ $selectedRoute?->doctor ? 'Dr. '.$selectedRoute->doctor->full_name : 'Unassigned' }}</div> --}}
                <div class="small text-muted">{{ $contributors->isNotEmpty() ? $contributors->implode(', ') : __('consultations.workspace.no_contributors_yet') }}</div>
            </div>

        <div class="d-flex flex-wrap gap-2">
            {{-- <x-status-badge :status="$visit->status" />
            @if($selectedRoute)
                <span class="badge bg-{{ $routeBadge($selectedRoute->status) }}">{{ $selectedRoute->status }}</span>
            @endif --}}
            @if($selectedRoute)
                @if(in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-player-play me-1"></i>{{ __('consultations.workspace.start_session') }}</button>
                    </form>
                    @endcan
                @endif
                @if($selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" data-confirm="{{ __('consultations.workspace.complete_session_confirm') }}">
                            <i class="ti ti-check me-1"></i>{{ __('consultations.workspace.complete_current_session') }}
                        </button>
                    </form>
                    @endcan
                @endif
            @endif
        </div>
    </div>
    {{-- <div class="card-body"> --}}
        {{-- @if($routeSelectorRequired)
            <div class="alert alert-warning py-2 mb-3">
                <strong>Select a consultation session.</strong>
                This visit has multiple consultation routes and none is active yet.
            </div>
        @endif --}}
        {{-- <div class="session-summary-grid">
            <div class="session-summary-item">
                <div class="text-muted small">Doctor</div>
                <div class="fw-semibold">{{ $selectedRoute?->doctor ? 'Dr. ' . $selectedRoute->doctor->full_name : 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Medical Record</div>
                <div class="fw-semibold">{{ $record ? 'MR-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT) : '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Visit Type</div>
                <div class="fw-semibold">{{ $visit->visit_type?->translatedLabel() ?? '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Insurance</div>
                <div class="fw-semibold">{{ $insuranceLabel }}</div>
            </div>
        </div> --}}
        {{-- <div class="d-flex flex-wrap gap-2 mt-3">
            @if($selectedRoute)
                @if(in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-player-play me-1"></i>Start Session</button>
                    </form>
                    @endcan
                @endif
                @if($selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" data-confirm="Complete this consultation session?">
                            <i class="ti ti-check me-1"></i>Complete Current Session
                        </button>
                    </form>
                    @endcan
                @endif
            @endif
            @can('consultations.create')
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendSessionModal">
                <i class="ti ti-transfer me-1"></i>Send to Another Session
            </button>
            @endcan
        </div> --}}
    {{-- </div> --}}
</div> -->

@if($isEmergencyRoute && $emergencyCase)
<div class="card mb-3 border-danger-subtle">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="ti ti-urgent me-1 text-danger"></i>Emergency Department Session</h6>
            <small class="text-muted">{{ $emergencyCase->emergency_number }} · {{ $emergencyCase->arrival_time?->format('d M Y, h:i A') ?? $emergencyCase->created_at?->format('d M Y, h:i A') }}</small>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <span class="badge bg-danger">{{ str_replace('_', ' ', $emergencyCase->emergency_status) }}</span>
            @if($emergencyCase->current_triage_category)
                <span class="badge {{ $emergencyCase->triage_badge_class }}">{{ $emergencyCase->current_triage_category }}</span>
            @endif
            @if($emergencyReadOnly)
                <span class="badge bg-secondary">Read only</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($emergencyReadOnly)
            <div class="alert alert-secondary py-2 small mb-3">
                This emergency session has been disposed or cancelled. Clinical details are shown as a completed session record.
            </div>
        @endif

        <div class="session-summary-grid mb-3">
            <div class="session-summary-item">
                <div class="text-muted small">Chief Complaint</div>
                <div class="fw-semibold">{{ $emergencyCase->chief_complaint ?: '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Main Doctor</div>
                <div class="fw-semibold">{{ $emergencySession?->mainDoctor?->full_name ?? $emergencyCase->assignedDoctor?->full_name ?? 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Primary Nurse</div>
                <div class="fw-semibold">{{ $emergencySession?->primaryNurse?->full_name ?? $emergencyCase->assignedNurse?->full_name ?? 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Medical Record</div>
                <div class="fw-semibold">{{ $emergencySession?->medical_record_id ? 'MR-'.str_pad((string) $emergencySession->medical_record_id, 5, '0', STR_PAD_LEFT) : '-' }}</div>
            </div>
        </div>

        <div class="row g-3">
            {{-- <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-heartbeat me-1 text-danger"></i>Triage & Vitals</div>
                    @php $latestEmergencyVitals = $emergencyCase->latestVitals; @endphp
                    <div class="small text-muted mb-1">Triaged by {{ $emergencyCase->triagedBy?->full_name ?? '-' }}</div>
                    <div class="small mb-2">{{ $emergencyCase->triage_notes ?: 'No triage notes recorded.' }}</div>
                    @if($latestEmergencyVitals)
                        <div class="d-flex flex-wrap gap-1 small">
                            @if($latestEmergencyVitals->blood_pressure)<span class="badge bg-light text-dark">BP {{ $latestEmergencyVitals->blood_pressure }}</span>@endif
                            @if($latestEmergencyVitals->heart_rate)<span class="badge bg-light text-dark">HR {{ $latestEmergencyVitals->heart_rate }}</span>@endif
                            @if($latestEmergencyVitals->respiratory_rate)<span class="badge bg-light text-dark">RR {{ $latestEmergencyVitals->respiratory_rate }}</span>@endif
                            @if($latestEmergencyVitals->temperature)<span class="badge bg-light text-dark">Temp {{ $latestEmergencyVitals->temperature }}</span>@endif
                            @if($latestEmergencyVitals->spo2)<span class="badge bg-light text-dark">SpO2 {{ $latestEmergencyVitals->spo2 }}%</span>@endif
                        </div>
                    @endif
                </div>
            </div> --}}
            <div class="col-lg-12">
                <div class="border rounded p-2 h-100">
                    <div class="d-flex fw-semibold small mb-2"><i class="ti ti-notes me-1 text-primary"></i>Emergency Notes</div>
                    <div class="row g-2">
                        @forelse($emergencyCase->notes->take(3) as $note)
                        <div class="col-lg-6">
                            <div class="small border-bottom pb-1 mb-1 h-100">
                                <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $note->note_type) }}</span>
                                {{ Str::limit($note->content, 90) }}
                                <div class="text-muted">{{ $note->creator?->full_name ?? 'Unknown user' }} · {{ $note->created_at?->format('d M, h:i A') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 small text-muted">No emergency notes recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-pill me-1 text-success"></i>Medication / MAR</div>
                    @forelse($emergencyCase->medicationOrders->take(4) as $order)
                        <div class="small border-bottom pb-1 mb-1">
                            <div class="fw-semibold">{{ $order->display_name }}</div>
                            <div class="text-muted">{{ trim(($order->dose ?: '').' '.($order->dose_unit ?: '').' '.($order->route ?: '')) ?: '-' }} · <x-status-badge :status="$order->status" domain="med_order" size="sm" /></div>
                        </div>
                    @empty
                        <div class="small text-muted">No emergency medications ordered.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-microscope me-1 text-info"></i>Investigations</div>
                    @forelse($emergencyCase->labRequests->take(4) as $request)
                        <div class="small">{{ $request->items->map(fn($item) => $item->display_name ?? $item->name)->filter()->implode(', ') ?: $request->request_number }} <x-status-badge :status="$request->status" domain="lab" size="sm" /></div>
                    @empty
                        <div class="small text-muted">No investigations requested.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-activity me-1 text-warning"></i>Procedures</div>
                    @forelse($emergencyCase->procedureRequests->take(4) as $procedure)
                        <div class="small">{{ $procedure->service?->name ?? $procedure->procedure?->name ?? 'Procedure request' }} <span class="text-muted">{{ $procedure->status?->translatedLabel() ?? $procedure->status }}</span></div>
                    @empty
                        <div class="small text-muted">No procedures requested.</div>
                    @endforelse
                </div>
            </div>
            {{-- <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-package me-1 text-secondary"></i>Consumables</div>
                    @forelse($emergencyCase->consumableUsages->take(4) as $usage)
                        <div class="small">{{ $usage->product?->name ?? 'Consumable' }} x {{ (float) $usage->quantity_used }} @if($usage->invoice_item_id)<span class="badge bg-success-subtle text-success">Billed</span>@endif</div>
                    @empty
                        <div class="small text-muted">No consumables used.</div>
                    @endforelse
                </div>
            </div> --}}
        </div>

        <div class="mt-3">
            <a href="{{ route('admin.emergency.cases.show', $emergencyCase) }}" class="btn btn-sm btn-outline-danger">
                <i class="ti ti-external-link me-1"></i>Open Emergency Control Sheet
            </a>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- VITALS — STATIC SECTION (always visible) --}}
{{-- ============================================================ --}}
<div class="card mb-3 vitals-static">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 small"><i class="ti ti-heartbeat me-1 text-danger"></i>{{ __('consultations.latest_vitals') }}</h6>
            @php
                $triageScore = $visit->triage_score;
                if (!$triageScore && $vitals->count() > 0) {
                    $lv = $vitals->first();
                    $triageScore = \App\Enums\TriageScore::compute([
                        'temperature'      => $lv->temperature,
                        'heart_rate'       => $lv->heart_rate,
                        'respiratory_rate' => $lv->respiratory_rate,
                        'spo2'             => $lv->spo2,
                    ]);
                }
            @endphp
            @if($triageScore)
                <span class="badge bg-{{ $triageScore->color() }} triage-badge">
                    <i class="ti {{ $triageScore->icon() }} me-1"></i>{{ $triageScore->translatedLabel() }}
                </span>
            @else
                <span class="badge bg-secondary triage-badge"><i class="ti ti-help me-1"></i>{{ __('consultations.workspace.triage_not_available') }}</span>
            @endif
        </div>

        @if($vitals->count() > 0)
            @php $lv = $vitals->first(); @endphp
            <div class="row g-2">
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">{{ __('consultations.workspace.blood_pressure') }}</div>
                    <div class="vitals-val">{{ $lv->blood_pressure ?? '—' }}</div>
                    <div class="vitals-label">mmHg</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">{{ __('consultations.workspace.heart_rate') }}</div>
                    <div class="vitals-val">{{ $lv->heart_rate ?? '—' }}</div>
                    <div class="vitals-label">bpm</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">{{ __('consultations.workspace.temperature') }}</div>
                    <div class="vitals-val">{{ $lv->temperature ?? '—' }}</div>
                    <div class="vitals-label">°C</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">SpO₂</div>
                    <div class="vitals-val">{{ $lv->spo2 ?? '—' }}</div>
                    <div class="vitals-label">%</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">{{ __('consultations.workspace.respiratory_rate_short') }}</div>
                    <div class="vitals-val">{{ $lv->respiratory_rate ?? '—' }}</div>
                    <div class="vitals-label">/min</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">{{ __('consultations.workspace.bmi') }}</div>
                    <div class="vitals-val {{ $lv->bmi ? ($lv->bmi < 18.5 ? 'text-warning' : ($lv->bmi < 25 ? 'text-success' : ($lv->bmi < 30 ? 'text-warning' : 'text-danger'))) : '' }}">
                        {{ $lv->bmi ?? '—' }}
                    </div>
                    <div class="vitals-label">
                        @if($lv->bmi)
                            @if($lv->bmi < 18.5) {{ __('consultations.workspace.underweight') }}
                            @elseif($lv->bmi < 25) {{ __('consultations.workspace.normal') }}
                            @elseif($lv->bmi < 30) {{ __('consultations.workspace.overweight') }}
                            @else {{ __('consultations.workspace.obese') }} @endif
                        @else kg/m² @endif
                    </div>
                </div>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem">
                <i class="ti ti-clock me-1"></i>{{ __('consultations.workspace.recorded_by', ['time' => $lv->recorded_at->diffForHumans(), 'name' => $lv->recordedBy?->full_name ?? __('consultations.workspace.unknown')]) }}
                @if($vitals->count() > 1)
                    &middot; <span class="text-primary">{{ trans_choice('consultations.workspace.earlier_readings', $vitals->count() - 1, ['count' => $vitals->count() - 1]) }}</span>
                @endif
            </div>
        @else
            <div class="text-muted small py-1">
                <i class="ti ti-heartbeat me-1"></i>{{ __('consultations.workspace.no_vitals') }}
                @can('vitals.create')
                    <a href="{{ route('admin.vitals.create', ['visit_id' => $visit->id]) }}" class="ms-2">{{ __('consultations.workspace.record_now') }}</a>
                @endcan
            </div>
        @endif
    </div>
</div>


