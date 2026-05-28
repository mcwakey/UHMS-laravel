@extends('layouts.app')
@section('title', $case->emergency_number)

@php
    $triageClass = $case->triage_badge_class;
    $activeInvoice = $case->visit?->latestInvoice;
    $temporaryPatient = $case->patient?->is_temporary ? $case->patient : null;
    $identityAction = old('_identity_action');
    $shouldOpenIdentityModal = $errors->any() && in_array($identityAction, ['existing', 'register'], true);
    $identityModalTabSelector = $identityAction === 'register' ? '#register-identity-tab' : '#existing-identity-tab';
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">{{ $case->emergency_number }}</h4>
        <p class="text-muted mb-0">{{ $case->patient->full_name ?? 'Unknown patient' }} - {{ $case->visit->visit_number ?? 'No visit number' }}</p>
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
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Triage Category</div>
                <div class="h5 mb-1"><span class="badge {{ $triageClass }}">{{ $case->triage_category ?? 'UNTRIAGED' }}</span></div>
                <small class="text-muted">{{ $case->triage_notes ?: 'No triage notes yet' }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Current Status</div>
                <div class="h5 mb-1">{{ str_replace('_', ' ', $case->emergency_status) }}</div>
                <small class="text-muted">Arrived {{ $case->arrival_time?->format('d M Y H:i') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Bay / Location</div>
                <div class="h5 mb-1">{{ $case->bay->name ?? 'Unassigned' }}</div>
                <small class="text-muted">{{ $case->bay ? str_replace('_', ' ', $case->bay->bay_type) : 'Assign a bay when ready' }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-0 bg-light">
            <div class="card-body">
                <div class="text-muted small">Assigned Team</div>
                <div class="small">Doctor: <span class="fw-semibold">{{ $case->assignedDoctor->name ?? 'Unassigned' }}</span></div>
                <div class="small">Nurse: <span class="fw-semibold">{{ $case->assignedNurse->name ?? 'Unassigned' }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Emergency Control Sheet</h5>
                <span class="badge bg-light text-dark">{{ $case->arrival_mode }}</span>
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
            <div class="card-header"><h5 class="card-title mb-0">Triage</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.triage.store', $case) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="triage_category" required>
                            @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                                <option value="{{ $category }}" @selected($case->triage_category === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><input class="form-control" name="blood_pressure_systolic" placeholder="BP Sys"></div>
                        <div class="col-6"><input class="form-control" name="blood_pressure_diastolic" placeholder="BP Dia"></div>
                        <div class="col-4"><input class="form-control" name="heart_rate" placeholder="HR"></div>
                        <div class="col-4"><input class="form-control" name="respiratory_rate" placeholder="RR"></div>
                        <div class="col-4"><input class="form-control" name="spo2" placeholder="SpO2"></div>
                        <div class="col-6"><input class="form-control" name="temperature" placeholder="Temp"></div>
                        <div class="col-6"><input class="form-control" name="triage_score" placeholder="Score"></div>
                    </div>
                    <textarea class="form-control mt-2" name="triage_notes" rows="2" placeholder="Triage notes">{{ $case->triage_notes }}</textarea>
                    <button class="btn btn-primary w-100 mt-2" type="submit">Save Triage</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">Bay and Team</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.bay.assign', $case) }}" class="mb-3">
                    @csrf
                    <label class="form-label">Assign Bay</label>
                    <div class="input-group">
                        <select class="form-select" name="emergency_bay_id" required>
                            @foreach($bays as $bay)
                                <option value="{{ $bay->id }}" @selected($case->emergency_bay_id === $bay->id)>{{ $bay->name }} - {{ $bay->status }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-primary" type="submit">Assign</button>
                    </div>
                </form>
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
            <div class="card-header"><h5 class="card-title mb-0">Medication / MAR</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.medications.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <select class="form-select" name="product_id" required>
                            <option value="">Select medication/product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
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
                    <div class="col-6"><input class="form-control" type="number" min="0" name="quantity_ordered" placeholder="Qty"></div>
                    <div class="col-6"><input class="form-control" type="datetime-local" name="start_at"></div>
                    <div class="col-12"><textarea class="form-control" name="instructions" rows="2" placeholder="Instructions"></textarea></div>
                    <div class="col-12"><button class="btn btn-outline-danger w-100" type="submit">Order Emergency Medication</button></div>
                </form>
                @forelse($case->medicationOrders as $order)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $order->display_name }}</div>
                        <small class="text-muted">{{ $order->dose }} {{ $order->route }} {{ $order->frequency?->code }} - {{ $order->status }}</small>
                    </div>
                @empty
                    <div class="text-muted">No emergency medication orders yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Investigations</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.investigations.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12"><input class="form-control" name="test_name" placeholder="Investigation name" required></div>
                    <div class="col-12">
                        <select class="form-select" name="target_department_id">
                            <option value="">Target department</option>
                            @foreach($investigationDepartments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="urgency">
                            <option value="emergency">Emergency</option>
                            <option value="urgent">Urgent</option>
                            <option value="routine">Routine</option>
                        </select>
                    </div>
                    <div class="col-12"><textarea class="form-control" name="clinical_info" rows="2" placeholder="Clinical information"></textarea></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100" type="submit">Request Investigation</button></div>
                </form>
                @forelse($case->labRequests as $request)
                    @php $requestItems = $request->items->map(fn ($item) => $item->display_name ?? $item->name ?? $item->labTest?->name)->filter()->implode(', '); @endphp
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $requestItems ?: $request->request_number }}</div>
                        <small class="text-muted">{{ $request->targetDepartment->name ?? 'Department pending' }} - {{ $request->status }}</small>
                    </div>
                @empty
                    <div class="text-muted">No emergency investigations yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Procedures and Billing</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.emergency.procedures.store', $case) }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <select class="form-select" name="department_id" required>
                            <option value="">Procedure department</option>
                            @foreach($procedureDepartments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="service_catalog_id" required>
                            <option value="">Procedure service</option>
                            @foreach($procedureServices as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="priority" required>
                            <option value="emergency">Emergency</option>
                            <option value="urgent">Urgent</option>
                            <option value="routine">Routine</option>
                        </select>
                    </div>
                    <div class="col-12"><textarea class="form-control" name="indication" rows="2" placeholder="Indication" required></textarea></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100" type="submit">Request Procedure</button></div>
                </form>
                <form method="POST" action="{{ route('admin.emergency.services.store', $case) }}" class="row g-2">
                    @csrf
                    <div class="col-8">
                        <select class="form-select" name="service_catalog_id" required>
                            <option value="">Add billable emergency service</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4"><input class="form-control" type="number" name="quantity" min="1" value="1"></div>
                    <div class="col-12"><button class="btn btn-outline-success w-100" type="submit">Add to Invoice</button></div>
                </form>
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
