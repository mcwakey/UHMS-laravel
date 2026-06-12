@extends('layouts.app')

@section('title', 'Procedure Report ' . $procedure->request_number)

@section('content')
<div class="container py-3" id="procedure-report-area">

    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h3 class="mb-0">Procedure Report</h3>
        <div>
            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">Print</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.show', $procedure) }}">Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>{{ config('app.name') }}</h5>
                    <div class="small text-muted">Procedure Report</div>
                </div>
                <div class="col-md-6 text-end">
                    <div><strong>Request #:</strong> {{ $procedure->request_number }}</div>
                    <div><strong>Status:</strong> <span class="badge" style="background-color: {{ $procedure->status->color() }};">{{ $procedure->status->translatedLabel() }}</span></div>
                    <div><strong>Printed:</strong> {{ now()->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Patient &amp; Visit</strong></div>
        <div class="card-body">
            <div class="row small">
                <div class="col-md-6">
                    <div><strong>Name:</strong> {{ $procedure->patient?->first_name }} {{ $procedure->patient?->last_name }}</div>
                    <div><strong>Patient #:</strong> {{ $procedure->patient?->patient_number }}</div>
                    <div><strong>Sex / DOB:</strong> {{ $procedure->patient?->gender }} / {{ optional($procedure->patient?->date_of_birth)->format('d M Y') }}</div>
                </div>
                <div class="col-md-6">
                    <div><strong>Visit #:</strong> {{ $procedure->visit?->visit_number }}</div>
                    <div><strong>Department:</strong> {{ $procedure->department?->name }}</div>
                    <div><strong>Service:</strong> {{ $procedure->service?->name }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Request</strong></div>
        <div class="card-body small">
            <div><strong>Requested by:</strong> {{ $procedure->requestingDoctor?->name }} on {{ optional($procedure->requested_at)->format('d M Y H:i') }}</div>
            <div><strong>Priority:</strong> {{ ucfirst($procedure->priority) }}</div>
            <div><strong>Indication:</strong> {{ $procedure->indication }}</div>
            @if ($procedure->notes)<div><strong>Notes:</strong> {{ $procedure->notes }}</div>@endif
        </div>
    </div>

    @if ($schedule = $procedure->schedule)
        <div class="card mb-3">
            <div class="card-header"><strong>Schedule</strong></div>
            <div class="card-body small">
                <div><strong>Theatre:</strong> {{ $schedule->theatreRoom?->name ?? '—' }}</div>
                <div><strong>Start:</strong> {{ optional($schedule->scheduled_start)->format('d M Y H:i') }}
                     · <strong>End:</strong> {{ optional($schedule->scheduled_end)->format('d M Y H:i') }}</div>
                <div><strong>Surgeon:</strong> {{ $schedule->surgeon?->name }} · <strong>Anaesthetist:</strong> {{ $schedule->anaesthetist?->name }}</div>
                @if ($schedule->assistantSurgeon)<div><strong>Assistant:</strong> {{ $schedule->assistantSurgeon->name }}</div>@endif
                @if ($schedule->required_equipment)<div><strong>Equipment:</strong> {{ $schedule->required_equipment }}</div>@endif
            </div>
        </div>
    @endif

    @if ($cl = $procedure->checklist)
        <div class="card mb-3">
            <div class="card-header"><strong>Pre-op Checklist</strong></div>
            <div class="card-body small">
                @foreach ([
                    'consent_signed'=>'Consent','fasting_confirmed'=>'Fasting','allergies_checked'=>'Allergies',
                    'blood_available'=>'Blood','site_marked'=>'Site marked','equipment_ready'=>'Equipment',
                    'anaesthesia_review_done'=>'Anaesthesia review'
                ] as $k=>$l)
                    <span class="me-3">{{ $l }}: <strong>{{ $cl->$k ? 'Yes' : 'No' }}</strong></span>
                @endforeach
                @if ($cl->pre_op_diagnosis)<div class="mt-2"><strong>Pre-op diagnosis:</strong> {{ $cl->pre_op_diagnosis }}</div>@endif
                @if ($cl->notes)<div><strong>Notes:</strong> {{ $cl->notes }}</div>@endif
            </div>
        </div>
    @endif

    @if ($procedure->vitals->count())
        <div class="card mb-3">
            <div class="card-header"><strong>Vitals</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>Stage</th><th>Temp</th><th>BP</th><th>Pulse</th><th>RR</th><th>SpO₂</th><th>Pain</th><th>By</th><th>At</th></tr></thead>
                    <tbody>
                        @foreach ($procedure->vitals as $v)
                            <tr>
                                <td>{{ \Illuminate\Support\Str::headline($v->stage) }}</td>
                                <td>{{ $v->temperature }}</td>
                                <td>{{ $v->blood_pressure }}</td>
                                <td>{{ $v->pulse }}</td>
                                <td>{{ $v->respiratory_rate }}</td>
                                <td>{{ $v->oxygen_saturation }}</td>
                                <td>{{ $v->pain_score }}</td>
                                <td>{{ $v->recordedBy?->name }}</td>
                                <td>{{ optional($v->recorded_at)->format('d M H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
        </div>
    @endif

    @if ($an = $procedure->anaesthesiaNote)
        <div class="card mb-3">
            <div class="card-header"><strong>Anaesthesia Note</strong></div>
            <div class="card-body small">
                <div><strong>Type:</strong> {{ strtoupper($an->anaesthesia_type) }} · <strong>By:</strong> {{ $an->anaesthetist?->name }}</div>
                <div><strong>Time:</strong> {{ optional($an->start_time)->format('d M H:i') }} – {{ optional($an->end_time)->format('d M H:i') }}</div>
                @foreach (['pre_assessment'=>'Pre-assessment','drugs_used'=>'Drugs used','dosage_notes'=>'Dosage','airway_management'=>'Airway','monitoring_notes'=>'Monitoring','complications'=>'Complications','notes'=>'Notes'] as $k=>$l)
                    @if ($an->$k)<div><strong>{{ $l }}:</strong> {{ $an->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($op = $procedure->operativeNote)
        <div class="card mb-3">
            <div class="card-header"><strong>Operative Note</strong></div>
            <div class="card-body small">
                <div><strong>Surgeon:</strong> {{ $op->surgeon?->name }} @if ($op->assistantSurgeon) · <strong>Assistant:</strong> {{ $op->assistantSurgeon->name }} @endif</div>
                <div><strong>Time:</strong> {{ optional($op->start_time)->format('d M H:i') }} – {{ optional($op->end_time)->format('d M H:i') }}</div>
                <div><strong>Procedure performed:</strong> {{ $op->procedure_performed }}</div>
                @foreach (['pre_op_diagnosis'=>'Pre-op diagnosis','post_op_diagnosis'=>'Post-op diagnosis','findings'=>'Findings','incision'=>'Incision','technique'=>'Technique','blood_loss'=>'Blood loss','complications'=>'Complications','specimens'=>'Specimens','implants'=>'Implants','outcome'=>'Outcome','notes'=>'Notes'] as $k=>$l)
                    @if ($op->$k)<div><strong>{{ $l }}:</strong> {{ $op->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($po = $procedure->postOpNote)
        <div class="card mb-3">
            <div class="card-header"><strong>Post-op Note</strong></div>
            <div class="card-body small">
                <div><strong>By:</strong> {{ $po->recordedBy?->name }}</div>
                @foreach (['recovery_status'=>'Recovery status','pain_score'=>'Pain score','consciousness_level'=>'Consciousness','post_op_instructions'=>'Instructions','medications'=>'Medications','complications'=>'Complications','transfer_destination'=>'Transfer','notes'=>'Notes'] as $k=>$l)
                    @if ($po->$k !== null && $po->$k !== '')<div><strong>{{ $l }}:</strong> {{ $po->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($procedure->billing_item_id)
        <div class="card mb-3">
            <div class="card-header"><strong>Billing</strong></div>
            <div class="card-body small">
                <div><strong>Invoice:</strong> {{ $procedure->billingItem?->invoice?->invoice_number }}</div>
                <div><strong>Service price:</strong> {{ number_format((float) ($procedure->billingItem?->selected_price ?? 0), 2) }}</div>
                <div><strong>Status:</strong> {{ $procedure->billingItem?->payment_status }}</div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><strong>Timeline</strong></div>
        <div class="card-body">
            @include('theatre.partials.timeline', ['timeline' => $timeline])
        </div>
    </div>

</div>
@endsection
