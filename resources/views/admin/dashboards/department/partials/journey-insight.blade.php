{{-- Phase 9.2/9.4: optional patient-flow insight. Renders when the dashboard payload
     carries delayed patients OR a cross-department handoff balance (set only for
     flow-relevant, permitted departments). --}}
@php
    $insight = $journey_insight ?? null;
    $handoff = $journey_handoff ?? null;
    $myAssignments = (int) ($journey_my_assignments ?? 0);
    $accuracy = $journey_prediction_accuracy ?? null;
    $hasInsight = ! empty($insight) && ($insight['delayed'] ?? 0) > 0;
    $hasHandoff = ! empty($handoff) && (($handoff['owed_by'] ?? 0) + ($handoff['owed_to'] ?? 0)) > 0;
    $variant = (($hasInsight && ($insight['severity'] ?? null) === 'critical') || ! empty($handoff['breached'])) ? 'danger' : 'warning';
@endphp
@if($hasInsight || $hasHandoff || $myAssignments > 0 || ! empty($accuracy))
<div class="alert alert-{{ $variant }} border-0 shadow-sm mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold d-flex align-items-center gap-1"><i class="ti ti-route"></i>{{ __('journey.insight.title') }}</span>
        @if($myAssignments > 0)
            <a href="{{ route('admin.journey.worklist', ['tab' => 'assigned_to_me']) }}" class="badge bg-info text-decoration-none">{{ __('journey.notification.assigned_to_you', ['count' => $myAssignments]) }}</a>
        @endif
        @if(!empty($accuracy))
            <a href="{{ route('admin.journey.analytics') }}" class="badge bg-success-subtle text-success text-decoration-none" title="{{ __('journey.accuracy.evaluated', ['count' => $accuracy['evaluated']]) }}">{{ __('journey.accuracy.chip', ['pct' => $accuracy['accuracy']]) }}</a>
        @endif
        @if($hasInsight)
            <span class="badge bg-{{ $variant }}-subtle text-{{ $variant }}">{{ __('journey.insight.delayed_patients', ['count' => $insight['delayed']]) }}</span>
            @if($insight['top_cause'])
                <span class="d-inline-flex align-items-center gap-1"><i class="ti {{ $insight['top_cause']->icon() }}"></i>{{ __('journey.bottleneck.top_cause') }}: <strong>{{ $insight['top_cause']->translatedLabel() }}</strong></span>
                <span class="text-muted small d-inline-flex align-items-center gap-1"><i class="ti ti-arrow-right"></i>{{ __('journey.insight.next_action') }}: {{ $insight['top_cause']->action() }}</span>
            @endif
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.journey.worklist'))
            <a href="{{ route('admin.journey.worklist', ['department_id' => $insight['department_id'] ?? null]) }}" class="btn btn-sm btn-{{ $variant }} ms-auto">
                <i class="ti ti-list-check me-1"></i>{{ __('journey.insight.view_worklist') }}
            </a>
        @endif
    </div>
    @if($hasHandoff)
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-2 border-top">
            <span class="d-inline-flex align-items-center gap-1"><i class="ti ti-arrow-bar-up text-info"></i>{{ __('journey.insight.owes', ['count' => $handoff['owed_by']]) }}</span>
            <span class="d-inline-flex align-items-center gap-1"><i class="ti ti-arrow-bar-down text-warning"></i>{{ __('journey.insight.waiting_on', ['count' => $handoff['owed_to']]) }}</span>
            @if(($handoff['breached'] ?? 0) > 0)
                <span class="badge bg-danger">{{ __('journey.insight.breached', ['count' => $handoff['breached']]) }}</span>
            @endif
            @if(($handoff['critical_escalations'] ?? 0) > 0)
                <span class="badge bg-danger"><i class="ti ti-alert-triangle me-1"></i>{{ __('journey.escalation.critical_count', ['count' => $handoff['critical_escalations']]) }}</span>
            @endif
            @if(($handoff['supervisor_escalations'] ?? 0) > 0)
                <span class="badge bg-warning text-dark">{{ __('journey.escalation.supervisor_count', ['count' => $handoff['supervisor_escalations']]) }}</span>
            @endif
            @if(!empty($handoff['supervisor_missing']) && (($handoff['critical_escalations'] ?? 0) > 0 || ($handoff['breached'] ?? 0) > 0))
                <span class="badge bg-secondary"><i class="ti ti-user-question me-1"></i>{{ __('journey.supervisor.missing') }}</span>
            @endif
            @php $riskLikely = (int) ($handoff['near_breach'] ?? 0) + (int) ($handoff['breached'] ?? 0); @endphp
            @if($riskLikely > 0)
                <span class="badge bg-warning text-dark" title="{{ __('journey.risk.estimates_note') }}"><i class="ti ti-chart-dots me-1"></i>{{ __('journey.risk.summary.likely', ['count' => $riskLikely]) }}</span>
            @endif
            @if($handoff['top_counterpart'])
                <span class="text-muted small">{{ __('journey.insight.top_counterpart', ['dept' => \Illuminate\Support\Str::headline($handoff['top_counterpart'])]) }}</span>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('admin.journey.worklist'))
                <span class="ms-auto d-flex gap-2">
                    <a href="{{ route('admin.journey.worklist', ['tab' => 'owed_by']) }}" class="btn btn-sm btn-outline-{{ $variant }}">{{ __('journey.handoff.tab_owed_by') }}</a>
                    <a href="{{ route('admin.journey.worklist', ['tab' => 'owed_to']) }}" class="btn btn-sm btn-outline-{{ $variant }}">{{ __('journey.handoff.tab_owed_to') }}</a>
                </span>
            @endif
        </div>
    @endif
</div>
@endif
