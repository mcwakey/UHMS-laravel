@php
    use Illuminate\Support\Str;
    $sevVariant = fn ($s) => $s === 'critical' ? 'danger' : 'warning';
    $slaVariant = fn ($s) => match ($s) { 'critical_breach', 'breached' => 'danger', 'near_breach' => 'warning', default => 'success' };
    $stVariant = fn ($s) => ['actionable' => 'success', 'blocked' => 'danger', 'open' => 'secondary', 'resolved' => 'secondary'][$s] ?? 'secondary';
    $asgVariant = fn ($s) => ['assigned' => 'info', 'acknowledged' => 'success'][$s] ?? 'secondary';
    $escVariant = fn ($s) => match ($s) { 'critical', 'supervisor' => 'danger', 'warning' => 'warning', default => 'secondary' };
    $deptLabel = fn ($name, $type) => $name ?: ($type ? Str::headline($type) : '—');
    $canAct = fn ($type) => ($canActByType[$type] ?? false);
    $isHandoffTab = $tab !== 'my_actions';
    $showRisk = ($canPredict ?? false) && ($predictions ?? collect())->isNotEmpty();
    $handoffCols = $showRisk ? 10 : 9;
@endphp

{{-- Summary --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-md"><div class="card border-start border-primary border-3 shadow-sm h-100"><div class="card-body py-2">
        <div class="text-muted small text-uppercase">{{ __('journey.worklist.total') }}</div>
        <div class="h5 fw-bold mb-0">{{ $actionSummary['total'] }}</div>
    </div></div></div>
    <div class="col-6 col-md"><div class="card border-start border-info border-3 shadow-sm h-100"><div class="card-body py-2">
        <div class="text-muted small text-uppercase">{{ __('journey.handoff.owed_by') }}</div>
        <div class="h5 fw-bold mb-0">{{ $handoffSummary['owed_by'] }}</div>
    </div></div></div>
    <div class="col-6 col-md"><div class="card border-start border-warning border-3 shadow-sm h-100"><div class="card-body py-2">
        <div class="text-muted small text-uppercase">{{ __('journey.handoff.owed_to') }}</div>
        <div class="h5 fw-bold mb-0">{{ $handoffSummary['owed_to'] }}</div>
    </div></div></div>
    <div class="col-6 col-md"><div class="card border-start border-danger border-3 shadow-sm h-100"><div class="card-body py-2">
        <div class="text-muted small text-uppercase">{{ __('journey.sla.breached') }}</div>
        <div class="h5 fw-bold mb-0 text-danger">{{ $handoffSummary['breached'] }}</div>
    </div></div></div>
    <div class="col-6 col-md"><div class="card border-start border-danger border-3 shadow-sm h-100"><div class="card-body py-2">
        <div class="text-muted small text-uppercase">{{ __('journey.escalation.critical') }}</div>
        <div class="h5 fw-bold mb-0 text-danger">{{ $handoffSummary['critical_escalations'] ?? 0 }}</div>
    </div></div></div>
</div>

{{-- Handoff hotspots matrix --}}
@if(!empty($matrix))
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 fw-bold"><i class="ti ti-arrows-transfer-down me-1"></i>{{ __('journey.handoff.matrix_title') }}</div>
        <div class="card-body d-flex flex-wrap gap-2 py-2">
            @foreach(array_slice($matrix, 0, 8) as $cell)
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 p-2">
                    <strong>{{ Str::headline($cell['from']) }}</strong><i class="ti ti-arrow-right text-muted"></i><strong>{{ Str::headline($cell['to']) }}</strong>
                    <span class="ms-1">{{ __('journey.handoff.matrix_actions', ['count' => $cell['count']]) }}</span>
                    @if($cell['breached'] > 0)<span class="badge bg-danger">{{ __('journey.handoff.matrix_breached', ['count' => $cell['breached']]) }}</span>@endif
                </span>
            @endforeach
        </div>
    </div>
@endif

{{-- Table --}}
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('journey.worklist.col_patient') }}</th>
                    <th>{{ __('journey.worklist.col_visit') }}</th>
                    @if($isHandoffTab)
                        <th>{{ __('journey.handoff.col_from') }}</th>
                        <th>{{ __('journey.handoff.col_to') }}</th>
                    @else
                        <th>{{ __('journey.worklist.col_stage') }}</th>
                        <th>{{ __('journey.worklist.col_owner') }}</th>
                    @endif
                    <th>{{ __('journey.worklist.col_cause') }}</th>
                    @if($isHandoffTab)
                        <th>{{ __('journey.sla.label') }}</th>
                        @if($showRisk)<th>{{ __('journey.risk.col_risk') }}</th>@endif
                        <th>{{ __('journey.assignment.col_status') }}</th>
                        <th>{{ __('journey.escalation.label') }}</th>
                    @else
                        <th>{{ __('journey.worklist.col_severity') }}</th>
                    @endif
                    <th>{{ __('journey.worklist.col_waiting') }}</th>
                    <th>{{ __('journey.worklist.col_action') }}</th>
                </tr>
            </thead>
            <tbody>
                @if($isHandoffTab)
                    @forelse($handoffs as $h)
                        <tr>
                            <td class="fw-medium">{{ $h->patientName }}</td>
                            <td><span class="text-muted">{{ $h->visitNumber }}</span></td>
                            <td>{{ $deptLabel($h->fromDepartmentName, $h->fromDepartmentType) }}</td>
                            <td><i class="ti ti-arrow-right text-muted me-1"></i>{{ $deptLabel($h->toDepartmentName, $h->toDepartmentType) }}</td>
                            <td><i class="ti {{ $h->cause->icon() }} me-1 text-{{ $sevVariant($h->severity) }}"></i>{{ $h->cause->translatedLabel() }}</td>
                            <td>
                                <span class="badge bg-{{ $slaVariant($h->slaStatus) }}">{{ __('journey.sla.'.$h->slaStatus) }}</span>
                                <div class="small text-muted">
                                    @if($h->minutesToBreach !== null && $h->minutesToBreach < 0){{ __('journey.sla.overdue', ['minutes' => abs($h->minutesToBreach)]) }}
                                    @elseif($h->minutesToBreach !== null){{ __('journey.sla.to_breach', ['minutes' => $h->minutesToBreach]) }}@endif
                                </div>
                            </td>
                            @if($showRisk)
                                @php $pred = ($predictions ?? collect())->get($h->visitId); @endphp
                                <td style="min-width: 180px;">
                                    @if($pred)
                                        <span class="badge bg-{{ $pred->riskLevel->color() }}"><i class="ti {{ $pred->riskLevel->icon() }} me-1"></i>{{ $pred->riskLevel->translatedLabel() }} · {{ $pred->riskScore }}</span>
                                        <div class="small text-muted">
                                            @if($pred->estimatedRemainingMinutes !== null){{ __('journey.risk.eta.remaining', ['minutes' => $pred->estimatedRemainingMinutes]) }} · @endif{{ __('journey.risk.confidence_level.'.$pred->confidence) }}
                                        </div>
                                        <div class="small text-muted fst-italic">{{ $pred->riskReason }}</div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="badge bg-{{ $asgVariant($h->assignmentStatus) }}-subtle text-{{ $asgVariant($h->assignmentStatus) }}">{{ __('journey.assignment.status.'.$h->assignmentStatus) }}</span>
                                @if($h->assignedAt && $h->assignedAt->gt(now()->subMinutes(15)))<span class="badge bg-info">{{ __('journey.notification.new') }}</span>@endif
                                @if($h->assignedToName)<div class="small text-muted">{{ $h->assignedToName }}</div>@endif
                            </td>
                            <td>
                                @if($h->escalationLevel !== 'none')
                                    <span class="badge bg-{{ $escVariant($h->escalationLevel) }}"><i class="ti ti-alert-triangle me-1"></i>{{ __('journey.escalation.'.$h->escalationLevel) }}</span>
                                    @if($h->lastEscalatedAt && $h->lastEscalatedAt->gt(now()->subMinutes(15)))<span class="badge bg-warning text-dark">{{ __('journey.notification.new') }}</span>@endif
                                @else
                                    <span class="small text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ intdiv($h->elapsedMinutes, 60) }}h {{ $h->elapsedMinutes % 60 }}m</td>
                            <td style="min-width: 260px;">
                                <div class="small mb-1">{{ $h->actionLabel }}</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($h->actionUrl)
                                        <a href="{{ $workspaceRoutes->visitActionUrl($h->visitId, $h->actionUrl) }}" class="btn btn-sm btn-outline-primary" title="{{ __('journey.worklist.open_action') }}"><i class="ti ti-external-link"></i></a>
                                    @endif
                                    @if($h->isUnassigned() && $canAct($h->toDepartmentType))
                                        <form method="POST" action="{{ route($workspaceRoutes->handoffRouteName('claim')) }}" class="d-inline">@csrf
                                            <input type="hidden" name="visit_id" value="{{ $h->visitId }}"><input type="hidden" name="cause" value="{{ $h->cause->value }}">
                                            <button class="btn btn-sm btn-primary">{{ __('journey.assignment.claim') }}</button>
                                        </form>
                                    @endif
                                    @if($canAct($h->toDepartmentType) && !empty($assignableByType[$h->toDepartmentType] ?? null))
                                        <form method="POST" action="{{ route($workspaceRoutes->handoffRouteName('assign')) }}" class="d-inline-flex gap-1">@csrf
                                            <input type="hidden" name="visit_id" value="{{ $h->visitId }}"><input type="hidden" name="cause" value="{{ $h->cause->value }}">
                                            <select name="assignee_id" class="form-select form-select-sm" style="max-width: 150px;" required>
                                                <option value="">{{ __('journey.assignment.choose_assignee') }}</option>
                                                @foreach($assignableByType[$h->toDepartmentType] as $candidate)
                                                    <option value="{{ $candidate->id }}">{{ $candidate->full_name ?? ($candidate->first_name.' '.$candidate->last_name) }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-outline-secondary">{{ __('journey.assignment.assign') }}</button>
                                        </form>
                                    @endif
                                    @if($h->assignmentId && $h->assignmentStatus === 'assigned' && ($h->assignedToUserId === ($currentUserId ?? null) || $canAct($h->toDepartmentType)))
                                        <form method="POST" action="{{ route($workspaceRoutes->handoffRouteName('acknowledge'), $h->assignmentId) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-outline-success">{{ __('journey.assignment.acknowledge') }}</button>
                                        </form>
                                    @endif
                                    @if($h->assignmentId && ($h->assignedToUserId === ($currentUserId ?? null) || $canAct($h->toDepartmentType)))
                                        <form method="POST" action="{{ route($workspaceRoutes->handoffRouteName('resolve'), $h->assignmentId) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-success">{{ __('journey.assignment.resolve') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $handoffCols }}" class="text-center text-muted py-5">
                            <i class="ti ti-circle-check fs-1 d-block mb-2 text-success"></i>
                            {{ $tab === 'sla_breaches' ? __('journey.handoff.no_sla_breaches') : __('journey.handoff.no_handoffs') }}
                        </td></tr>
                    @endforelse
                @else
                    @forelse($actions as $action)
                        <tr>
                            <td class="fw-medium">{{ $action->patientName }}</td>
                            <td><span class="text-muted">{{ $action->visitNumber }}</span></td>
                            <td>@if($action->stage)<i class="ti {{ $action->stage->icon() }} me-1"></i>{{ $action->stage->translatedLabel() }}@endif</td>
                            <td>{{ $deptLabel($action->ownerDepartmentName, $action->ownerType) }}</td>
                            <td><i class="ti {{ $action->cause->icon() }} me-1 text-{{ $sevVariant($action->severity) }}"></i>{{ $action->cause->translatedLabel() }}</td>
                            <td><span class="badge bg-{{ $sevVariant($action->severity) }}">{{ __('journey.status.'.$action->severity) }}</span></td>
                            <td>{{ intdiv($action->elapsedMinutes, 60) }}h {{ $action->elapsedMinutes % 60 }}m</td>
                            <td style="min-width: 220px;">
                                <div class="small mb-1">{{ $action->actionLabel }}
                                    <span class="badge bg-{{ $stVariant($action->actionStatus) }}-subtle text-{{ $stVariant($action->actionStatus) }}">{{ __('journey.action_status.'.$action->actionStatus) }}</span>
                                </div>
                                @if($action->actionUrl)
                                    <a href="{{ $workspaceRoutes->visitActionUrl($action->visitId, $action->actionUrl) }}" class="btn btn-sm btn-primary"><i class="ti ti-arrow-right me-1"></i>{{ __('journey.worklist.open_action') }}</a>
                                @else
                                    <span class="small text-muted">{{ __('journey.worklist.unavailable') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">
                            <i class="ti ti-circle-check fs-1 d-block mb-2 text-success"></i>
                            {{ __('journey.worklist.empty') }}
                        </td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>
