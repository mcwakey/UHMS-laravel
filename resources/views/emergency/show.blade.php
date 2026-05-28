@extends('layouts.app')
@section('title', $case->emergency_number)

@php
    $triageClass = $case->triage_badge_class;
    $activeInvoice = $case->visit?->latestInvoice;
    $temporaryPatient = $case->patient?->is_temporary ? $case->patient : null;
    $identityAction = old('_identity_action');
    $formContext = old('_form');
    $shouldOpenIdentityModal = $errors->any() && in_array($identityAction, ['existing', 'register'], true);
    $shouldOpenTriageModal = $errors->any() && $formContext === 'triage';
    $shouldOpenControlSheetModal = $errors->any() && $formContext === 'control_sheet';
    $identityModalTabSelector = $identityAction === 'register' ? '#register-identity-tab' : '#existing-identity-tab';
    $session = $case->activeEmergencySession;
    $currentTriage = $case->current_triage_category ?: 'UNTRIAGED';
    $contributors = $session?->contributors?->map(fn ($contributor) => $contributor->user?->name ?: $contributor->user?->full_name)->filter()->unique()->values() ?? collect();
    $latestVitals = $case->latestVitals;
    $criticalAlerts = collect([
        $case->patient?->allergies ? 'Allergies: '.$case->patient->allergies : null,
        in_array($currentTriage, ['RED', 'BLACK'], true) ? 'Critical triage: '.$currentTriage : null,
        $case->patient?->is_temporary ? 'Temporary identity not confirmed' : null,
        $activeInvoice && (float) $activeInvoice->balance > 0 ? 'Unpaid balance GH'.number_format((float) $activeInvoice->balance, 2) : null,
    ])->filter();
    $medicationSchedules = $case->medicationOrders->flatMap(fn ($order) => $order->schedules ?? collect());
    $medCounts = [
        'due_now' => $medicationSchedules->filter(fn ($schedule) => in_array($schedule->status, ['DUE', 'SCHEDULED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->lte(now()))->count(),
        'overdue' => $medicationSchedules->filter(fn ($schedule) => ! in_array($schedule->status, ['GIVEN', 'MISSED', 'SKIPPED', 'REFUSED', 'HELD', 'CANCELLED', 'VOIDED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->lt(now()->subMinutes(30)))->count(),
        'upcoming' => $medicationSchedules->filter(fn ($schedule) => in_array($schedule->status, ['SCHEDULED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->gt(now()))->count(),
        'administered_today' => $case->medicationOrders->flatMap(fn ($order) => $order->administrations ?? collect())->filter(fn ($admin) => $admin->administered_at?->isToday())->count(),
    ];
    $frequencyMap = $frequencies->mapWithKeys(fn ($frequency) => [
        $frequency->code => [
            'times' => $frequency->times_per_day ?: ($frequency->interval_hours ? floor(24 / $frequency->interval_hours) : 0),
            'stat' => (bool) $frequency->is_stat,
            'prn' => (bool) $frequency->is_prn,
        ],
    ]);
    $pendingTasks = $case->clinicalTasks->filter(fn ($task) => ! in_array($task->status, ['COMPLETED', 'CANCELLED', 'MISSED', 'HELD', 'REFUSED', 'SKIPPED'], true));
    $dangerSignOptions = [
        'respiratory_distress' => 'Respiratory distress',
        'seizure' => 'Seizure',
        'shock' => 'Shock indicators',
        'uncontrolled_bleeding' => 'Uncontrolled bleeding',
        'trauma' => 'Major trauma',
        'bleeding' => 'Bleeding',
        'pregnancy' => 'Pregnancy',
        'dead_on_arrival' => 'Dead on arrival',
    ];
    $investigationServiceOptions = $investigationServices->map(fn ($service) => [
        'id' => $service->id,
        'department_id' => $service->department_id,
        'name' => $service->name,
        'code' => $service->code,
        'price' => $service->price,
    ])->values();
    $procedureServiceOptions = $procedureServices->map(fn ($service) => [
        'id' => $service->id,
        'department_id' => $service->department_id,
        'name' => $service->name,
        'code' => $service->code,
        'price' => $service->price,
    ])->values();
@endphp

@push('styles')
<style>
    .emergency-kpi { border-left: 4px solid rgba(220, 53, 69, .65); }
    .vitals-val { font-size: 1.1rem; font-weight: 700; }
    .vitals-label { font-size: .68rem; color: #6c757d; }
    .er-section-title { font-size: .88rem; letter-spacing: 0; text-transform: uppercase; color: #6c757d; }
    .er-scroll { max-height: 360px; overflow: auto; }
    .er-chart-wrap { position: relative; height: 190px; min-height: 190px; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">
            {{ $case->emergency_number }}
            <span class="badge {{ $triageClass }} ms-1">{{ $currentTriage }}</span>
            <span class="badge bg-light text-dark ms-1">{{ str_replace('_', ' ', $case->emergency_status) }}</span>
        </h4>
        <p class="text-muted mb-0">
            {{ $case->patient->full_name ?? 'Unknown patient' }} - {{ $case->patient->patient_number ?? 'No patient number' }} - {{ $case->visit->visit_number ?? 'No visit number' }} - arrived {{ $case->waiting_minutes }} min ago
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($temporaryPatient)
            @can('patients.merge.confirm_identity')
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#confirmEmergencyIdentityModal">
                    <i class="ti ti-id-badge-2 me-1"></i>Confirm Identity
                </button>
            @endcan
        @endif
        <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">Board</a>
        @if($case->visit)
            <a href="{{ route('admin.emergency.mar-chart', $case->visit) }}" class="btn btn-outline-danger btn-sm">Open MAR</a>
            <a href="{{ route('admin.visits.preview', $case->visit) }}" class="btn btn-outline-primary btn-sm">Visit Preview</a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please correct the highlighted details.</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light emergency-kpi">
            <div class="card-body">
                <div class="text-muted small">Triage</div>
                <div class="h5 mb-1"><span class="badge {{ $triageClass }}">{{ $currentTriage }}</span></div>
                <small class="text-muted">Auto: {{ $case->auto_triage_category ?: 'Pending' }} @if($case->triage_score) - Score {{ $case->triage_score }} @endif</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Bay / Bed</div>
                <div class="h5 mb-1">{{ $case->activeBayAssignment?->bed?->bed_number ?: ($case->bay->name ?? 'Unassigned') }}</div>
                <small class="text-muted">{{ $case->activeBayAssignment?->ward?->name ?: ($case->bay?->ward?->name ?: 'No emergency bed linked') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Emergency Team</div>
                <div class="small">Doctor: <span class="fw-semibold">{{ $session?->mainDoctor?->name ?? $case->assignedDoctor->name ?? 'Unassigned' }}</span></div>
                <div class="small">Nurse: <span class="fw-semibold">{{ $session?->primaryNurse?->name ?? $case->assignedNurse->name ?? 'Unassigned' }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Contributors</div>
                <div class="small fw-semibold">{{ $contributors->isNotEmpty() ? $contributors->take(3)->implode(', ') : 'None yet' }}</div>
                <small class="text-muted">Session: {{ $session?->status ?? 'Pending' }}</small>
            </div>
        </div>
    </div>
</div>

@if($criticalAlerts->isNotEmpty())
    <div class="alert alert-danger py-2">
        <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>Critical Alerts</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($criticalAlerts as $alert)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $alert }}</span>
            @endforeach
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Emergency Control Sheet</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">{{ $case->arrival_mode }}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#controlSheetModal">
                        <i class="ti ti-edit me-1"></i>Edit
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Patient</div>
                        <div class="fw-semibold">{{ $case->patient->full_name ?? 'Unknown patient' }}</div>
                        <div class="small text-muted">{{ $case->patient->patient_number ?? '' }}</div>
                        @if($case->patient?->is_temporary)
                            <span class="badge bg-warning-subtle text-warning">Temporary emergency patient</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Chief Complaint</div>
                        <div>{{ $case->chief_complaint ?: 'Not recorded' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Initial Condition</div>
                        <div>{{ $case->initial_condition ?: 'Not recorded' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Referral / Source</div>
                        <div>{{ $case->referral_facility ?: ($case->source ?: 'Not recorded') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Latest Vitals</div>
                        @if($case->latestVitals)
                            <div class="small">
                                BP {{ $case->latestVitals->blood_pressure ?? '-' }},
                                HR {{ $case->latestVitals->heart_rate ?? '-' }},
                                Temp {{ $case->latestVitals->temperature ?? '-' }},
                                SpO2 {{ $case->latestVitals->spo2 ?? '-' }}
                            </div>
                            <small class="text-muted">By {{ $case->latestVitals->recordedBy->name ?? 'Unknown' }} at {{ $case->latestVitals->recorded_at?->format('d M H:i') }}</small>
                        @else
                            <div class="text-muted">No vitals recorded</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Billing</div>
                        <div class="fw-semibold">{{ $activeInvoice?->invoice_number ?? 'No invoice yet' }}</div>
                        <small class="text-muted">Emergency care is not blocked by payment.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Vitals Trend</h5>
                <span class="badge bg-light text-dark">{{ $case->vitals->count() }} readings</span>
            </div>
            <div class="card-body">
                @if($case->vitals->isNotEmpty())
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-6 col-md-3"><div class="vitals-val">{{ $latestVitals?->blood_pressure ?? '-' }}</div><div class="vitals-label">BP</div></div>
                        <div class="col-6 col-md-3"><div class="vitals-val">{{ $latestVitals?->heart_rate ?? '-' }}</div><div class="vitals-label">HR</div></div>
                        <div class="col-6 col-md-3"><div class="vitals-val">{{ $latestVitals?->respiratory_rate ?? '-' }}</div><div class="vitals-label">RR</div></div>
                        <div class="col-6 col-md-3"><div class="vitals-val">{{ $latestVitals?->spo2 ?? '-' }}</div><div class="vitals-label">SpO2</div></div>
                    </div>
                    <div class="er-chart-wrap">
                        <canvas id="emergencyVitalsChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3 er-scroll">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Time</th><th>BP</th><th>HR</th><th>RR</th><th>Temp</th><th>SpO2</th><th>By</th></tr></thead>
                            <tbody>
                                @foreach($case->vitals->sortByDesc('recorded_at') as $vital)
                                    <tr>
                                        <td>{{ $vital->recorded_at?->format('d M H:i') }}</td>
                                        <td>{{ $vital->blood_pressure ?? '-' }}</td>
                                        <td>{{ $vital->heart_rate ?? '-' }}</td>
                                        <td>{{ $vital->respiratory_rate ?? '-' }}</td>
                                        <td>{{ $vital->temperature ?? '-' }}</td>
                                        <td>{{ $vital->spo2 ?? '-' }}</td>
                                        <td>{{ $vital->recordedBy->name ?? 'Unknown' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted py-3">No emergency vitals recorded yet.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Clinical Notes</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.notes.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-4">
                        <select class="form-select" name="note_type" required>
                            @foreach(['DOCTOR_ASSESSMENT','NURSING_NOTE','RESUSCITATION_NOTE','OBSERVATION_NOTE','GENERAL_NOTE'] as $type)
                                <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><textarea class="form-control" name="content" rows="2" placeholder="Add rapid emergency note" required></textarea></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add Note</button></div>
                </form>
                <div class="list-group list-group-flush">
                    @forelse($case->notes->sortByDesc('created_at') as $note)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $note->note_type) }}</span>
                                <small class="text-muted">{{ $note->created_at?->format('d M Y H:i') }}</small>
                            </div>
                            <div class="mt-1">{{ $note->content }}</div>
                            <small class="text-muted">Entered by {{ $note->creator->name ?? 'Unknown user' }}</small>
                        </div>
                    @empty
                        <div class="text-muted py-3">No emergency notes yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Emergency Timeline</h5></div>
            <div class="card-body">
                @forelse($case->logs->sortByDesc('created_at') as $log)
                    <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                        <div class="text-muted small" style="min-width: 110px;">{{ $log->created_at?->format('d M H:i') }}</div>
                        <div>
                            <div class="fw-semibold">{{ $log->title }}</div>
                            @if($log->description)<div class="small">{{ $log->description }}</div>@endif
                            <small class="text-muted">{{ $log->action }} by {{ $log->performedBy->name ?? 'System' }}</small>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No timeline entries yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Triage</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#triageModal">
                    <i class="ti ti-activity-heartbeat me-1"></i>Record
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge {{ $triageClass }}">{{ $currentTriage }}</span>
                    <span class="small text-muted">Score {{ $case->triage_score ?? '-' }}</span>
                </div>
                <div class="small mb-1">Auto: <span class="fw-semibold">{{ $case->auto_triage_category ?: 'Pending' }}</span></div>
                <div class="small mb-1">AVPU: <span class="fw-semibold">{{ $case->avpu ?: 'Not recorded' }}</span></div>
                <div class="small mb-2">Pain: <span class="fw-semibold">{{ $case->pain_score ?? 'Not recorded' }}</span></div>
                @if($case->triage_reasons)
                    <div class="border rounded p-2 small">
                        <div class="fw-semibold text-danger mb-1">Automated reasons</div>
                        <ul class="mb-0 ps-3">
                            @foreach(array_slice($case->triage_reasons, 0, 3) as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-muted small">No triage score has been calculated yet.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Bay and Team</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.bay.assign', $case) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Assign Ward / Bed / Bay</label>
                    <select class="form-select mb-2" name="ward_id">
                        <option value="">No ward link</option>
                        @foreach($wards as $ward)
                            <option value="{{ $ward->id }}" @selected($case->activeBayAssignment?->ward_id === $ward->id)>{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select mb-2" name="bed_id">
                        <option value="">No bed link</option>
                        @foreach($wards as $ward)
                            @foreach($ward->beds as $bed)
                                <option value="{{ $bed->id }}" @selected($case->activeBayAssignment?->bed_id === $bed->id)>{{ $ward->name }} - {{ $bed->bed_number }} ({{ $bed->status }})</option>
                            @endforeach
                        @endforeach
                    </select>
                    <div class="input-group mb-2">
                        <select class="form-select" name="emergency_bay_id" required>
                            @foreach($bays as $bay)
                                <option value="{{ $bay->id }}" @selected($case->emergency_bay_id === $bay->id)>{{ $bay->name }} - {{ $bay->status }}{{ $bay->bed?->bed_number ? ' - '.$bay->bed->bed_number : '' }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-primary" type="submit">Assign</button>
                    </div>
                    <div class="form-check small">
                        <input class="form-check-input" type="checkbox" name="override" value="1" id="overrideBay">
                        <label class="form-check-label" for="overrideBay">Override occupied bed/bay when clinically required</label>
                    </div>
                </form>
                <div class="border-top pt-2 mb-3">
                    <div class="er-section-title mb-2">Assignment History</div>
                    @forelse($case->bayAssignments->sortByDesc('assigned_at') as $assignment)
                        <div class="small mb-2">
                            <div class="fw-semibold">{{ $assignment->ward?->name ?: 'Emergency' }} / {{ $assignment->bed?->bed_number ?: ($assignment->emergencyBay?->name ?: 'Bay') }}</div>
                            <span class="text-muted">{{ $assignment->status }} - {{ $assignment->assigned_at?->format('d M H:i') }}</span>
                        </div>
                    @empty
                        <div class="small text-muted">No assignment history yet.</div>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('admin.emergency.cases.update', $case) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-2">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" name="assigned_doctor_id">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($case->assigned_doctor_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Nurse</label>
                        <select class="form-select" name="assigned_nurse_id">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($case->assigned_nurse_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Emergency Status</label>
                        <select class="form-select" name="emergency_status">
                            @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION','CANCELLED'] as $status)
                                <option value="{{ $status }}" @selected($case->emergency_status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-outline-primary w-100" type="submit">Update Case</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Medication / MAR</h5>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.emergency.mar-chart', $case->visit) }}">Open MAR</a>
            </div>
            <div class="card-body">
                <div class="row g-2 text-center mb-3">
                    <div class="col-3"><div class="fw-bold text-danger">{{ $medCounts['due_now'] }}</div><small class="text-muted">Due</small></div>
                    <div class="col-3"><div class="fw-bold text-warning">{{ $medCounts['overdue'] }}</div><small class="text-muted">Late</small></div>
                    <div class="col-3"><div class="fw-bold text-primary">{{ $medCounts['upcoming'] }}</div><small class="text-muted">Next</small></div>
                    <div class="col-3"><div class="fw-bold text-success">{{ $medCounts['administered_today'] }}</div><small class="text-muted">Given</small></div>
                </div>
                <form method="POST" action="{{ route('admin.emergency.medications.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <input class="form-control form-control-sm mb-1" data-filter-target="medicationProductSelect" placeholder="Search medication">
                        <select class="form-select" name="product_id" id="medicationProductSelect" required>
                            <option value="">Select medication/product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-emergency-stock="{{ $product->emergency_available_quantity ?? 0 }}" data-pharmacy-stock="{{ $product->pharmacy_available_quantity ?? 0 }}">
                                    {{ $product->name }} - ER {{ number_format($product->emergency_available_quantity ?? 0, 0) }} / Pharmacy {{ number_format($product->pharmacy_available_quantity ?? 0, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Emergency stock is shown first; pharmacy remains the wider stock source for formal dispensing.</small>
                    </div>
                    <div class="col-4"><input class="form-control" name="dose" placeholder="Dose" required></div>
                    <div class="col-4"><input class="form-control" name="route" placeholder="Route" required></div>
                    <div class="col-4">
                        <select class="form-select" name="frequency_code" required>
                            @foreach($frequencies as $frequency)
                                <option value="{{ $frequency->code }}">{{ $frequency->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4"><input class="form-control" type="number" min="1" name="duration_value" value="1" placeholder="Duration"></div>
                    <div class="col-4">
                        <select class="form-select" name="duration_unit">
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                        </select>
                    </div>
                    <div class="col-4"><input class="form-control" type="number" min="0" name="quantity_ordered" placeholder="Qty override"></div>
                    <div class="col-12"><input class="form-control" type="datetime-local" name="start_at"></div>
                    <div class="col-12"><div class="small text-muted" id="medicationQuantityHint">Quantity is calculated from frequency and duration unless overridden.</div></div>
                    <div class="col-12"><textarea class="form-control" name="instructions" rows="2" placeholder="Instructions"></textarea></div>
                    <div class="col-12"><button class="btn btn-outline-danger w-100" type="submit">Order Emergency Medication</button></div>
                </form>
                @forelse($case->medicationOrders as $order)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $order->display_name }}</div>
                            <span class="badge bg-light text-dark">{{ $order->status }}</span>
                        </div>
                        <small class="text-muted">{{ $order->dose }} {{ $order->route }} {{ $order->frequency?->code }} - Qty {{ $order->quantity_ordered ?? '-' }}</small>
                        @if($order->schedules->isNotEmpty())
                            <div class="small mt-1">Next: {{ optional($order->schedules->whereIn('status', ['SCHEDULED', 'DUE'])->sortBy('scheduled_at')->first())->scheduled_at?->format('d M H:i') ?: 'No pending doses' }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No emergency medication orders yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Investigations</h5>
                <span class="badge bg-light text-dark">{{ $case->labRequests->count() }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.investigations.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <select class="form-select" name="target_department_id" id="emergencyInvestigationDepartment" required>
                            <option value="">Investigation department</option>
                            @foreach($investigationDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('target_department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="service_id" id="emergencyInvestigationService" data-old-value="{{ old('service_id') }}" required disabled>
                            <option value="">Select department first</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="urgency">
                            <option value="emergency" @selected(old('urgency', 'emergency') === 'emergency')>Emergency</option>
                            <option value="urgent" @selected(old('urgency') === 'urgent')>Urgent</option>
                            <option value="routine" @selected(old('urgency') === 'routine')>Routine</option>
                        </select>
                    </div>
                    <div class="col-12"><textarea class="form-control" name="clinical_info" rows="2" placeholder="Clinical information">{{ old('clinical_info') }}</textarea></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100" type="submit">Request Investigation</button></div>
                </form>
                @forelse($case->labRequests as $request)
                    @php $requestItems = $request->items->map(fn ($item) => $item->display_name ?? $item->name ?? $item->labTest?->name)->filter()->implode(', '); @endphp
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $requestItems ?: $request->request_number }}</div>
                            <span class="badge bg-light text-dark">{{ $request->status }}</span>
                        </div>
                        <small class="text-muted">{{ $request->targetDepartment->name ?? 'Department pending' }} - {{ $request->urgency ?? 'routine' }} - Requested by {{ $request->requestedBy->name ?? 'Unknown' }}</small>
                    </div>
                @empty
                    <div class="text-muted">No emergency investigations yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Procedures</h5>
                <span class="badge bg-light text-dark">{{ $case->procedureRequests->count() }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.procedures.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <select class="form-select" name="department_id" id="emergencyProcedureDepartment" required>
                            <option value="">Procedure department</option>
                            @foreach($procedureDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="service_catalog_id" id="emergencyProcedureService" data-old-value="{{ old('service_catalog_id') }}" required disabled>
                            <option value="">Select department first</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="priority" required>
                            <option value="emergency" @selected(old('priority', 'emergency') === 'emergency')>Emergency</option>
                            <option value="urgent" @selected(old('priority') === 'urgent')>Urgent</option>
                            <option value="routine" @selected(old('priority') === 'routine')>Routine</option>
                        </select>
                    </div>
                    <div class="col-12"><textarea class="form-control" name="indication" rows="2" placeholder="Indication" required>{{ old('indication') }}</textarea></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100" type="submit">Request Procedure</button></div>
                </form>
                @forelse($case->procedureRequests as $request)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $request->service?->name ?? $request->procedure_name ?? $request->request_number }}</div>
                            <span class="badge bg-light text-dark">{{ $request->status }}</span>
                        </div>
                        <small class="text-muted">{{ $request->department->name ?? 'Department pending' }} - {{ $request->priority ?? 'routine' }} - Requested by {{ $request->requestingDoctor->name ?? 'Unknown' }}</small>
                    </div>
                @empty
                    <div class="text-muted">No emergency procedures yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Consumables</h5>
                <span class="badge bg-light text-dark">Emergency stock</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.consumables.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <input class="form-control form-control-sm mb-1" data-filter-target="emergencyConsumableSelect" placeholder="Search consumable">
                        <select class="form-select" name="product_id" id="emergencyConsumableSelect" required>
                            <option value="">Select consumable</option>
                            @foreach($consumableProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} - ER {{ number_format($product->emergency_available_quantity ?? 0, 0) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4"><input class="form-control" type="number" min="0.0001" step="0.01" name="quantity" value="1" required></div>
                    <div class="col-8"><input class="form-control" name="notes" placeholder="Usage notes"></div>
                    <div class="col-12"><button class="btn btn-outline-danger w-100" type="submit">Use Consumable</button></div>
                </form>
                <div class="er-scroll">
                    @forelse($case->consumableUsages->sortByDesc('used_at') as $usage)
                        <div class="border rounded p-2 mb-2 small">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold">{{ $usage->product->name ?? 'Consumable' }}</span>
                                <span>{{ number_format((float) $usage->quantity_used, 2) }}</span>
                            </div>
                            <span class="text-muted">{{ $usage->used_at?->format('d M H:i') }} - {{ $usage->user->name ?? 'Unknown' }}</span>
                        </div>
                    @empty
                        <div class="text-muted">No emergency consumables recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Tasks and Monitoring</h5>
                <span class="badge bg-light text-dark">{{ $pendingTasks->count() }} pending</span>
            </div>
            <div class="card-body er-scroll">
                @forelse($case->clinicalTasks->sortByDesc('scheduled_at') as $task)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <span class="badge bg-light text-dark">{{ $task->status }}</span>
                        </div>
                        <small class="text-muted">{{ $task->priority }} - {{ $task->scheduled_at?->format('d M H:i') ?: 'No schedule' }} - {{ $task->assignedUser->name ?? $task->assigned_role ?? 'Unassigned' }}</small>
                    </div>
                @empty
                    <div class="text-muted">No emergency monitoring tasks yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Billing</h5>
                @if($activeInvoice)
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.billing.invoices.show', $activeInvoice) }}">Invoice</a>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.services.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-8">
                        <select class="form-select" name="service_catalog_id" required>
                            <option value="">Add billable emergency service</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} - {{ $service->formatted_price }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4"><input class="form-control" type="number" name="quantity" min="1" value="1"></div>
                    <div class="col-12"><button class="btn btn-outline-success w-100" type="submit">Add to Invoice</button></div>
                </form>
                @if($billingGroups)
                    @foreach($billingGroups as $group => $items)
                        <div class="mb-2">
                            <div class="er-section-title mb-1">{{ $group }}</div>
                            @foreach($items as $item)
                                <div class="d-flex justify-content-between small border-bottom py-1">
                                    <span>{{ $item->description }}</span>
                                    <span>{{ number_format((float) $item->total, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <div class="text-muted">No emergency invoice items yet.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h5 class="card-title mb-0">Disposition</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.emergency.disposition.store', $case) }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Disposition</label>
                <select class="form-select" name="disposition" required>
                    @foreach(['ADMITTED','DISCHARGED','TRANSFERRED_TO_OPD','TRANSFERRED_TO_THEATRE','REFERRED_OUT','LEFT_AGAINST_MEDICAL_ADVICE','ABSCONDED','DIED','DEAD_ON_ARRIVAL'] as $disposition)
                        <option value="{{ $disposition }}" @selected($case->disposition === $disposition)>{{ str_replace('_', ' ', $disposition) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Disposition Time</label>
                <input class="form-control" type="datetime-local" name="disposition_time" value="{{ now()->format('Y-m-d\TH:i') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Cause of Death</label>
                <input class="form-control" name="cause_of_death" placeholder="If applicable">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-danger w-100" type="submit">Record Disposition</button>
            </div>
            <div class="col-12">
                <label class="form-label">Disposition Notes</label>
                <textarea class="form-control" name="disposition_notes" rows="2">{{ $case->disposition_notes }}</textarea>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="controlSheetModal" tabindex="-1" aria-labelledby="controlSheetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.emergency.cases.update', $case) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_form" value="control_sheet">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="controlSheetModalLabel">Emergency Control Sheet</h5>
                        <div class="small text-muted">{{ $case->emergency_number }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Arrival Mode</label>
                            <select class="form-select" name="arrival_mode">
                                @foreach(['WALK_IN','AMBULANCE','POLICE','FAMILY_BROUGHT','REFERRAL','TRANSFER_FROM_OPD','TRANSFER_FROM_WARD','UNKNOWN'] as $mode)
                                    <option value="{{ $mode }}" @selected(old('arrival_mode', $case->arrival_mode) === $mode)>{{ str_replace('_', ' ', $mode) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Arrival Time</label>
                            <input class="form-control" type="datetime-local" name="arrival_time" value="{{ old('arrival_time', $case->arrival_time?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brought By</label>
                            <input class="form-control" name="brought_by" value="{{ old('brought_by', $case->brought_by) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Source</label>
                            <input class="form-control" name="source" value="{{ old('source', $case->source) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referral Facility</label>
                            <input class="form-control" name="referral_facility" value="{{ old('referral_facility', $case->referral_facility) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Status</label>
                            <select class="form-select" name="emergency_status">
                                @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION','CANCELLED'] as $status)
                                    <option value="{{ $status }}" @selected(old('emergency_status', $case->emergency_status) === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Chief Complaint</label>
                            <textarea class="form-control" name="chief_complaint" rows="3">{{ old('chief_complaint', $case->chief_complaint) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Initial Condition</label>
                            <textarea class="form-control" name="initial_condition" rows="3">{{ old('initial_condition', $case->initial_condition) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Control Sheet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="triageModal" tabindex="-1" aria-labelledby="triageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.emergency.triage.store', $case) }}">
                @csrf
                <input type="hidden" name="_form" value="triage">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="triageModalLabel">Record Emergency Triage</h5>
                        <div class="small text-muted">{{ $case->emergency_number }} - {{ $case->patient->full_name ?? 'Unknown patient' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Auto Category</label>
                            <input class="form-control" value="{{ $case->auto_triage_category ?: 'Calculated on save' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Final Category</label>
                            <select class="form-select" name="final_triage_category">
                                <option value="">Use automated category</option>
                                @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                                    <option value="{{ $category }}" @selected(old('final_triage_category', $currentTriage) === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">AVPU</label>
                            <select class="form-select" name="avpu">
                                <option value="">Not recorded</option>
                                @foreach(['A' => 'Alert', 'V' => 'Voice', 'P' => 'Pain', 'U' => 'Unresponsive'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('avpu', $case->avpu) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pain Score</label>
                            <input class="form-control" type="number" min="0" max="10" name="pain_score" value="{{ old('pain_score', $case->pain_score) }}" placeholder="0-10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Danger Signs</label>
                            <div class="row g-2">
                                @foreach($dangerSignOptions as $value => $label)
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="danger_signs[]" value="{{ $value }}" id="triage_danger_{{ $value }}" @checked(in_array($value, old('danger_signs', $case->danger_signs ?: []), true))>
                                            <label class="form-check-label" for="triage_danger_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Override Reason</label>
                            <input class="form-control" name="triage_override_reason" value="{{ old('triage_override_reason', $case->triage_override_reason) }}" placeholder="Required only if final category differs from auto category">
                        </div>
                        <div class="col-md-3"><input class="form-control" name="blood_pressure_systolic" value="{{ old('blood_pressure_systolic') }}" placeholder="BP Sys"></div>
                        <div class="col-md-3"><input class="form-control" name="blood_pressure_diastolic" value="{{ old('blood_pressure_diastolic') }}" placeholder="BP Dia"></div>
                        <div class="col-md-2"><input class="form-control" name="heart_rate" value="{{ old('heart_rate') }}" placeholder="HR"></div>
                        <div class="col-md-2"><input class="form-control" name="respiratory_rate" value="{{ old('respiratory_rate') }}" placeholder="RR"></div>
                        <div class="col-md-2"><input class="form-control" name="spo2" value="{{ old('spo2') }}" placeholder="SpO2"></div>
                        <div class="col-md-6"><input class="form-control" name="temperature" value="{{ old('temperature') }}" placeholder="Temperature"></div>
                        <div class="col-md-6"><input class="form-control" name="triage_score" value="{{ old('triage_score') }}" placeholder="Manual score"></div>
                        <div class="col-12">
                            <textarea class="form-control" name="triage_notes" rows="3" placeholder="Triage notes">{{ old('triage_notes', $case->triage_notes) }}</textarea>
                        </div>
                        @if($case->triage_reasons || $case->triage_warnings)
                            <div class="col-12">
                                <div class="border rounded p-3 small">
                                    @if($case->triage_reasons)
                                        <div class="fw-semibold text-danger mb-1">Automated reasons</div>
                                        <ul class="mb-2 ps-3">
                                            @foreach($case->triage_reasons as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if($case->triage_warnings)
                                        <div class="fw-semibold text-warning mb-1">Warnings</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach($case->triage_warnings as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Triage</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var vitalsChartData = @json($vitalsChartData);
    var chartEl = document.getElementById('emergencyVitalsChart');
    if (chartEl && window.Chart && vitalsChartData.labels && vitalsChartData.labels.length) {
        new Chart(chartEl, {
            type: 'line',
            data: {
                labels: vitalsChartData.labels,
                datasets: [
                    { label: 'Systolic BP', data: vitalsChartData.systolic, borderColor: '#6f42c1', tension: .3, spanGaps: true },
                    { label: 'Diastolic BP', data: vitalsChartData.diastolic, borderColor: '#20c997', tension: .3, spanGaps: true },
                    { label: 'Heart Rate', data: vitalsChartData.heart_rate, borderColor: '#dc3545', tension: .3, spanGaps: true },
                    { label: 'Respiratory Rate', data: vitalsChartData.respiratory_rate, borderColor: '#0d6efd', tension: .3, spanGaps: true },
                    { label: 'Temperature', data: vitalsChartData.temperature, borderColor: '#fd7e14', tension: .3, spanGaps: true },
                    { label: 'SpO2', data: vitalsChartData.spo2, borderColor: '#198754', tension: .3, spanGaps: true }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: false } } }
        });
    }

    var modalToOpen = @json($shouldOpenTriageModal ? 'triageModal' : ($shouldOpenControlSheetModal ? 'controlSheetModal' : null));
    if (modalToOpen && window.bootstrap) {
        var targetModal = document.getElementById(modalToOpen);
        if (targetModal) {
            bootstrap.Modal.getOrCreateInstance(targetModal).show();
        }
    }

    document.querySelectorAll('[data-filter-target]').forEach(function (input) {
        var select = document.getElementById(input.dataset.filterTarget);
        if (!select) return;
        var options = Array.from(select.options).map(function (option) { return { option: option, text: option.text.toLowerCase() }; });
        input.addEventListener('input', function () {
            var needle = input.value.toLowerCase().trim();
            options.forEach(function (entry, index) {
                entry.option.hidden = index > 0 && needle && !entry.text.includes(needle);
            });
        });
    });

    function formatEmergencyServiceLabel(service) {
        var parts = [service.name];
        if (service.code) parts.push('[' + service.code + ']');
        if (service.price !== null && service.price !== undefined && service.price !== '') parts.push('GH' + Number(service.price).toFixed(2));
        return parts.join(' - ');
    }

    function setupDepartmentServiceSelect(departmentSelectId, serviceSelectId, services, emptyDepartmentLabel, selectServiceLabel, emptyServiceLabel) {
        var departmentSelect = document.getElementById(departmentSelectId);
        var serviceSelect = document.getElementById(serviceSelectId);
        if (!departmentSelect || !serviceSelect) return;

        var oldValue = serviceSelect.dataset.oldValue || '';
        function renderServices() {
            var departmentId = departmentSelect.value;
            serviceSelect.innerHTML = '';

            if (!departmentId) {
                serviceSelect.disabled = true;
                serviceSelect.append(new Option(emptyDepartmentLabel, ''));
                return;
            }

            var matches = services.filter(function (service) {
                return String(service.department_id) === String(departmentId);
            });

            serviceSelect.disabled = matches.length === 0;
            serviceSelect.append(new Option(matches.length ? selectServiceLabel : emptyServiceLabel, ''));

            matches.forEach(function (service) {
                var option = new Option(formatEmergencyServiceLabel(service), service.id);
                option.selected = oldValue && String(oldValue) === String(service.id);
                serviceSelect.append(option);
            });
        }

        departmentSelect.addEventListener('change', function () {
            oldValue = '';
            renderServices();
        });
        renderServices();
    }

    setupDepartmentServiceSelect(
        'emergencyInvestigationDepartment',
        'emergencyInvestigationService',
        @json($investigationServiceOptions),
        'Select department first',
        'Investigation service',
        'No investigation services for this department'
    );
    setupDepartmentServiceSelect(
        'emergencyProcedureDepartment',
        'emergencyProcedureService',
        @json($procedureServiceOptions),
        'Select department first',
        'Procedure service',
        'No procedure services for this department'
    );

    var frequencyMap = @json($frequencyMap);
    var medicationForm = document.querySelector('form[action="{{ route('admin.emergency.medications.store', $case) }}"]');
    var hint = document.getElementById('medicationQuantityHint');
    function updateMedicationHint() {
        if (!medicationForm || !hint) return;
        var frequencyCode = medicationForm.querySelector('[name="frequency_code"]')?.value;
        var quantity = medicationForm.querySelector('[name="quantity_ordered"]')?.value;
        var durationValue = parseInt(medicationForm.querySelector('[name="duration_value"]')?.value || '1', 10);
        var durationUnit = medicationForm.querySelector('[name="duration_unit"]')?.value || 'days';
        if (quantity) {
            hint.textContent = 'Manual quantity override: ' + quantity + ' unit(s).';
            return;
        }
        var frequency = frequencyMap[frequencyCode] || {};
        var days = durationUnit === 'weeks' ? durationValue * 7 : (durationUnit === 'months' ? durationValue * 30 : durationValue);
        var calculated = frequency.stat ? 1 : Math.max(1, days * (frequency.times || 0));
        hint.textContent = frequency.prn ? 'PRN orders use the quantity override when supplied.' : 'Estimated quantity: ' + calculated + ' dose(s).';
    }
    if (medicationForm) {
        medicationForm.querySelectorAll('[name="frequency_code"], [name="duration_value"], [name="duration_unit"], [name="quantity_ordered"]').forEach(function (field) {
            field.addEventListener('input', updateMedicationHint);
            field.addEventListener('change', updateMedicationHint);
        });
        updateMedicationHint();
    }
});
</script>
@endpush

@if($temporaryPatient)
@can('patients.merge.confirm_identity')
<div class="modal fade" id="confirmEmergencyIdentityModal" tabindex="-1" aria-labelledby="confirmEmergencyIdentityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <div>
                    <h5 class="modal-title" id="confirmEmergencyIdentityModalLabel"><i class="ti ti-id-badge-2 me-1"></i>Confirm Temporary Emergency Identity</h5>
                    <div class="small text-muted">{{ $temporaryPatient->patient_number }} - {{ $temporaryPatient->full_name }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ old('_identity_action') === 'register' ? '' : 'active' }}" id="existing-identity-tab" data-bs-toggle="tab" data-bs-target="#existing-identity-pane" type="button" role="tab" aria-controls="existing-identity-pane" aria-selected="{{ old('_identity_action') === 'register' ? 'false' : 'true' }}">
                            <i class="ti ti-users me-1"></i>Existing Patient
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ old('_identity_action') === 'register' ? 'active' : '' }}" id="register-identity-tab" data-bs-toggle="tab" data-bs-target="#register-identity-pane" type="button" role="tab" aria-controls="register-identity-pane" aria-selected="{{ old('_identity_action') === 'register' ? 'true' : 'false' }}">
                            <i class="ti ti-user-plus me-1"></i>Register Patient
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ old('_identity_action') === 'register' ? '' : 'show active' }}" id="existing-identity-pane" role="tabpanel" aria-labelledby="existing-identity-tab" tabindex="0">
                        <form method="POST" action="{{ route('admin.emergency.cases.confirm-identity', $case) }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="_identity_action" value="existing">
                            <div class="col-md-7">
                                <label class="form-label">Confirmed Patient Folder <span class="text-danger">*</span></label>
                                <select name="confirmed_patient_id" class="form-select @error('confirmed_patient_id') is-invalid @enderror" required>
                                    <option value="">Select confirmed patient</option>
                                    @foreach($identityCandidates as $candidate)
                                        <option value="{{ $candidate->id }}" @selected(old('confirmed_patient_id') == $candidate->id)>{{ $candidate->patient_number }} - {{ $candidate->full_name }} - {{ $candidate->phone }}</option>
                                    @endforeach
                                </select>
                                @error('confirmed_patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Confirmation Note</label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="ID confirmed by family, Ghana Card, or staff verification">
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" name="confirmed" value="1" id="existingIdentityConfirmed" required @checked(old('confirmed'))>
                                    <label class="form-check-label" for="existingIdentityConfirmed">I have verified this temporary patient belongs to the selected folder.</label>
                                    @error('confirmed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button class="btn btn-warning" type="submit"><i class="ti ti-git-merge me-1"></i>Merge Into Existing Folder</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ old('_identity_action') === 'register' ? 'show active' : '' }}" id="register-identity-pane" role="tabpanel" aria-labelledby="register-identity-tab" tabindex="0">
                        <form method="POST" action="{{ route('admin.emergency.cases.register-identity', $case) }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="_identity_action" value="register">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $temporaryPatient->first_name) }}" required>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Other Names</label>
                                <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names', $temporaryPatient->other_names) }}">
                                @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $temporaryPatient->last_name) }}" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $temporaryPatient->date_of_birth?->format('Y-m-d')) }}" required>
                                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                    <option value="">Select Gender</option>
                                    @foreach(\App\Enums\Gender::cases() as $gender)
                                        <option value="{{ $gender->value }}" @selected(old('gender', $temporaryPatient->getRawOriginal('gender')) === $gender->value)>{{ $gender->label() }}</option>
                                    @endforeach
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $temporaryPatient->phone === '0000000000' ? '' : $temporaryPatient->phone) }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Secondary Phone</label>
                                <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary', $temporaryPatient->phone_secondary) }}">
                                @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $temporaryPatient->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ghana Card Number</label>
                                <input type="text" name="ghana_card_number" class="form-control @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number', $temporaryPatient->ghana_card_number) }}">
                                @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                    <option value="">Select</option>
                                    @foreach(\App\Enums\BloodGroup::cases() as $bloodGroup)
                                        <option value="{{ $bloodGroup->value }}" @selected(old('blood_group', $temporaryPatient->getRawOriginal('blood_group')) === $bloodGroup->value)>{{ $bloodGroup->label() }}</option>
                                    @endforeach
                                </select>
                                @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                                    <option value="">Select</option>
                                    @foreach(\App\Enums\MaritalStatus::cases() as $maritalStatus)
                                        <option value="{{ $maritalStatus->value }}" @selected(old('marital_status', $temporaryPatient->getRawOriginal('marital_status')) === $maritalStatus->value)>{{ $maritalStatus->label() }}</option>
                                    @endforeach
                                </select>
                                @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Region</label>
                                <input type="text" name="region" class="form-control @error('region') is-invalid @enderror" value="{{ old('region', $temporaryPatient->region) }}">
                                @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $temporaryPatient->city) }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Town</label>
                                <input type="text" name="town" class="form-control @error('town') is-invalid @enderror" value="{{ old('town', $temporaryPatient->town) }}">
                                @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Digital Address</label>
                                <input type="text" name="digital_address" class="form-control @error('digital_address') is-invalid @enderror" value="{{ old('digital_address', $temporaryPatient->digital_address) }}">
                                @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $temporaryPatient->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Confirmation Note</label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="Patient identified during emergency registration">
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" name="confirmed" value="1" id="registerIdentityConfirmed" required @checked(old('confirmed'))>
                                    <label class="form-check-label" for="registerIdentityConfirmed">I have verified these details and want to register this emergency patient.</label>
                                    @error('confirmed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button class="btn btn-warning" type="submit"><i class="ti ti-user-plus me-1"></i>Register and Confirm</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var shouldOpen = @json($shouldOpenIdentityModal);
        if (!shouldOpen || !window.bootstrap) {
            return;
        }

        var modalEl = document.getElementById('confirmEmergencyIdentityModal');
        if (!modalEl) {
            return;
        }

        var tabSelector = @json($identityModalTabSelector);
        var tabEl = document.querySelector(tabSelector);
        if (tabEl && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
</script>
@endpush
@endcan
@endif
@endsection
