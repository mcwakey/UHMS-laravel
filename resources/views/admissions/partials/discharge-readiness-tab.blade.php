@php
    $areas = $dischargeReadiness['areas'] ?? collect();
    $summary = $dischargeReadiness['summary'] ?? null;
    $enforcement = $dischargeReadiness['enforcement'] ?? [];
    $overall = $dischargeReadiness['overall_status'] ?? \App\Enums\AdmissionDischargeReadinessStatus::WARNING;
    $summaryRoute = $summary
        ? $workspaceRoutes->route('admin.admissions.discharge-summary.update', $admission)
        : $workspaceRoutes->route('admin.admissions.discharge-summary.save', $admission);
    $prefill = $dischargeSummaryPrefill ?? [];
    $summaryValue = function (string $field) use ($summary, $prefill) {
        $value = $summary?->{$field};
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->implode("\n");
        } elseif (is_array($value)) {
            $value = collect($value)->implode("\n");
        }

        if (filled($value)) {
            return $value;
        }

        $default = $prefill[$field] ?? null;
        if ($default instanceof \Illuminate\Support\Collection) {
            return $default->implode("\n");
        }
        if (is_array($default)) {
            return collect($default)->implode("\n");
        }

        return $default;
    };
@endphp

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-calendar-time me-1"></i>{{ __('admissions.discharge_planning') }}</h5></div>
            <div class="card-body">
                @can('admission.discharge.plan')
                <form method="POST" action="{{ $admission->discharge_planning_started_at ? $workspaceRoutes->route('admin.admissions.discharge-planning.update', $admission) : $workspaceRoutes->route('admin.admissions.discharge-planning.start', $admission) }}">
                    @csrf
                    @if($admission->discharge_planning_started_at) @method('PATCH') @endif
                    <div class="mb-2">
                        <label class="form-label small">{{ __('admissions.expected_discharge') }}</label>
                        <input type="datetime-local" name="expected_discharge_at" class="form-control form-control-sm" value="{{ old('expected_discharge_at', $admission->expected_discharge_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('admissions.discharge_planning_note') }}</label>
                        <textarea name="discharge_planning_note" rows="3" class="form-control form-control-sm">{{ old('discharge_planning_note', $admission->discharge_planning_note) }}</textarea>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ $admission->discharge_planning_started_at ? __('admissions.update_planning') : __('admissions.start_discharge_planning') }}</button>
                    </div>
                </form>
                @else
                    <p class="text-muted mb-0">{{ $admission->discharge_planning_note ?: __('admissions.no_discharge_plan') }}</p>
                @endcan
            </div>
        </div>

        @can('admission.discharge.summary.view')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-file-description me-1"></i>{{ __('admissions.structured_discharge_summary') }}</h5>
                @if($summary)
                    <div class="d-flex gap-1">
                        <span class="badge bg-{{ $summary->summary_status?->color() }}">{{ $summary->summary_status?->label() }}</span>
                        <a href="{{ $workspaceRoutes->route('admin.admissions.discharge-summary.print', [$admission, $summary]) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="ti ti-printer me-1"></i>{{ __('admissions.print_summary') }}</a>
                    </div>
                @endif
            </div>
            <div class="card-body">
                @php $canEditSummary = auth()->user()?->can($summary ? 'admission.discharge.summary.update' : 'admission.discharge.summary.create') && ! ($summary?->summary_status?->isLocked() ?? false); @endphp
                @if($canEditSummary)
                <form method="POST" action="{{ $summaryRoute }}">
                    @csrf
                    @if($summary) @method('PATCH') @endif
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('admissions.primary_diagnosis') }}</label>
                            <input name="primary_diagnosis" class="form-control form-control-sm" value="{{ old('primary_diagnosis', $summaryValue('primary_diagnosis') ?? $admission->admitting_diagnosis) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('admissions.discharge_condition') }}</label>
                            <input name="discharge_condition" class="form-control form-control-sm" value="{{ old('discharge_condition', $summaryValue('discharge_condition')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('admissions.secondary_diagnoses') }}</label>
                            <textarea name="secondary_diagnoses" rows="2" class="form-control form-control-sm">{{ old('secondary_diagnoses', $summaryValue('secondary_diagnoses')) }}</textarea>
                        </div>
                        @foreach([
                            'admission_reason' => __('admissions.admission_reason'),
                            'hospital_course' => __('admissions.hospital_course'),
                            'investigations_summary' => __('admissions.investigations_summary'),
                            'procedures_summary' => __('admissions.procedures_summary'),
                            'treatment_given' => __('admissions.treatment_given'),
                            'discharge_medications' => __('admissions.discharge_medications'),
                            'follow_up_instructions' => __('admissions.follow_up_instructions'),
                            'warning_signs' => __('admissions.warning_signs'),
                        ] as $field => $label)
                            <div class="col-12">
                                <label class="form-label small">{{ $label }}</label>
                                <textarea name="{{ $field }}" rows="2" class="form-control form-control-sm">{{ old($field, $summaryValue($field)) }}</textarea>
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('admissions.follow_up_date') }}</label>
                            <input type="date" name="follow_up_date" class="form-control form-control-sm" value="{{ old('follow_up_date', $summary?->follow_up_date?->format('Y-m-d') ?? $summaryValue('follow_up_date')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('admissions.final_outcome') }}</label>
                            <input name="final_outcome" class="form-control form-control-sm" value="{{ old('final_outcome', $summaryValue('final_outcome')) }}">
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_summary') }}</button>
                    </div>
                </form>
                @elseif($summary)
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('admissions.primary_diagnosis') }}</dt><dd class="col-sm-8">{{ $summary->primary_diagnosis ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('admissions.discharge_condition') }}</dt><dd class="col-sm-8">{{ $summary->discharge_condition ?: '—' }}</dd>
                        <dt class="col-sm-4">{{ __('admissions.follow_up_date') }}</dt><dd class="col-sm-8">{{ $summary->follow_up_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-sm-4">{{ __('admissions.follow_up_instructions') }}</dt><dd class="col-sm-8">{{ $summary->follow_up_instructions ?: '—' }}</dd>
                    </dl>
                @else
                    <div class="text-center py-4 text-muted">{{ __('admissions.missing_summary') }}</div>
                @endif

                @if($summary)
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        @can('admission.discharge.summary.update')
                        @if(! $summary->summary_status?->isLocked())
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.discharge-summary.prepare', [$admission, $summary]) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-info">{{ __('admissions.mark_prepared') }}</button>
                        </form>
                        @endif
                        @endcan
                        @can('admission.discharge.summary.approve')
                        @if(! $summary->summary_status?->isApproved())
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.discharge-summary.approve', [$admission, $summary]) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-success">{{ __('admissions.approve_summary') }}</button>
                        </form>
                        @endif
                        @endcan
                    </div>
                @endif
            </div>
        </div>
        @endcan
    </div>

    <div class="col-xl-6">
        <div class="card mb-3 border-{{ $overall->color() }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('admissions.discharge_readiness') }}</h5>
                <span class="badge bg-{{ $overall->color() }}">{{ $overall->label() }}</span>
            </div>
            <div class="card-body">
                <div class="alert alert-{{ ($enforcement['enabled'] ?? false) ? 'warning' : 'info' }} py-2">
                    {{ ($enforcement['enabled'] ?? false) ? __('admissions.enforcement_enabled') : __('admissions.enforcement_disabled') }}
                </div>

                @if(($enforcement['blockers'] ?? collect())->isNotEmpty())
                    <div class="alert alert-danger py-2">
                        <strong>{{ __('admissions.blockers') }}:</strong>
                        {{ $enforcement['blockers']->implode(' ') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tr>
                            <td class="text-muted">{{ __('admissions.discharge_planning') }}</td>
                            <td>
                                @if($dischargeReadiness['planning_started'] ?? false)
                                    <span class="badge badge-soft-success">{{ __('admissions.started') }}</span>
                                    <small class="text-muted">{{ $admission->discharge_planning_started_at?->format('d M Y H:i') }}</small>
                                @else
                                    <span class="badge badge-soft-warning">{{ __('admissions.not_started') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('admissions.expected_discharge') }}</td>
                            <td>{{ $admission->expected_discharge_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('admissions.billing_balance') }}</td>
                            <td>
                                @if(($dischargeReadiness['invoice_balance'] ?? null) !== null)
                                    GH&#8373; {{ number_format($dischargeReadiness['invoice_balance'], 2) }}
                                @else
                                    {{ __('admissions.unavailable') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('admissions.summary_status') }}</td>
                            <td>
                                @if($summary)
                                    <span class="badge bg-{{ $summary->summary_status?->color() }}">{{ $summary->summary_status?->label() }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('admissions.missing_summary') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        @can('admission.discharge.clearance.view')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-list-check me-1"></i>{{ __('admissions.discharge_clearance') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('admissions.clearance') }}</th><th>{{ __('admissions.readiness') }}</th><th>{{ __('admissions.status_col') }}</th><th>{{ __('admissions.by_col') }}</th><th class="text-end">{{ __('admissions.actions') }}</th></tr></thead>
                        <tbody>
                            @foreach($areas as $key => $area)
                                @php $clearance = $area['clearance']; @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $area['label'] }}</div>
                                        <small class="text-muted">{{ $area['message'] }}</small>
                                    </td>
                                    <td><span class="badge bg-{{ $area['status']->color() }}">{{ $area['status']->label() }}</span></td>
                                    <td><span class="badge badge-soft-{{ $clearance?->status?->color() ?? 'warning' }}">{{ $clearance?->status?->label() ?? __('admissions.pending') }}</span></td>
                                    <td>
                                        @if($clearance?->clearedBy)
                                            {{ $clearance->clearedBy->name }}<br><small class="text-muted">{{ $clearance->cleared_at?->format('d M H:i') }}</small>
                                        @elseif($clearance?->revokedBy)
                                            {{ $clearance->revokedBy->name }}<br><small class="text-muted">{{ $clearance->revoked_at?->format('d M H:i') }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @can('admission.discharge.clearance.manage')
                                        @if($clearance)
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.discharge-clearances.update', [$admission, $clearance]) }}" class="d-inline-flex gap-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="{{ \App\Enums\AdmissionDischargeClearanceStatus::CLEARED->value }}">
                                                <input type="hidden" name="note" value="">
                                                <button class="btn btn-sm btn-outline-success" title="{{ __('admissions.clear_item') }}"><i class="ti ti-check"></i></button>
                                            </form>
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.discharge-clearances.update', [$admission, $clearance]) }}" class="d-inline-flex gap-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="{{ \App\Enums\AdmissionDischargeClearanceStatus::BLOCKED->value }}">
                                                <input type="hidden" name="note" value="{{ __('admissions.blocked_without_note') }}">
                                                <button class="btn btn-sm btn-outline-danger" title="{{ __('admissions.block_item') }}"><i class="ti ti-alert-triangle"></i></button>
                                            </form>
                                            @if($clearance->status?->isReady())
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.discharge-clearances.revoke', [$admission, $clearance]) }}" class="d-inline-flex gap-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="note" value="{{ __('admissions.revoked_from_workspace') }}">
                                                <button class="btn btn-sm btn-outline-secondary" title="{{ __('admissions.revoke_clearance') }}"><i class="ti ti-rotate-2"></i></button>
                                            </form>
                                            @endif
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endcan
    </div>
</div>
