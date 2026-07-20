@php
    $tileBalance = $dischargeReadiness['invoice_balance'] ?? null;
    $medsDue = $medicationBoard['counts']['due_now'] ?? 0;
    $medsOverdue = $medicationBoard['counts']['overdue'] ?? 0;
    $openNursing = ($careOverview['open_tasks'] ?? collect())->count();
    $overdueNursing = ($careOverview['overdue_tasks'] ?? collect())->count();
    $latestVitals = $careOverview['latest_vitals'] ?? null;
    $vitalsOverdue = $careOverview['vitals_overdue'] ?? false;
    $drStatus = $dischargeReadiness['overall_status'] ?? null;
    $canViewBilling = auth()->user()?->can('invoices.view') ?? false;

    $medsClass = $medsOverdue > 0 ? 'crit' : ($medsDue > 0 ? 'warn' : 'ok');
    $nurseClass = $overdueNursing > 0 ? 'crit' : ($openNursing > 0 ? 'warn' : 'ok');
    $balClass = ($tileBalance ?? 0) > 0 ? 'crit' : 'ok';
    $summaryTasks = $medicalRecord?->tasks ?? collect();
    $recentNursingNotes = ($admission->nursingNotes ?? collect())->sortByDesc('observed_at')->take(3);
@endphp

<!-- <div class="adm-tiles mb-3">
    <button type="button" class="adm-tile adm-tile--primary" onclick="showTab('tab-simplified-overview')">
        <span class="adm-tile__lab"><i class="ti ti-layout-dashboard"></i>{{ __('admissions.tab_overview') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">{{ $admission->length_of_stay }}<span class="fs-13 text-muted"> {{ __('admissions.days') }}</span></span>
            <span class="adm-tile__sub">{{ $admission->bed->bed_type->label() }} - GH&#8373; {{ number_format($admission->bed->daily_rate, 2) }}</span>
        </div>
    </button>

    <button type="button" class="adm-tile adm-tile--{{ $vitalsOverdue ? 'crit' : 'info' }}" onclick="showTab('tab-simplified-vitals')">
        <span class="adm-tile__lab"><i class="ti ti-heartbeat"></i>{{ __('admissions.last_vitals') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val">{{ $latestVitals?->recorded_at?->diffForHumans() ?? __('admissions.not_recorded') }}</span>
            <span class="adm-tile__sub adm-num">{{ $admission->visit->vitals->count() }} {{ __('admissions.tab_vitals') }}</span>
        </div>
    </button>

    @can('admission.nursing.view')
    <button type="button" class="adm-tile adm-tile--{{ $nurseClass }}" onclick="showTab('tab-simplified-nursing')">
        <span class="adm-tile__lab"><i class="ti ti-clipboard-check"></i>{{ __('admissions.open_nursing_tasks') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">{{ $openNursing }}</span>
            <span class="adm-tile__sub adm-num">{{ $overdueNursing }} {{ __('admissions.overdue_label') }}</span>
        </div>
    </button>
    @endcan

    @can('admission.medication_board.view')
    <button type="button" class="adm-tile adm-tile--{{ $medsClass }}" onclick="showTab('tab-simplified-medications')">
        <span class="adm-tile__lab"><i class="ti ti-pill"></i>{{ __('admissions.medications_due') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">{{ $medsDue }}</span>
            <span class="adm-tile__sub adm-num">{{ $medsOverdue }} {{ __('admissions.overdue_badge') }}</span>
        </div>
    </button>
    @endcan

    <button type="button" class="adm-tile adm-tile--primary" onclick="showTab('tab-simplified-rounds')">
        <span class="adm-tile__lab"><i class="ti ti-notes"></i>{{ __('admissions.tab_ward_rounds') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">{{ $admission->wardRounds->count() }}</span>
            <span class="adm-tile__sub">{{ __('admissions.ward_rounds_history') }}</span>
        </div>
    </button>

    @can('admission.discharge.readiness.view')
    <button type="button" class="adm-tile adm-tile--{{ $drStatus?->color() === 'success' ? 'ok' : ($drStatus?->color() === 'danger' ? 'crit' : ($drStatus?->color() === 'info' ? 'info' : 'warn')) }}" onclick="showTab('tab-simplified-discharge')">
        <span class="adm-tile__lab"><i class="ti ti-shield-check"></i>{{ __('admissions.tab_discharge') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val">{{ $drStatus?->label() ?? '-' }}</span>
            <span class="adm-tile__sub">{{ __('admissions.discharge_readiness') }}</span>
        </div>
    </button>
    @endcan

    @if($canViewBilling)
    <button type="button" class="adm-tile adm-tile--{{ $balClass }}" onclick="showTab('tab-simplified-billing')">
        <span class="adm-tile__lab"><i class="ti ti-cash"></i>{{ __('admissions.billing_balance') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">@if($tileBalance !== null)GH&#8373; {{ number_format($tileBalance, 2) }}@else-@endif</span>
            <span class="adm-tile__sub">{{ ($tileBalance ?? 0) > 0 ? __('admissions.not_cleared') : __('admissions.cleared') }}</span>
        </div>
    </button>
    @endif
</div> -->

<!-- <ul class="nav nav-tabs mb-3" id="admSimplifiedTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-simplified-overview" type="button">
            <i class="ti ti-layout-dashboard me-1"></i>{{ __('admissions.tab_overview') }}
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-vitals" type="button">
            <i class="ti ti-heartbeat me-1"></i>{{ __('admissions.tab_vitals') }}
            <span class="badge bg-secondary ms-1">{{ $admission->visit->vitals->count() }}</span>
        </button>
    </li>
    @can('admission.nursing.view')
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-nursing" type="button">
            <i class="ti ti-report-medical me-1"></i>{{ __('admissions.tab_nursing') }}
            @if(($careOverview['overdue_tasks'] ?? collect())->isNotEmpty())
                <span class="badge bg-danger ms-1">{{ $careOverview['overdue_tasks']->count() }}</span>
            @elseif(($careOverview['open_tasks'] ?? collect())->isNotEmpty())
                <span class="badge bg-warning text-dark ms-1">{{ $careOverview['open_tasks']->count() }}</span>
            @endif
        </button>
    </li>
    @endcan
    @can('admission.medication_board.view')
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-medications" type="button">
            <i class="ti ti-pill me-1"></i>{{ __('admissions.tab_mar') }}
            @if($medsOverdue > 0)
                <span class="badge bg-danger ms-1">{{ $medsOverdue }}</span>
            @elseif($medsDue > 0)
                <span class="badge bg-info ms-1">{{ $medsDue }}</span>
            @endif
        </button>
    </li>
    @endcan
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-rounds" type="button">
            <i class="ti ti-notes me-1"></i>{{ __('admissions.tab_ward_rounds') }}
            <span class="badge bg-secondary ms-1">{{ $admission->wardRounds->count() }}</span>
        </button>
    </li>
    @if($summaryTasks->isNotEmpty())
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-consultation" type="button">
            <i class="ti ti-stethoscope me-1"></i>{{ __('admissions.tab_consultation') }}
            <span class="badge bg-warning text-dark ms-1">{{ $summaryTasks->whereNotIn('status', ['completed', 'cancelled'])->count() }}</span>
        </button>
    </li>
    @endif
    @if($canViewBilling)
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-billing" type="button">
            <i class="ti ti-file-invoice me-1"></i>{{ __('admissions.tab_billing') }}
            <span class="badge bg-secondary ms-1">{{ $admission->visit->latestInvoice?->items?->count() ?? 0 }}</span>
        </button>
    </li>
    @endif
    @can('admission.discharge.readiness.view')
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-simplified-discharge" type="button">
            <i class="ti ti-shield-check me-1"></i>{{ __('admissions.tab_discharge') }}
            <span class="badge bg-{{ $dischargeReadiness['overall_status']->color() }} ms-1">{{ $dischargeReadiness['overall_status']->label() }}</span>
        </button>
    </li>
    @endcan
</ul> -->

<!-- <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-simplified-overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-clipboard me-1"></i>{{ __('admissions.admission_details_card') }}</h5>
                        <span class="badge badge-soft-{{ $admission->status->color() }}">{{ $admission->status->label() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <div>
                                <div class="text-muted fs-12 mb-1">{{ __('admissions.admission_no') }}</div>
                                <div class="fw-semibold adm-num">{{ $admission->admission_number }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-12 mb-1">{{ __('admissions.location_card') }}</div>
                                <div class="fw-semibold">{{ $admission->bed->ward->name }} / {{ $admission->bed->bed_number }}</div>
                                <small class="text-muted">{{ $admission->bed->bed_type->label() }} - GH&#8373; {{ number_format($admission->bed->daily_rate, 2) }}</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted fs-12 mb-1">{{ __('admissions.admitted_on_label') }}</div>
                                    <div class="fw-semibold adm-num">{{ $admission->admission_date->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $admission->admittedBy->name ?? '-' }}</small>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fs-12 mb-1">{{ __('admissions.length_of_stay_label') }}</div>
                                    <div class="fw-semibold adm-num">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</div>
                                </div>
                            </div>
                            @if($admission->expected_discharge_date || $admission->actual_discharge_date)
                            <div class="row g-2">
                                @if($admission->expected_discharge_date)
                                <div class="col-6">
                                    <div class="text-muted fs-12 mb-1">{{ __('admissions.expected_discharge_label') }}</div>
                                    <div class="fw-semibold adm-num">{{ $admission->expected_discharge_date->format('d M Y') }}</div>
                                </div>
                                @endif
                                @if($admission->actual_discharge_date)
                                <div class="col-6">
                                    <div class="text-muted fs-12 mb-1">{{ __('admissions.discharged_on_label') }}</div>
                                    <div class="fw-semibold adm-num">{{ $admission->actual_discharge_date->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $admission->dischargedBy->name ?? '-' }}</small>
                                </div>
                                @endif
                            </div>
                            @endif
                            @if($admission->admitting_diagnosis)
                            <div class="alert alert-light border py-2 mb-0">
                                <div class="text-muted fs-12 mb-1"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.admitting_diagnosis_card') }}</div>
                                <div class="fs-13">{{ $admission->admitting_diagnosis }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h5 class="card-title mb-0"><i class="ti ti-clipboard-heart me-1"></i>{{ __('admissions.clinical_snapshot') }}</h5>
                        @if($medicalRecord)
                            <a href="{{ $workspaceRoutes->route('admin.consultations.show', $admission->visit) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-external-link me-1"></i>{{ __('admissions.open_full_consultation') }}
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($medicalRecord && ($medicalRecord->diagnoses->count() || $medicalRecord->complaints->count() || $medicalRecord->treatments->count() || $medicalRecord->prescriptions->count()))
                            <div class="row g-3">
                                @if($medicalRecord->diagnoses->count() > 0)
                                <div class="col-md-6">
                                    <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.diagnoses') }}</h6>
                                    @foreach($medicalRecord->diagnoses->take(4) as $diagnosis)
                                    <div class="ehr-item {{ $diagnosis->is_primary ? 'is-primary' : '' }}">
                                        @if($diagnosis->is_primary)
                                            <span class="badge bg-warning text-dark me-1" style="font-size:0.6rem">{{ __('admissions.primary_badge') }}</span>
                                        @endif
                                        <span class="fw-medium">{{ $diagnosis->description }}</span>
                                        @if($diagnosis->icdCodeEntry)
                                            <span class="badge bg-light text-dark ms-1 border">{{ $diagnosis->icdCodeEntry->code }}</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                @if($medicalRecord->complaints->count() > 0)
                                <div class="col-md-6">
                                    <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-message-circle me-1"></i>{{ __('admissions.complaints') }}</h6>
                                    @foreach($medicalRecord->complaints->take(4) as $complaint)
                                    <div class="ehr-item">
                                        <div class="fw-medium">{{ $complaint->description }}</div>
                                        @if($complaint->duration)<small class="text-muted">{{ $complaint->duration }}</small>@endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                @if($medicalRecord->treatments->count() > 0)
                                <div class="col-md-6">
                                    <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-first-aid-kit me-1"></i>{{ __('admissions.treatments') }}</h6>
                                    @foreach($medicalRecord->treatments->take(3) as $treatment)
                                    <div class="ehr-item">
                                        <div class="fw-medium">{{ $treatment->description }}</div>
                                        @if($treatment->notes)<small class="text-muted">{{ Str::limit($treatment->notes, 90) }}</small>@endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                @if($summaryTasks->isNotEmpty())
                                <div class="col-md-6">
                                    <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.doctor_clinical_tasks') }}</h6>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-warning text-dark">{{ $summaryTasks->whereNotIn('status', ['completed', 'cancelled'])->count() }} {{ __('admissions.pending_label') }}</span>
                                        <span class="badge bg-success">{{ $summaryTasks->where('status', 'completed')->count() }} {{ __('admissions.done_label') }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_consultation_data') }}</div>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0"><i class="ti ti-heartbeat me-1"></i>{{ __('admissions.last_vitals') }}</h5>
                                <a href="#" onclick="event.preventDefault();showTab('tab-simplified-vitals')" class="fs-12 text-decoration-none">{{ __('admissions.tab_vitals') }} <i class="ti ti-arrow-right fs-11"></i></a>
                            </div>
                            <div class="card-body">
                                @if($latestVitals)
                                    <div class="row g-2 text-center">
                                        <div class="col-4"><div class="vitals-val">{{ $latestVitals->blood_pressure ?? '-' }}</div><div class="vitals-label">{{ __('admissions.bp_col') }}</div></div>
                                        <div class="col-4"><div class="vitals-val adm-num">{{ $latestVitals->heart_rate ?? '-' }}</div><div class="vitals-label">{{ __('admissions.hr_col') }}</div></div>
                                        <div class="col-4"><div class="vitals-val adm-num">{{ $latestVitals->temperature ? $latestVitals->temperature.'&deg;' : '-' }}</div><div class="vitals-label">{{ __('admissions.temp_col') }}</div></div>
                                        <div class="col-4"><div class="vitals-val adm-num">{{ $latestVitals->spo2 ? $latestVitals->spo2.'%' : '-' }}</div><div class="vitals-label">{{ __('admissions.spo2_col') }}</div></div>
                                        <div class="col-4"><div class="vitals-val adm-num">{{ $latestVitals->respiratory_rate ?? '-' }}</div><div class="vitals-label">{{ __('admissions.rr_col') }}</div></div>
                                        <div class="col-4"><div class="vitals-val adm-num">{{ $latestVitals->blood_sugar ?? '-' }}</div><div class="vitals-label">{{ __('admissions.sugar_col') }}</div></div>
                                    </div>
                                    <div class="text-muted fs-12 mt-3">
                                        <i class="ti ti-clock me-1"></i>{{ $latestVitals->recorded_at?->diffForHumans() }} - {{ $latestVitals->recordedBy->name ?? '-' }}
                                    </div>
                                @else
                                    <div class="text-center py-3 text-muted"><i class="ti ti-activity-off fs-1 d-block mb-2"></i>{{ __('admissions.no_vitals_yet') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="card-title mb-0"><i class="ti ti-report-medical me-1"></i>{{ __('admissions.tab_nursing') }}</h5>
                                @can('admission.nursing.view')
                                <a href="#" onclick="event.preventDefault();showTab('tab-simplified-nursing')" class="fs-12 text-decoration-none">{{ __('admissions.tab_nursing') }} <i class="ti ti-arrow-right fs-11"></i></a>
                                @endcan
                            </div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap mb-3">
                                    <span class="badge bg-warning text-dark">{{ $openNursing }} {{ __('admissions.pending_label') }}</span>
                                    <span class="badge bg-danger">{{ $overdueNursing }} {{ __('admissions.overdue_label') }}</span>
                                </div>
                                @if($recentNursingNotes->isNotEmpty())
                                    <div class="list-group list-group-flush">
                                        @foreach($recentNursingNotes as $note)
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between gap-2">
                                                <span class="fw-semibold">{{ $note->note_type?->label() ?? __('admissions.nursing_notes') }}</span>
                                                <small class="text-muted adm-num">{{ $note->observed_at?->format('d M H:i') }}</small>
                                            </div>
                                            <div class="text-muted fs-12">{{ Str::limit($note->note, 120) }}</div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted fs-13">{{ __('admissions.no_nursing_notes') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-simplified-vitals" role="tabpanel">
        @include('admissions.partials.vitals-trend')
    </div>

    @can('admission.nursing.view')
    <div class="tab-pane fade" id="tab-simplified-nursing" role="tabpanel">
        @include('admissions.partials.nursing-care-tab')
    </div>
    @endcan

    @can('admission.medication_board.view')
    <div class="tab-pane fade" id="tab-simplified-medications" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>{{ __('admissions.medication_admin_record') }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    @can('admission.mar_chart.view')
                    <a href="{{ $workspaceRoutes->route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">{{ __('admissions.mar_chart_btn') }}</a>
                    @endcan
                    <a href="{{ $workspaceRoutes->route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">{{ __('admissions.dose_board_btn') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    @include('admissions.partials.medication-counts', ['counts' => $medicationBoard['counts'] ?? [], 'layout' => 'grid'])
                </div>
                @if(($medicationBoard['orders'] ?? collect())->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>{{ __('admissions.medication_col') }}</th>
                                    <th>{{ __('admissions.progress_col') }}</th>
                                    <th>{{ __('admissions.next_due_col') }}</th>
                                    <th>{{ __('admissions.status_col') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($medicationBoard['orders'] as $entry)
                                @php $order = $entry['order']; $progress = $entry['progress']; @endphp
                                <tr>
                                    <td><strong>{{ $order->display_name }}</strong><br><small class="text-muted">{{ $order->dose }} {{ $order->frequency_code ? ' - '.$order->frequency_code : '' }}</small></td>
                                    <td>{{ $progress['given_doses'] }}/{{ $progress['total_doses'] }} {{ __('admissions.given_doses') }}</td>
                                    <td>{{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '-' }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">{{ __('admissions.no_medication_orders') }}</div>
                @endif
            </div>
        </div>
    </div>
    @endcan

    <div class="tab-pane fade" id="tab-simplified-rounds" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>{{ __('admissions.ward_rounds_history') }} ({{ $admission->wardRounds->count() }})</h5>
                @if($admission->status->value === 'admitted')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordWardRoundModal">
                    <i class="ti ti-plus me-1"></i>{{ __('admissions.record_ward_round') }}
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="nursing-thread">
                    @include('admissions.partials.ward-rounds-thread', ['rounds' => $admission->wardRounds])
                </div>
            </div>
        </div>
    </div>

    @if($summaryTasks->isNotEmpty())
    <div class="tab-pane fade" id="tab-simplified-consultation" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.doctor_clinical_tasks') }}</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning text-dark">{{ $summaryTasks->whereNotIn('status', ['completed', 'cancelled'])->count() }} {{ __('admissions.pending_label') }}</span>
                    <span class="badge bg-success">{{ $summaryTasks->where('status', 'completed')->count() }} {{ __('admissions.done_label') }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                @include('admissions.partials.consultation-tasks-table', ['tasks' => $summaryTasks, 'detailed' => true])
            </div>
        </div>
    </div>
    @endif

    @if($canViewBilling)
    <div class="tab-pane fade" id="tab-simplified-billing" role="tabpanel">
        @include('admissions.partials.billing-tab')
    </div>
    @endif

    @can('admission.discharge.readiness.view')
    <div class="tab-pane fade" id="tab-simplified-discharge" role="tabpanel">
        @include('admissions.partials.discharge-readiness-tab')
    </div>
    @endcan
</div> -->

@if($admission->status->value === 'admitted')
<div class="modal fade" id="recordWardRoundModal" tabindex="-1" aria-labelledby="recordWardRoundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.rounds.store', $admission) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="recordWardRoundModalLabel"><i class="ti ti-notes me-1"></i>{{ __('admissions.record_ward_round') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('admissions.round_notes_label') }} <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control" rows="4" required placeholder="{{ __('admissions.round_notes_ph') }}">{{ old('notes') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admissions.instructions_field') }}</label>
                    <textarea name="instructions" class="form-control" rows="3" placeholder="{{ __('admissions.instructions_ph') }}">{{ old('instructions') }}</textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('admissions.round_datetime_label') }}</label>
                    <input type="datetime-local" name="round_date" class="form-control" value="{{ old('round_date', now()->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('admissions.save_round_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
