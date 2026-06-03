@extends('layouts.app')
@section('title', 'Admission — ' . $admission->admission_number)

@push('styles')
<style>
    .vitals-val { font-size: 1.1rem; font-weight: 700; }
    .vitals-label { font-size: 0.68rem; color: #6c757d; }
    .task-done { text-decoration: line-through; opacity: 0.6; }
    .ehr-item { border-left: 3px solid #dee2e6; padding-left: 0.9rem; margin-bottom: 0.6rem; }
    .ehr-item.is-primary { border-left-color: #ffc107; }
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            Admission {{ $admission->admission_number }}
            <span class="badge badge-soft-{{ $admission->status->color() }} ms-2">{{ $admission->status->label() }}</span>
        </h4>
    </div>
    <div class="text-end d-flex gap-2">
        @if($admission->status->value === 'admitted')
            @can('ward.discharge')
            <a href="{{ route('admin.admissions.discharge', $admission) }}" class="btn btn-warning btn-md fs-13">
                <i class="ti ti-logout me-1"></i>Discharge Patient
            </a>
            @endcan
        @endif
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@php $medicalRecord = $admission->visit->medicalRecord; @endphp

@cannot('ward.manage')
{{-- ═══════════════════════════════════════════════════════════════════════
     SIMPLIFIED WARD ACTIVITY VIEW — for Nurses and limited-access roles
     Shows: Patient brief | Vitals form | Consultation preview | Tasks
═══════════════════════════════════════════════════════════════════════ --}}
<div class="row g-3">
    {{-- Patient + Admission brief --}}
    <div class="col-12">
        <div class="card border-0 bg-light">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                        <span class="fw-bold text-primary">{{ strtoupper(substr($admission->patient->first_name,0,1).substr($admission->patient->last_name,0,1)) }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $admission->patient->full_name }}</h5>
                        <small class="text-muted">{{ $admission->patient->patient_number }} &bull; {{ $admission->patient->age }} yrs &bull; {{ $admission->patient->gender->label() }}</small>
                    </div>
                    <div class="ms-auto d-flex gap-3 flex-wrap">
                        <div class="text-center"><div class="fw-bold">{{ $admission->bed->ward->name }}</div><small class="text-muted">Ward</small></div>
                        <div class="text-center"><div class="fw-bold">{{ $admission->bed->bed_number }}</div><small class="text-muted">Bed</small></div>
                        <div class="text-center"><div class="fw-bold">{{ $admission->length_of_stay }}d</div><small class="text-muted">Stay</small></div>
                        <div class="text-center">
                            <span class="badge badge-soft-{{ $admission->status->color() }}">{{ $admission->status->label() }}</span>
                        </div>
                    </div>
                </div>
                @if($admission->patient->allergies)
                <div class="alert alert-danger py-1 px-2 mb-0 mt-2 fs-12"><i class="ti ti-alert-triangle me-1"></i><strong>Allergies:</strong> {{ $admission->patient->allergies }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Medication administration --}}
    @can('admission.medication_board.view')
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>Medication Administration</h5>
                <div class="d-flex gap-2 flex-wrap">
                    @can('admission.mar_chart.view')
                    <a href="{{ route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">MAR Chart</a>
                    @endcan
                    <a href="{{ route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">Board</a>
                </div>
            </div>
            <div class="card-body">
                @php $medCounts = $medicationBoard['counts'] ?? []; @endphp
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-info">Due now: {{ $medCounts['due_now'] ?? 0 }}</span>
                    <span class="badge bg-danger">Overdue: {{ $medCounts['overdue'] ?? 0 }}</span>
                    <span class="badge bg-secondary">Upcoming: {{ $medCounts['upcoming'] ?? 0 }}</span>
                    <span class="badge bg-success">Completed today: {{ $medCounts['completed_today'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- Vitals recording --}}
    @can('vitals.create')
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-heartbeat me-1"></i>Record Vitals</h5>
                @if($admission->visit->vitals->count() > 0)
                @php $lastV = $admission->visit->vitals->sortByDesc('recorded_at')->first(); @endphp
                <small class="text-muted">Last: <strong>{{ $lastV->recorded_at->diffForHumans() }}</strong> by {{ $lastV->recordedBy->name ?? '—' }}</small>
                @endif
            </div>
            <div class="card-body">
                @if($admission->status->value === 'admitted')
                <form method="POST" action="{{ route('admin.admissions.vitals.store', $admission) }}">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3"><label class="form-label small">BP Systolic</label><div class="input-group input-group-sm"><input type="number" name="blood_pressure_systolic" class="form-control" placeholder="120" min="0" max="300"><span class="input-group-text">mmHg</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">BP Diastolic</label><div class="input-group input-group-sm"><input type="number" name="blood_pressure_diastolic" class="form-control" placeholder="80" min="0" max="200"><span class="input-group-text">mmHg</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">Heart Rate</label><div class="input-group input-group-sm"><input type="number" name="heart_rate" class="form-control" placeholder="72" min="0" max="300"><span class="input-group-text">bpm</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">Temperature</label><div class="input-group input-group-sm"><input type="number" name="temperature" class="form-control" placeholder="36.6" step="0.1" min="30" max="45"><span class="input-group-text">°C</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">Resp. Rate</label><div class="input-group input-group-sm"><input type="number" name="respiratory_rate" class="form-control" placeholder="16" min="0" max="60"><span class="input-group-text">/min</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">SpO₂</label><div class="input-group input-group-sm"><input type="number" name="spo2" class="form-control" placeholder="98" step="0.1" min="0" max="100"><span class="input-group-text">%</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">Weight</label><div class="input-group input-group-sm"><input type="number" name="weight" class="form-control" placeholder="70" step="0.1" min="0"><span class="input-group-text">kg</span></div></div>
                        <div class="col-6 col-md-3"><label class="form-label small">Blood Sugar</label><div class="input-group input-group-sm"><input type="number" name="blood_sugar" class="form-control" placeholder="5.0" step="0.1" min="0"><span class="input-group-text">mmol/L</span></div></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><label class="form-label small">Recorded At</label><input type="datetime-local" name="recorded_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                        <div class="col-md-8"><label class="form-label small">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional observations..."></div>
                    </div>
                    <div class="text-end"><button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>Save Vitals</button></div>
                </form>
                @else
                <p class="text-muted mb-0">Patient is no longer admitted. Vitals recording disabled.</p>
                @endif
            </div>
        </div>
    </div>
    @endcan

    {{-- Consultation preview --}}
    @if($medicalRecord)
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>Consultation Records</h5>
                <small class="text-muted">Dr. {{ $medicalRecord->doctor->name ?? 'N/A' }}</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($medicalRecord->complaints->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-message-circle me-1"></i>Complaints</h6>
                        @foreach($medicalRecord->complaints as $c)
                        <div class="ehr-item"><div class="fw-medium">{{ $c->description }}</div>@if($c->duration)<small class="text-muted">{{ $c->duration }}</small>@endif</div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->diagnoses->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-stethoscope me-1"></i>Diagnoses</h6>
                        @foreach($medicalRecord->diagnoses as $d)
                        <div class="ehr-item {{ $d->is_primary?'is-primary':'' }}">
                            @if($d->is_primary)<span class="badge bg-warning text-dark me-1" style="font-size:0.6rem">Primary</span>@endif
                            <span class="fw-medium">{{ $d->description }}</span>
                            @if($d->icdCodeEntry)<span class="badge bg-light text-dark ms-1 border">{{ $d->icdCodeEntry->code }}</span>@endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->treatments->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-first-aid-kit me-1"></i>Treatments</h6>
                        @foreach($medicalRecord->treatments as $t)
                        <div class="ehr-item"><div class="fw-medium">{{ $t->description }}</div>@if($t->notes)<small class="text-muted">{{ $t->notes }}</small>@endif</div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->prescriptions->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-pill me-1"></i>Active Prescriptions</h6>
                        @foreach($medicalRecord->prescriptions as $rx)
                        @if($rx->items->count() > 0)
                        <div class="ehr-item mb-2">
                            <ul class="mb-0 ps-3 fs-12">
                                @foreach($rx->items as $item)
                                <li><strong>{{ $item->drug->name ?? '?' }}</strong> — {{ $item->dosage }}@if($item->frequency), {{ $item->frequency }}@endif @if($item->duration), {{ $item->duration }}@endif</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tasks --}}
    @if($medicalRecord->tasks->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard-list me-1"></i>Nursing Tasks</h5>
                <span class="badge bg-warning text-dark">{{ $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count() }} pending</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light"><tr><th style="width:3rem"></th><th>Task</th><th>Priority</th><th>Assigned To</th><th>Due</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($medicalRecord->tasks->sortBy('status') as $task)
                            @php $isDone = $task->status === 'completed'; @endphp
                            <tr class="{{ $isDone ? 'table-success' : '' }}">
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.consultations.tasks.toggle', $task) }}">
                                        @csrf @method('PATCH')
                                        <button aria-label="Confirm" title="Confirm" type="submit" class="btn btn-sm {{ $isDone?'btn-success':'btn-outline-success' }} p-1">
                                            <i class="ti ti-check fs-13"></i>
                                        </button>
                                    </form>
                                </td>
                                <td><div class="{{ $isDone?'task-done':'fw-medium' }}">{{ $task->title }}</div></td>
                                <td><span class="badge bg-{{ match($task->priority){'high'=>'danger','medium'=>'warning',default=>'secondary'} }}">{{ ucfirst($task->priority??'normal') }}</span></td>
                                <td>{{ $task->assignedUser->name ?? '—' }}</td>
                                <td>@if($task->due_date)<span class="{{ !$isDone&&$task->due_date->isPast()?'text-danger fw-medium':'' }}">{{ $task->due_date->format('d M') }}</span>@else—@endif</td>
                                <td><span class="badge badge-soft-{{ match($task->status){'completed'=>'success','in_progress'=>'info','cancelled'=>'secondary',default=>'warning'} }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@else
{{-- ═══════════════════════════════════════════════════════════════════════
     FULL MANAGEMENT VIEW — for Doctors, Admin, Ward Manager, etc.
═══════════════════════════════════════════════════════════════════════ --}}

<div class="row">
    <!-- LEFT COLUMN -->
    <div class="col-lg-4">
        @include('partials.patient-card', [
            'patient' => $admission->patient,
            'visit'   => $admission->visit,
        ])

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-clipboard me-1"></i>Admission Details</h5></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:45%">Admission #</td><td class="fw-medium">{{ $admission->admission_number }}</td></tr>
                    <tr><td class="text-muted">Visit #</td><td><a href="{{ route('admin.visits.show', $admission->visit) }}" class="text-decoration-none">{{ $admission->visit->visit_number }}</a></td></tr>
                    <tr><td class="text-muted">Ward</td><td>{{ $admission->bed->ward->name }}</td></tr>
                    <tr><td class="text-muted">Bed</td><td>{{ $admission->bed->bed_number }} ({{ $admission->bed->bed_type->label() }})</td></tr>
                    <tr><td class="text-muted">Daily Rate</td><td>GH&#8373; {{ number_format($admission->bed->daily_rate,2) }}</td></tr>
                    <tr><td class="text-muted">Admitted On</td><td>{{ $admission->admission_date->format('d M Y, H:i') }}</td></tr>
                    <tr><td class="text-muted">Admitted By</td><td>{{ $admission->admittedBy->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Length of Stay</td><td><span class="badge badge-soft-secondary">{{ $admission->length_of_stay }} day(s)</span></td></tr>
                    @if($admission->expected_discharge_date)
                    <tr><td class="text-muted">Expected Discharge</td><td>{{ $admission->expected_discharge_date->format('d M Y') }}</td></tr>
                    @endif
                    @if($admission->actual_discharge_date)
                    <tr><td class="text-muted">Discharged On</td><td>{{ $admission->actual_discharge_date->format('d M Y, H:i') }}</td></tr>
                    <tr><td class="text-muted">Discharged By</td><td>{{ $admission->dischargedBy->name ?? '—' }}</td></tr>
                    @endif
                </table></div>
            </div>
        </div>

        @if($admission->admitting_diagnosis)
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>Admitting Diagnosis</h5></div>
            <div class="card-body"><p class="mb-0">{{ $admission->admitting_diagnosis }}</p></div>
        </div>
        @endif

        @if($admission->status->value === 'discharged')
        <div class="card mb-3 border-success">
            <div class="card-header bg-success bg-opacity-10">
                <h5 class="card-title mb-0 text-success"><i class="ti ti-logout me-1"></i>Discharge Summary</h5>
            </div>
            <div class="card-body">
                @if($admission->discharge_summary)<p>{{ $admission->discharge_summary }}</p>@endif
                @if($admission->discharge_instructions)<hr><h6>Instructions</h6><p class="mb-0">{{ $admission->discharge_instructions }}</p>@endif
            </div>
        </div>
        @endif

        @if($medicalRecord && $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count() > 0)
        @php $pendingSummary = $medicalRecord->tasks->whereNotIn('status',['completed','cancelled']); @endphp
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="card-title mb-0 text-warning">
                    <i class="ti ti-clipboard-list me-1"></i>Pending Tasks
                    <span class="badge bg-warning text-dark ms-1">{{ $pendingSummary->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-2">
                @foreach($pendingSummary->take(3) as $task)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <form method="POST" action="{{ route('admin.consultations.tasks.toggle', $task) }}" class="mb-0">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-success p-1" title="Mark done"><i class="ti ti-check fs-12"></i></button>
                    </form>
                    <span class="fs-13 @if($task->priority==='high') text-danger fw-medium @endif">{{ $task->title }}</span>
                </div>
                @endforeach
                @if($pendingSummary->count() > 3)
                <a href="#tab-tasks" class="fs-12 text-muted" onclick="event.preventDefault();showTab('tab-tasks')">+ {{ $pendingSummary->count()-3 }} more...</a>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- RIGHT COLUMN — Tabbed -->
    <div class="col-lg-8">
        <ul class="nav nav-tabs mb-3" id="admTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-consult" type="button"><i class="ti ti-stethoscope me-1"></i>Consultation</button></li>
            @can('admission.medication_board.view')
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-medications" type="button"><i class="ti ti-pill me-1"></i>MAR @if(($medicationBoard['counts']['overdue'] ?? 0) > 0)<span class="badge bg-danger ms-1">{{ $medicationBoard['counts']['overdue'] }}</span>@elseif(($medicationBoard['counts']['due_now'] ?? 0) > 0)<span class="badge bg-info ms-1">{{ $medicationBoard['counts']['due_now'] }}</span>@endif</button></li>
            @endcan
            @if($medicalRecord)
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">
                    <i class="ti ti-clipboard-list me-1"></i>Tasks
                    @php $pendingCount = $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count(); @endphp
                    @if($pendingCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                    @else<span class="badge bg-secondary ms-1">{{ $medicalRecord->tasks->count() }}</span>@endif
                </button>
            </li>
            @endif
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rounds" type="button"><i class="ti ti-notes me-1"></i>Ward Rounds <span class="badge bg-secondary ms-1">{{ $admission->wardRounds->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button"><i class="ti ti-heartbeat me-1"></i>Vitals <span class="badge bg-secondary ms-1">{{ $admission->visit->vitals->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button"><i class="ti ti-file-invoice me-1"></i>Billing <span class="badge bg-secondary ms-1">{{ $admission->visit->visitServices->count() }}</span></button></li>
        </ul>

        <div class="tab-content">

            @can('admission.medication_board.view')
            <div class="tab-pane fade" id="tab-medications" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>Medication Administration Record</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            @can('admission.mar_chart.view')
                            <a href="{{ route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">MAR Chart</a>
                            @endcan
                            <a href="{{ route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">Dose Board</a>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $medCounts = $medicationBoard['counts'] ?? []; @endphp
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3"><span class="badge bg-info w-100 py-2">Due now: {{ $medCounts['due_now'] ?? 0 }}</span></div>
                            <div class="col-6 col-md-3"><span class="badge bg-danger w-100 py-2">Overdue: {{ $medCounts['overdue'] ?? 0 }}</span></div>
                            <div class="col-6 col-md-3"><span class="badge bg-secondary w-100 py-2">Upcoming: {{ $medCounts['upcoming'] ?? 0 }}</span></div>
                            <div class="col-6 col-md-3"><span class="badge bg-success w-100 py-2">Completed today: {{ $medCounts['completed_today'] ?? 0 }}</span></div>
                        </div>
                        @if(($medicationBoard['orders'] ?? collect())->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead class="bg-light"><tr><th>Medication</th><th>Progress</th><th>Next Due</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @foreach($medicationBoard['orders'] as $entry)
                                        @php $order = $entry['order']; $progress = $entry['progress']; @endphp
                                        <tr>
                                            <td><strong>{{ $order->display_name }}</strong><br><small class="text-muted">{{ $order->dose }} {{ $order->frequency_code ? '· '.$order->frequency_code : '' }}</small></td>
                                            <td>{{ $progress['given_doses'] }}/{{ $progress['total_doses'] }} given</td>
                                            <td>{{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '—' }}</td>
                                            <td><span class="badge badge-soft-secondary">{{ str_replace('_',' ', $order->status) }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">No medication administration orders are linked to this admission yet.</div>
                        @endif
                    </div>
                </div>
            </div>
            @endcan

            <!-- TAB: WARD ROUNDS -->
            <div class="tab-pane fade" id="tab-rounds" role="tabpanel">
                @if($admission->status->value === 'admitted')
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-plus me-1"></i>Record Ward Round</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.admissions.rounds.store', $admission) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Round Notes <span class="text-danger">*</span></label>
                                <textarea name="notes" class="form-control" rows="3" required placeholder="Observations, patient condition...">{{ old('notes') }}</textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Instructions</label>
                                    <textarea name="instructions" class="form-control" rows="2" placeholder="Medication changes, diet...">{{ old('instructions') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Round Date/Time</label>
                                    <input type="datetime-local" name="round_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                            <div class="text-end"><button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Save Round</button></div>
                        </form>
                    </div>
                </div>
                @endif
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>Ward Rounds History ({{ $admission->wardRounds->count() }})</h5></div>
                    <div class="card-body p-0">
                        @if($admission->wardRounds->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light"><tr><th style="width:15%">Date/Time</th><th style="width:15%">Recorded By</th><th>Notes</th><th>Instructions</th></tr></thead>
                                <tbody>
                                    @foreach($admission->wardRounds as $round)
                                    <tr>
                                        <td><div class="fw-medium">{{ $round->round_date->format('d M Y') }}</div><small class="text-muted">{{ $round->round_date->format('H:i') }}</small></td>
                                        <td>{{ $round->recordedBy->name ?? '—' }}</td>
                                        <td>{{ $round->notes }}</td>
                                        <td>{{ $round->instructions ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>No ward rounds recorded yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TAB: PERIODIC VITALS -->
            <div class="tab-pane fade" id="tab-vitals" role="tabpanel">
                @if($admission->status->value === 'admitted')
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-activity me-1"></i>Record Vitals</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.admissions.vitals.store', $admission) }}">
                            @csrf
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">BP Systolic</label>
                                    <div class="input-group input-group-sm"><input type="number" name="blood_pressure_systolic" class="form-control" placeholder="120" min="0" max="300"><span class="input-group-text">mmHg</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">BP Diastolic</label>
                                    <div class="input-group input-group-sm"><input type="number" name="blood_pressure_diastolic" class="form-control" placeholder="80" min="0" max="200"><span class="input-group-text">mmHg</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Heart Rate</label>
                                    <div class="input-group input-group-sm"><input type="number" name="heart_rate" class="form-control" placeholder="72" min="0" max="300"><span class="input-group-text">bpm</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Temperature</label>
                                    <div class="input-group input-group-sm"><input type="number" name="temperature" class="form-control" placeholder="36.6" step="0.1" min="30" max="45"><span class="input-group-text">°C</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Resp. Rate</label>
                                    <div class="input-group input-group-sm"><input type="number" name="respiratory_rate" class="form-control" placeholder="16" min="0" max="60"><span class="input-group-text">/min</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">SpO&#8322;</label>
                                    <div class="input-group input-group-sm"><input type="number" name="spo2" class="form-control" placeholder="98" step="0.1" min="0" max="100"><span class="input-group-text">%</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Weight</label>
                                    <div class="input-group input-group-sm"><input type="number" name="weight" class="form-control" placeholder="70" step="0.1" min="0" max="500"><span class="input-group-text">kg</span></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small">Blood Sugar</label>
                                    <div class="input-group input-group-sm"><input type="number" name="blood_sugar" class="form-control" placeholder="5.0" step="0.1" min="0"><span class="input-group-text">mmol/L</span></div>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Recorded At</label>
                                    <input type="datetime-local" name="recorded_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small">Notes</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional observations...">
                                </div>
                            </div>
                            <div class="text-end"><button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>Save Vitals</button></div>
                        </form>
                    </div>
                </div>
                @endif
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>Vitals History ({{ $admission->visit->vitals->count() }})</h5></div>
                    <div class="card-body p-0">
                        @if($admission->visit->vitals->count() > 0)
                        @php $vitalsChronological = $admission->visit->vitals->sortBy('recorded_at'); @endphp
                        {{-- Chart --}}
                        <div class="p-3 border-bottom">
                            <canvas id="vitalsChart" height="110"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr><th>Date/Time</th><th>BP</th><th>HR</th><th>Temp</th><th>SpO&#8322;</th><th>RR</th><th>Sugar</th><th>By</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($admission->visit->vitals->sortByDesc('recorded_at') as $vital)
                                    <tr>
                                        <td><div class="fw-medium">{{ $vital->recorded_at->format('d M Y') }}</div><small class="text-muted">{{ $vital->recorded_at->format('H:i') }}</small></td>
                                        <td>{{ $vital->blood_pressure ?? '—' }}</td>
                                        <td>{{ $vital->heart_rate ?? '—' }}</td>
                                        <td>{{ $vital->temperature ? $vital->temperature.'°C' : '—' }}</td>
                                        <td>{{ $vital->spo2 ? $vital->spo2.'%' : '—' }}</td>
                                        <td>{{ $vital->respiratory_rate ?? '—' }}</td>
                                        <td>{{ $vital->blood_sugar ?? '—' }}</td>
                                        <td>{{ $vital->recordedBy->name ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4 text-muted"><i class="ti ti-activity-off fs-1 d-block mb-2"></i>No vitals recorded yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TAB: CONSULTATION RECORDS (read-only) -->
            <div class="tab-pane fade show active" id="tab-consult" role="tabpanel">
                <div class="alert alert-info py-2 mb-3">
                    <i class="ti ti-user-md me-1"></i>
                    Consultation by <strong>{{ $medicalRecord->doctor->name ?? 'N/A' }}</strong>
                    &mdash;
                    <a href="{{ route('admin.consultations.show', $admission->visit) }}" target="_blank" class="link-primary ms-1">
                        <i class="ti ti-external-link me-1"></i>Open Full Consultation
                    </a>
                </div>
                @if($medicalRecord)
                    <div class="row" id="summary-section">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>Notes / Consultation Summary</h6>
                            </div>
                            <div class="card-body" id="consultation-summary-body">
                                @include('consultations.partials.summary-sections', ['consultationSummary' => $consultationSummary])
                            </div>
                        </div>
                    </div>

                {{-- <div class="row g-3">
                    @if($medicalRecord->complaints->count() > 0)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2"><h6 class="card-title mb-0"><i class="ti ti-message-circle me-1"></i>Complaints</h6></div>
                            <div class="card-body">
                                @foreach($medicalRecord->complaints as $c)
                                <div class="ehr-item"><div class="fw-medium">{{ $c->description }}</div>@if($c->duration)<small class="text-muted">{{ $c->duration }}</small>@endif</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($medicalRecord->diagnoses->count() > 0)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2"><h6 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>Diagnoses</h6></div>
                            <div class="card-body">
                                @foreach($medicalRecord->diagnoses as $d)
                                <div class="ehr-item {{ $d->is_primary ? 'is-primary' : '' }}">
                                    @if($d->is_primary)<span class="badge bg-warning text-dark me-1" style="font-size:0.6rem">Primary</span>@endif
                                    <span class="fw-medium">{{ $d->description }}</span>
                                    @if($d->icdCodeEntry)<span class="badge bg-light text-dark ms-1 border">{{ $d->icdCodeEntry->code }}</span>@endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($medicalRecord->treatments->count() > 0)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2"><h6 class="card-title mb-0"><i class="ti ti-first-aid-kit me-1"></i>Treatments</h6></div>
                            <div class="card-body">
                                @foreach($medicalRecord->treatments as $t)
                                <div class="ehr-item"><div class="fw-medium">{{ $t->description }}</div>@if($t->notes)<small class="text-muted">{{ $t->notes }}</small>@endif</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($medicalRecord->prescriptions->count() > 0)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2"><h6 class="card-title mb-0"><i class="ti ti-pill me-1"></i>Prescriptions</h6></div>
                            <div class="card-body p-0">
                                @foreach($medicalRecord->prescriptions as $rx)
                                <div class="px-3 py-2 border-bottom">
                                    <strong class="fs-13">Rx #{{ $rx->prescription_number ?? $rx->id }}</strong>
                                    @if($rx->items->count() > 0)
                                    <ul class="mb-0 mt-1 ps-3 fs-12">
                                        @foreach($rx->items as $item)
                                        <li><strong>{{ $item->drug->name ?? '?' }}</strong> — {{ $item->dosage }}@if($item->frequency), {{ $item->frequency }}@endif @if($item->duration), {{ $item->duration }}@endif</li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div> --}}
                    @if($medicalRecord->complaints->count()===0 && $medicalRecord->diagnoses->count()===0 && $medicalRecord->treatments->count()===0 && $medicalRecord->prescriptions->count()===0)
                        <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>No consultation data recorded yet.</div>
                    @endif
                @endif
            </div>

            <!-- TAB: TASKS -->
            @if($medicalRecord)
            <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-clipboard-list me-1"></i>Nursing / Clinical Tasks</h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-warning text-dark">{{ $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count() }} pending</span>
                            <span class="badge bg-success">{{ $medicalRecord->tasks->where('status','completed')->count() }} done</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($medicalRecord->tasks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr><th style="width:3rem"></th><th>Task</th><th style="width:8rem">Priority</th><th style="width:12rem">Assigned To</th><th style="width:8rem">Due</th><th style="width:8rem">Status</th><th style="width:9rem">Completed</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($medicalRecord->tasks->sortBy('status') as $task)
                                    @php $isDone = $task->status === 'completed'; @endphp
                                    <tr class="{{ $isDone ? 'table-success' : '' }}">
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('admin.consultations.tasks.toggle', $task) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-success' : 'btn-outline-success' }} p-1" title="{{ $isDone ? 'Mark as pending' : 'Mark as done' }}">
                                                    <i class="ti ti-check fs-13"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="{{ $isDone ? 'task-done' : 'fw-medium' }}">{{ $task->title }}</div>
                                            @if($task->description)<small class="text-muted">{{ Str::limit($task->description, 80) }}</small>@endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ match($task->priority) { 'high'=>'danger','medium'=>'warning',default=>'secondary' } }}">{{ ucfirst($task->priority ?? 'normal') }}</span>
                                        </td>
                                        <td>{{ $task->assignedUser->name ?? '—' }}</td>
                                        <td>
                                            @if($task->due_date)<span class="{{ !$isDone && $task->due_date->isPast() ? 'text-danger fw-medium' : '' }}">{{ $task->due_date->format('d M') }}</span>
                                            @else—@endif
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-{{ match($task->status){ 'completed'=>'success','in_progress'=>'info','cancelled'=>'secondary',default=>'warning'} }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                                        </td>
                                        <td>@if($task->completed_at)<small>{{ $task->completed_at->format('d M H:i') }}</small>@else—@endif</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4 text-muted"><i class="ti ti-clipboard-off fs-1 d-block mb-2"></i>No tasks assigned.</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- TAB: BILLING -->
            <div class="tab-pane fade" id="tab-billing" role="tabpanel">
                @if($admission->status->value === 'admitted')
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-plus me-1"></i>Add Service Charge</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.admissions.services.store', $admission) }}">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label">Service <span class="text-danger">*</span></label>
                                    <select name="service_catalog_id" class="form-select form-select-sm select2" required>
                                        <option value="">— Select service —</option>
                                        @foreach($services as $svc)
                                        <option value="{{ $svc->id }}" {{ old('service_catalog_id')==$svc->id?'selected':'' }}>
                                            {{ $svc->name }}@if($svc->base_price) (GH&#8373; {{ number_format($svc->base_price,2) }})@endif
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Qty</label>
                                    <input type="number" name="quantity" class="form-control form-control-sm" value="{{ old('quantity',1) }}" min="1" max="99">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional" value="{{ old('notes') }}">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button aria-label="Add" title="Add" type="submit" class="btn btn-primary btn-sm w-100"><i class="ti ti-plus"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
@php
                    $invoice = $admission->visit->latestInvoice;
                    $invoiceItems = $invoice ? $invoice->items : collect();
                    $svcItems = $admission->visit->visitServices;
                    $totalCharged = $svcItems->sum('total_price') + $invoiceItems->sum('total_price');
                    $totalPatient = $svcItems->sum('patient_payable') + ($invoice ? ($invoice->total_amount - $invoice->nhis_amount) : 0);
                    $totalInsurance = $svcItems->sum('insurance_covered') + ($invoice ? $invoice->nhis_amount : 0);
                @endphp

                {{-- Admission Invoice Items (Admission Fee, Bed Fee, Consumable) --}}
                @if($invoiceItems->count() > 0)
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-bed me-1"></i>Admission Charges
                            @if($invoice)<a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="btn btn-outline-primary btn-xs ms-2 fs-11"><i class="ti ti-file-invoice me-1"></i>{{ $invoice->invoice_number }}</a>@endif
                        </h5>
                        <span class="fw-bold text-primary">GH&#8373; {{ number_format($invoiceItems->sum('total_price'),2) }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light"><tr><th>Description</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th><th class="text-end">Insurance</th><th class="text-end">Patient Pays</th></tr></thead>
                                <tbody>
                                    @foreach($invoiceItems as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">GH&#8373; {{ number_format($item->unit_price,2) }}</td>
                                        <td class="text-end fw-medium">GH&#8373; {{ number_format($item->total_price,2) }}</td>
                                        <td class="text-end text-info">{{ $item->nhis_approved_amount > 0 ? 'GH&#8373; '.number_format($item->nhis_approved_amount,2) : '—' }}</td>
                                        <td class="text-end text-success fw-medium">GH&#8373; {{ number_format($item->total_price - $item->nhis_approved_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Additional Service Charges --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>Additional Service Charges ({{ $svcItems->count() }})</h5>
                        <span class="fw-bold text-success">GH&#8373; {{ number_format($svcItems->sum('total_price'),2) }}</span>
                    </div>
                    <div class="card-body p-0">
                        @if($svcItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light"><tr><th>Service</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th><th class="text-end">Insurance</th><th class="text-end">Patient Pays</th></tr></thead>
                                <tbody>
                                    @foreach($svcItems as $svc)
                                    <tr>
                                        <td>{{ $svc->serviceCatalog->name ?? '—' }}</td>
                                        <td class="text-center">{{ $svc->quantity }}</td>
                                        <td class="text-end">GH&#8373; {{ number_format($svc->unit_price,2) }}</td>
                                        <td class="text-end fw-medium">GH&#8373; {{ number_format($svc->total_price,2) }}</td>
                                        <td class="text-end text-info">{{ $svc->insurance_covered>0?'GH&#8373; '.number_format($svc->insurance_covered,2):'—' }}</td>
                                        <td class="text-end text-success fw-medium">GH&#8373; {{ number_format($svc->patient_payable,2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr><td colspan="3" class="text-end">Subtotal:</td><td class="text-end">GH&#8373; {{ number_format($svcItems->sum('total_price'),2) }}</td><td class="text-end text-info">GH&#8373; {{ number_format($svcItems->sum('insurance_covered'),2) }}</td><td class="text-end text-success">GH&#8373; {{ number_format($svcItems->sum('patient_payable'),2) }}</td></tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-3 text-muted"><i class="ti ti-receipt-off fs-1 d-block mb-2"></i>No additional service charges.</div>
                        @endif
                    </div>
                </div>
            </div>

        </div>{{-- /tab-content --}}
    </div>
</div>
@endcannot
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function(){
    var hash = window.location.hash;
    if(hash){
        var tab = document.querySelector('[data-bs-target="'+hash+'"]');
        if(tab) new bootstrap.Tab(tab).show();
    }
})();
function showTab(id){
    var tab = document.querySelector('[data-bs-target="#'+id+'"]');
    if(tab) new bootstrap.Tab(tab).show();
    var el = document.getElementById(id);
    if(el) setTimeout(function(){ el.scrollIntoView({behavior:'smooth'}); }, 100);
}

@if($admission->visit->vitals->count() > 1)
@php
$vitalsChartData = $admission->visit->vitals->sortBy('recorded_at')->values()->map(function($v) {
    return [
        'label'    => $v->recorded_at->format('d M H:i'),
        'systolic' => $v->blood_pressure_systolic,
        'diastolic'=> $v->blood_pressure_diastolic,
        'hr'       => $v->heart_rate,
        'temp'     => $v->temperature,
        'spo2'     => $v->spo2,
    ];
})->values()->toArray();
@endphp
(function(){
    var vitals = @json($vitalsChartData);

    var labels    = vitals.map(function(v){ return v.label; });
    var systolic  = vitals.map(function(v){ return v.systolic; });
    var diastolic = vitals.map(function(v){ return v.diastolic; });
    var hr        = vitals.map(function(v){ return v.hr; });
    var temp      = vitals.map(function(v){ return v.temp ? v.temp * 10 : null; }); // scale for visibility
    var spo2      = vitals.map(function(v){ return v.spo2; });

    new Chart(document.getElementById('vitalsChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'BP Systolic', data: systolic, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'BP Diastolic', data: diastolic, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'Heart Rate', data: hr, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'SpO₂ (%)', data: spo2, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'Temp (×10 °C)', data: temp, borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,0.08)', tension: 0.4, pointRadius: 4, borderDash: [5,5], fill: false },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if(ctx.dataset.label === 'Temp (×10 °C)' && ctx.raw != null)
                                return 'Temp: ' + (ctx.raw / 10).toFixed(1) + ' °C';
                            return ctx.dataset.label + ': ' + (ctx.raw ?? '—');
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();
@endif
</script>
@endpush
