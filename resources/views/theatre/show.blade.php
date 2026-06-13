@extends('layouts.app')

@section('title', __('theatre.procedure_title', ['number' => $procedure->request_number]))

@php
    use App\Enums\ProcedureStatus;
    $status = $procedure->status;
    $can = fn($p) => auth()->user()?->can($p) ?? false;

    // The procedure workflow is split into two segments:
    //   Request   — acceptance → billing → scheduling/rescheduling
    //   Operation — pre-op vitals & checklist → … → complete procedure
    // SCHEDULED is the hinge: reschedule lives in Request, pre-op opens Operation.
    $operationStatuses = [
        ProcedureStatus::SCHEDULED, ProcedureStatus::PRE_OP, ProcedureStatus::ANAESTHESIA,
        ProcedureStatus::IN_SURGERY, ProcedureStatus::SURGERY_DONE, ProcedureStatus::POST_OP,
        ProcedureStatus::COMPLETED,
    ];
    $showOperationFirst = in_array($status, $operationStatuses, true);
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
            <h3 class="mb-1">{{ __('theatre.procedure_title', ['number' => $procedure->request_number]) }}</h3>
            <div class="text-muted">
                {{ $procedure->patient?->first_name }} {{ $procedure->patient?->last_name }}
                · {{ __('theatre.visit_label') }} {{ $procedure->visit?->visit_number }}
                · {{ $procedure->service?->name }}
            </div>
        </div>
        <div>
            <span class="badge fs-6" style="background-color: {{ $status->color() }}; color:#fff;">
                {{ $status->translatedLabel() }}
            </span>
            @if ($status === ProcedureStatus::COMPLETED)
                <a data-no-inertia class="btn btn-outline-secondary btn-sm ms-2" href="{{ route('admin.theatre.report', $procedure) }}" target="_blank">{{ __('theatre.view_full_report') }}</a>
            @endif
            <a class="btn btn-outline-primary btn-sm ms-1" href="{{ route('admin.consultations.show', $procedure->visit_id) }}">{{ __('theatre.back_to_visit') }}</a>
        </div>
    </div>

    <div class="row g-3">

        {{-- LEFT: Timeline + Summary --}}
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong>{{ __('theatre.procedure_summary') }}</strong></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('theatre.department') }}</dt><dd class="col-7">{{ $procedure->department?->name }}</dd>
                        <dt class="col-5">{{ __('theatre.service') }}</dt><dd class="col-7">{{ $procedure->service?->name }}</dd>
                        <dt class="col-5">{{ __('theatre.priority') }}</dt><dd class="col-7">{{ ucfirst($procedure->priority) }}</dd>
                        <dt class="col-5">{{ __('theatre.requested_by') }}</dt><dd class="col-7">{{ $procedure->requestingDoctor?->name }}</dd>
                        <dt class="col-5">{{ __('theatre.requested_at') }}</dt><dd class="col-7">{{ optional($procedure->requested_at)->format('d M Y H:i') }}</dd>
                        <dt class="col-5">{{ __('theatre.indication') }}</dt><dd class="col-7">{{ $procedure->indication }}</dd>
                        @if ($procedure->notes)
                            <dt class="col-5">{{ __('theatre.notes') }}</dt><dd class="col-7">{{ $procedure->notes }}</dd>
                        @endif
                        @if ($procedure->billing_item_id)
                            <dt class="col-5">{{ __('theatre.billing') }}</dt>
                            <dd class="col-7">
                                {{ __('theatre.invoice') }} {{ $procedure->billingItem?->invoice?->invoice_number ?? '—' }}
                                · {{ __('theatre.item_hash') }}{{ $procedure->billing_item_id }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><strong>{{ __('theatre.timeline') }}</strong></div>
                <div class="card-body">
                    @include('theatre.partials.timeline', ['timeline' => $timeline])
                </div>
            </div>
        </div>

        {{-- RIGHT: Action forms (status-driven), split into Request + Operation segments --}}
        <div class="col-lg-7">

            <ul class="nav nav-tabs mb-3" id="procedureSegments" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $showOperationFirst ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#segRequest" type="button" role="tab">
                        <i class="ti ti-clipboard-check me-1"></i>{{ __('theatre.tab_request') }}
                        <span class="d-block small text-muted">{{ __('theatre.tab_request_hint') }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $showOperationFirst ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#segOperation" type="button" role="tab">
                        <i class="ti ti-stethoscope me-1"></i>{{ __('theatre.tab_operation') }}
                        <span class="d-block small text-muted">{{ __('theatre.tab_operation_hint') }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

            {{-- ============================ REQUEST SEGMENT ============================ --}}
            <div class="tab-pane fade {{ $showOperationFirst ? '' : 'show active' }}" id="segRequest" role="tabpanel">

            {{-- ACCEPT / REJECT --}}
            @if ($status === ProcedureStatus::REQUESTED)
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.acceptance_decision') }}</strong></div>
                    <div class="card-body">
                        @if ($can('procedure.accept'))
                            <form method="POST" action="{{ route('admin.theatre.accept', $procedure) }}" class="mb-3">
                                @csrf
                                <label class="form-label">{{ __('theatre.acceptance_notes_optional') }}</label>
                                <textarea name="notes" class="form-control mb-2" rows="2"></textarea>
                                <button class="btn btn-success">{{ __('theatre.accept_procedure') }}</button>
                            </form>
                        @endif
                        @if ($can('procedure.reject'))
                            <form method="POST" action="{{ route('admin.theatre.reject', $procedure) }}">
                                @csrf
                                <label class="form-label">{{ __('theatre.rejection_reason') }} <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                                <button class="btn btn-outline-danger">{{ __('common.reject') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- BILL --}}
            @if ($status === ProcedureStatus::ACCEPTED && $can('procedure.bill'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.generate_billing') }}</strong></div>
                    <div class="card-body">
                        <p class="text-muted small">{!! __('theatre.billing_hint', ['service' => '<strong>'.e($procedure->service?->name).'</strong>']) !!}</p>
                        <form method="POST" action="{{ route('admin.theatre.bill', $procedure) }}">
                            @csrf
                            <button class="btn btn-primary">{{ __('theatre.add_to_visit_invoice') }}</button>
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

            @if (in_array($status, [ProcedureStatus::REQUESTED, ProcedureStatus::ACCEPTED, ProcedureStatus::BILLED, ProcedureStatus::RESCHEDULED, ProcedureStatus::SCHEDULED], true) === false)
                <p class="text-muted small mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('theatre.moved_past_request') }}</p>
            @endif

            </div>{{-- /segRequest --}}

            {{-- ============================ OPERATION SEGMENT ============================ --}}
            <div class="tab-pane fade {{ $showOperationFirst ? 'show active' : '' }}" id="segOperation" role="tabpanel">

            @if (! $showOperationFirst)
                <p class="text-muted small"><i class="ti ti-info-circle me-1"></i>{{ __('theatre.operation_opens_when_scheduled') }}</p>
            @endif

            {{-- PRE-OP --}}
            @if ($status === ProcedureStatus::SCHEDULED && $can('procedure.record_preop'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.preop_vitals_checklist') }}</strong></div>
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
                            <button class="btn btn-primary mt-3">{{ __('theatre.save_preop') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ANAESTHESIA --}}
            @if ($status === ProcedureStatus::PRE_OP && $can('procedure.record_anaesthesia'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.anaesthesia_note') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.anaesthesia', $procedure) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Anaesthetist</label>
                                    <select name="anaesthetist_id" class="form-select form-select-sm">
                                        <option value="">{{ __('theatre.select_placeholder') }}</option>
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
                            <button class="btn btn-primary mt-3">{{ __('theatre.save_anaesthesia_note') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- START SURGERY --}}
            @if ($status === ProcedureStatus::ANAESTHESIA && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.start_surgery') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.start-surgery', $procedure) }}">
                            @csrf
                            <button class="btn btn-danger">{{ __('theatre.mark_surgery_started') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- OPERATIVE NOTE --}}
            @if (in_array($status, [ProcedureStatus::ANAESTHESIA, ProcedureStatus::IN_SURGERY]) && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.operative_note') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.operative-note', $procedure) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Surgeon</label>
                                    <select name="surgeon_id" class="form-select form-select-sm">
                                        <option value="">{{ __('theatre.select_placeholder') }}</option>
                                        @foreach ($clinicians as $u)<option value="{{ $u->id }}" @selected($procedure->schedule?->surgeon_id==$u->id)>{{ $u->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Assistant</label>
                                    <select name="assistant_surgeon_id" class="form-select form-select-sm">
                                        <option value="">{{ __('theatre.select_placeholder') }}</option>
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
                            <button class="btn btn-primary mt-3">{{ __('theatre.save_operative_note') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- COMPLETE SURGERY (alt) --}}
            @if ($status === ProcedureStatus::IN_SURGERY && $procedure->operativeNote && $can('procedure.record_surgery'))
                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span>{{ __('theatre.operative_note_recorded') }}</span>
                        <form method="POST" action="{{ route('admin.theatre.complete-surgery', $procedure) }}">
                            @csrf
                            <button class="btn btn-success btn-sm">{{ __('theatre.surgery_done') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- POST-OP --}}
            @if ($status === ProcedureStatus::SURGERY_DONE && $can('procedure.record_postop'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.post_op_note') }}</strong></div>
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
                            <button class="btn btn-primary mt-3">{{ __('theatre.save_postop') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- COMPLETE --}}
            @if ($status === ProcedureStatus::POST_OP && $can('procedure.complete'))
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><strong>{{ __('theatre.complete_procedure') }}</strong></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('theatre.complete_procedure_hint') }}</p>
                        <form method="POST" action="{{ route('admin.theatre.complete', $procedure) }}">
                            @csrf
                            <button class="btn btn-success">{{ __('theatre.mark_procedure_completed') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            </div>{{-- /segOperation --}}
            </div>{{-- /tab-content --}}

            {{-- CANCEL (available while open) — stays outside the segments --}}
            @if (! in_array($status, [ProcedureStatus::COMPLETED, ProcedureStatus::CANCELLED, ProcedureStatus::REJECTED]) && $can('procedure.cancel'))
                <div class="card shadow-sm mb-3 border-danger">
                    <div class="card-header bg-light"><strong class="text-danger">{{ __('theatre.cancel_procedure') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.theatre.cancel', $procedure) }}">
                            @csrf
                            <label class="form-label small">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('theatre.cancel_procedure_confirm') }}')">{{ __('theatre.cancel_procedure') }}</button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
