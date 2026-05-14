@extends('layouts.app')

@section('title', 'Procedure ' . $procedure->request_number)

@php
    use App\Enums\ProcedureStatus;
    $status = $procedure->status;
    $can = fn($p) => auth()->user()?->can($p) ?? false;
@endphp

@section('content')
<div class="container-fluid">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Procedure {{ $procedure->request_number }}</h3>
            <div class="text-muted">
                {{ $procedure->patient?->first_name }} {{ $procedure->patient?->last_name }}
                · Visit {{ $procedure->visit?->visit_number }}
                · {{ $procedure->service?->name }}
            </div>
        </div>
        <div>
            <span class="badge fs-6" style="background-color: {{ $status->color() }}; color:#fff;">
                {{ $status->label() }}
            </span>
            @if ($status === ProcedureStatus::COMPLETED)
                <a class="btn btn-outline-secondary btn-sm ms-2" href="{{ route('admin.theatre.report', $procedure) }}" target="_blank">View Full Report</a>
            @endif
            <a class="btn btn-outline-primary btn-sm ms-1" href="{{ route('admin.consultations.show', $procedure->visit_id) }}">Back to Visit</a>
        </div>
    </div>

    <div class="row g-3">

        {{-- LEFT: Timeline + Summary --}}
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong>Procedure Summary</strong></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Department</dt><dd class="col-7">{{ $procedure->department?->name }}</dd>
                        <dt class="col-5">Service</dt><dd class="col-7">{{ $procedure->service?->name }}</dd>
                        <dt class="col-5">Priority</dt><dd class="col-7">{{ ucfirst($procedure->priority) }}</dd>
                        <dt class="col-5">Requested By</dt><dd class="col-7">{{ $procedure->requestingDoctor?->name }}</dd>
                        <dt class="col-5">Requested At</dt><dd class="col-7">{{ optional($procedure->requested_at)->format('d M Y H:i') }}</dd>
                        <dt class="col-5">Indication</dt><dd class="col-7">{{ $procedure->indication }}</dd>
                        @if ($procedure->notes)
                            <dt class="col-5">Notes</dt><dd class="col-7">{{ $procedure->notes }}</dd>
                        @endif
                        @if ($procedure->billing_item_id)
                            <dt class="col-5">Billing</dt>
                            <dd class="col-7">
                                Invoice {{ $procedure->billingItem?->invoice?->invoice_number ?? '—' }}
                                · Item #{{ $procedure->billing_item_id }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><strong>Timeline</strong></div>
                <div class="card-body">
                    @include('theatre.partials.timeline', ['timeline' => $timeline])
                </div>
            </div>
        </div>

        {{-- RIGHT: Action forms (status-driven) --}}
        <div class="col-lg-7">

            {{-- ACCEPT / REJECT --}}
            @if ($status === ProcedureStatus::REQUESTED)
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Acceptance Decision</strong></div>
                    <div class="card-body">
                        @if ($can('procedure.accept'))
                            <form method="POST" action="{{ route('admin.theatre.accept', $procedure) }}" class="mb-3">
                                @csrf
                                <label class="form-label">Acceptance notes (optional)</label>
                                <textarea name="notes" class="form-control mb-2" rows="2"></textarea>
                                <button class="btn btn-success">Accept Procedure</button>
                            </form>
                        @endif
                        @if ($can('procedure.reject'))
                            <form method="POST" action="{{ route('admin.theatre.reject', $procedure) }}">
                                @csrf
                                <label class="form-label">Rejection reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                                <button class="btn btn-outline-danger">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- BILL --}}
            @if ($status === ProcedureStatus::ACCEPTED && $can('procedure.bill'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Generate Billing</strong></div>
                    <div class="card-body">
                        <p class="text-muted small">Adds <strong>{{ $procedure->service?->name }}</strong> to the visit invoice. Procedure cannot be scheduled until billed.</p>
                        <form method="POST" action="{{ route('admin.theatre.bill', $procedure) }}">
                            @csrf
                            <button class="btn btn-primary">Add to Visit Invoice</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- SCHEDULE --}}
            @if (in_array($status, [ProcedureStatus::BILLED, ProcedureStatus::RESCHEDULED]) && $can('procedure.schedule'))
                @include('theatre.partials.schedule-form', ['procedure'=>$procedure,'theatreRooms'=>$theatreRooms,'clinicians'=>$clinicians, 'mode'=>'schedule'])
            @endif

            {{-- RESCHEDULE --}}
            @if ($status === ProcedureStatus::SCHEDULED && $can('procedure.reschedule'))
                @include('theatre.partials.schedule-form', ['procedure'=>$procedure,'theatreRooms'=>$theatreRooms,'clinicians'=>$clinicians, 'mode'=>'reschedule'])
            @endif

            {{-- PRE-OP --}}
            @if ($status === ProcedureStatus::SCHEDULED && $can('procedure.record_preop'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Pre-op Vitals &amp; Checklist</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.preop', $procedure) }}">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-md-3"><label class="form-label small">Temp (°C)</label><input type="number" step="0.1" name="vitals[temperature]" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">BP</label><input name="vitals[blood_pressure]" placeholder="120/80" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><label class="form-label small">Pulse</label><input type="number" name="vitals[pulse]" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><label class="form-label small">RR</label><input type="number" name="vitals[respiratory_rate]" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><label class="form-label small">SpO₂</label><input type="number" name="vitals[oxygen_saturation]" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">Weight (kg)</label><input type="number" step="0.1" name="vitals[weight]" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">Pain (0-10)</label><input type="number" min="0" max="10" name="vitals[pain_score]" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">Vital notes</label><input name="vitals[notes]" class="form-control form-control-sm"></div>
                            </div>
                            <hr>
                            <label class="form-label">Pre-op Checklist</label>
                            <div class="row">
                                @foreach (['consent_signed'=>'Consent signed','fasting_confirmed'=>'Fasting confirmed','allergies_checked'=>'Allergies checked','blood_available'=>'Blood available','site_marked'=>'Surgical site marked','equipment_ready'=>'Equipment ready','anaesthesia_review_done'=>'Anaesthesia review done'] as $k=>$l)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="hidden" name="{{ $k }}" value="0">
                                            <input class="form-check-input" type="checkbox" name="{{ $k }}" id="chk_{{ $k }}" value="1">
                                            <label class="form-check-label" for="chk_{{ $k }}">{{ $l }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <label class="form-label small">Pre-op diagnosis</label>
                                <input name="pre_op_diagnosis" class="form-control form-control-sm">
                            </div>
                            <div class="mt-2">
                                <label class="form-label small">Checklist notes</label>
                                <textarea name="checklist_notes" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            @include('theatre.partials._template_fields', ['stage' => 'PRE_OP'])
                            @include('theatre.partials._consumables', ['stage' => 'PRE_OP'])
                            <button class="btn btn-primary mt-3">Save Pre-op</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ANAESTHESIA --}}
            @if ($status === ProcedureStatus::PRE_OP && $can('procedure.record_anaesthesia'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Anaesthesia Note</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.anaesthesia', $procedure) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Anaesthetist</label>
                                    <select name="anaesthetist_id" class="form-select form-select-sm">
                                        <option value="">Select…</option>
                                        @foreach ($clinicians as $u)<option value="{{ $u->id }}" @selected($procedure->schedule?->anaesthetist_id==$u->id)>{{ $u->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Type <span class="text-danger">*</span></label>
                                    <select name="anaesthesia_type" class="form-select form-select-sm" required>
                                        @foreach (['local','regional','spinal','general','sedation','other'] as $t)
                                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6"><label class="form-label small">Start time</label><input type="datetime-local" name="start_time" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">End time</label><input type="datetime-local" name="end_time" class="form-control form-control-sm"></div>
                            </div>
                            <div class="mt-2"><label class="form-label small">Pre-assessment</label><textarea name="pre_assessment" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Drugs used</label><textarea name="drugs_used" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Dosage notes</label><textarea name="dosage_notes" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Airway management</label><input name="airway_management" class="form-control form-control-sm"></div>
                            <div class="mt-2"><label class="form-label small">Monitoring</label><textarea name="monitoring_notes" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Complications</label><textarea name="complications" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Notes</label><textarea name="notes" class="form-control form-control-sm" rows="2"></textarea></div>
                            @include('theatre.partials._template_fields', ['stage' => 'ANAESTHESIA'])
                            @include('theatre.partials._consumables', ['stage' => 'ANAESTHESIA'])
                            <button class="btn btn-primary mt-3">Save Anaesthesia Note</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- START SURGERY --}}
            @if ($status === ProcedureStatus::ANAESTHESIA && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Start Surgery</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.start-surgery', $procedure) }}">
                            @csrf
                            <button class="btn btn-danger">Mark Surgery Started</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- OPERATIVE NOTE --}}
            @if (in_array($status, [ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY]) && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Operative Note</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.operative-note', $procedure) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Surgeon</label>
                                    <select name="surgeon_id" class="form-select form-select-sm">
                                        <option value="">Select…</option>
                                        @foreach ($clinicians as $u)<option value="{{ $u->id }}" @selected($procedure->schedule?->surgeon_id==$u->id)>{{ $u->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Assistant</label>
                                    <select name="assistant_surgeon_id" class="form-select form-select-sm">
                                        <option value="">Select…</option>
                                        @foreach ($clinicians as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-12"><label class="form-label small">Procedure performed <span class="text-danger">*</span></label><input name="procedure_performed" class="form-control form-control-sm" required></div>
                                <div class="col-md-6"><label class="form-label small">Pre-op diagnosis</label><input name="pre_op_diagnosis" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">Post-op diagnosis</label><input name="post_op_diagnosis" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">Incision</label><input name="incision" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">Blood loss</label><input name="blood_loss" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">Outcome</label><input name="outcome" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">Start time</label><input type="datetime-local" name="start_time" class="form-control form-control-sm"></div>
                                <div class="col-md-6"><label class="form-label small">End time</label><input type="datetime-local" name="end_time" class="form-control form-control-sm"></div>
                            </div>
                            <div class="mt-2"><label class="form-label small">Findings</label><textarea name="findings" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Technique</label><textarea name="technique" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Complications</label><textarea name="complications" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Specimens</label><input name="specimens" class="form-control form-control-sm"></div>
                            <div class="mt-2"><label class="form-label small">Implants</label><input name="implants" class="form-control form-control-sm"></div>
                            <div class="mt-2"><label class="form-label small">Notes</label><textarea name="notes" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="form-check mt-2">
                                <input type="hidden" name="completed" value="0">
                                <input class="form-check-input" type="checkbox" name="completed" id="op_completed" value="1">
                                <label class="form-check-label" for="op_completed">Surgery completed — mark as SURGERY DONE</label>
                            </div>
                            @include('theatre.partials._template_fields', ['stage' => 'OPERATIVE_NOTE'])
                            @include('theatre.partials._consumables', ['stage' => 'OPERATIVE_NOTE'])
                            <button class="btn btn-primary mt-3">Save Operative Note</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- COMPLETE SURGERY (alt) --}}
            @if ($status === ProcedureStatus::IN_SURGERY && $procedure->operativeNote && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span>Operative note recorded. Mark surgery as completed?</span>
                        <form method="POST" action="{{ route('admin.theatre.complete-surgery', $procedure) }}">
                            @csrf
                            <button class="btn btn-success btn-sm">Surgery Done</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- POST-OP --}}
            @if ($status === ProcedureStatus::SURGERY_DONE && $can('procedure.record_postop'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Post-op Note</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.postop', $procedure) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-4"><label class="form-label small">Recovery status</label><input name="recovery_status" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><label class="form-label small">Pain (0-10)</label><input type="number" min="0" max="10" name="pain_score" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><label class="form-label small">Consciousness</label><input name="consciousness_level" class="form-control form-control-sm"></div>
                                <div class="col-md-3">
                                    <label class="form-label small">Transfer to</label>
                                    <select name="transfer_destination" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach (['ward','icu','outpatient','emergency_obs','recovery_room'] as $d)
                                            <option value="{{ $d }}">{{ \Illuminate\Support\Str::headline($d) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2"><label class="form-label small">Post-op instructions</label><textarea name="post_op_instructions" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Medications</label><textarea name="medications" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Complications</label><textarea name="complications" class="form-control form-control-sm" rows="2"></textarea></div>
                            <div class="mt-2"><label class="form-label small">Notes</label><textarea name="notes" class="form-control form-control-sm" rows="2"></textarea></div>
                            <hr>
                            <label class="form-label small">Post-op Vitals (optional)</label>
                            <div class="row g-2">
                                <div class="col-md-3"><input type="number" step="0.1" name="vitals[temperature]" placeholder="Temp" class="form-control form-control-sm"></div>
                                <div class="col-md-3"><input name="vitals[blood_pressure]" placeholder="BP" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><input type="number" name="vitals[pulse]" placeholder="Pulse" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><input type="number" name="vitals[respiratory_rate]" placeholder="RR" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><input type="number" name="vitals[oxygen_saturation]" placeholder="SpO₂" class="form-control form-control-sm"></div>
                            </div>
                            @include('theatre.partials._template_fields', ['stage' => 'POST_OP'])
                            @include('theatre.partials._consumables', ['stage' => 'POST_OP'])
                            <button class="btn btn-primary mt-3">Save Post-op</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- COMPLETE --}}
            @if ($status === ProcedureStatus::POST_OP && $can('procedure.complete'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>Complete Procedure</strong></div>
                    <div class="card-body">
                        <p class="text-muted small">All three notes (anaesthesia, operative, post-op) are recorded. Mark this procedure complete to close the workflow.</p>
                        <form method="POST" action="{{ route('admin.theatre.complete', $procedure) }}">
                            @csrf
                            <button class="btn btn-success">Mark Procedure Completed</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- CANCEL (available while open) --}}
            @if (! in_array($status, [ProcedureStatus::COMPLETED, ProcedureStatus::CANCELLED, ProcedureStatus::REJECTED]) && $can('procedure.cancel'))
                <div class="card shadow-sm mb-3 border-danger">
                    <div class="card-header bg-light"><strong class="text-danger">Cancel Procedure</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.cancel', $procedure) }}">
                            @csrf
                            <label class="form-label small">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this procedure? Any billed item will be voided.')">Cancel Procedure</button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
