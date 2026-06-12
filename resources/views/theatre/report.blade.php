@extends('layouts.app')

@section('title', __('theatre.procedure_report') . ' ' . $procedure->request_number)

@section('content')
<div class="container py-3" id="procedure-report-area">

    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h3 class="mb-0">{{ __('theatre.procedure_report') }}</h3>
        <div>
            <button class="btn btn-outline-primary btn-sm" onclick="window.print()">{{ __('lab.print_button') }}</button>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.theatre.show', $procedure) }}">{{ __('theatre.back') }}</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>{{ config('app.name') }}</h5>
                    <div class="small text-muted">{{ __('theatre.procedure_report') }}</div>
                </div>
                <div class="col-md-6 text-end">
                    <div><strong>{{ __('lab.request_hash') }}:</strong> {{ $procedure->request_number }}</div>
                    <div><strong>{{ __('common.status') }}:</strong> <span class="badge" style="background-color: {{ $procedure->status->color() }};">{{ $procedure->status->translatedLabel() }}</span></div>
                    <div><strong>{{ __('theatre.printed') }}:</strong> {{ now()->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('theatre.patient_visit') }}</strong></div>
        <div class="card-body">
            <div class="row small">
                <div class="col-md-6">
                    <div><strong>{{ __('theatre.name') }}:</strong> {{ $procedure->patient?->first_name }} {{ $procedure->patient?->last_name }}</div>
                    <div><strong>{{ __('theatre.patient_number') }}:</strong> {{ $procedure->patient?->patient_number }}</div>
                    <div><strong>{{ __('theatre.sex_dob') }}:</strong> {{ $procedure->patient?->gender }} / {{ optional($procedure->patient?->date_of_birth)->format('d M Y') }}</div>
                </div>
                <div class="col-md-6">
                    <div><strong>{{ __('theatre.visit_number') }}:</strong> {{ $procedure->visit?->visit_number }}</div>
                    <div><strong>{{ __('theatre.department') }}:</strong> {{ $procedure->department?->name }}</div>
                    <div><strong>{{ __('theatre.service') }}:</strong> {{ $procedure->service?->name }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('theatre.request') }}</strong></div>
        <div class="card-body small">
            <div><strong>{{ __('theatre.requested_by') }}:</strong> {{ $procedure->requestingDoctor?->name }} {{ __('lab.on_label') }} {{ optional($procedure->requested_at)->format('d M Y H:i') }}</div>
            <div><strong>{{ __('theatre.priority') }}:</strong> {{ __('statuses.priority.' . $procedure->priority) }}</div>
            <div><strong>{{ __('theatre.indication') }}:</strong> {{ $procedure->indication }}</div>
            @if ($procedure->notes)<div><strong>{{ __('theatre.notes') }}:</strong> {{ $procedure->notes }}</div>@endif
        </div>
    </div>

    @if ($schedule = $procedure->schedule)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.schedule') }}</strong></div>
            <div class="card-body small">
                <div><strong>{{ __('theatre.theatre') }}:</strong> {{ $schedule->theatreRoom?->name ?? '—' }}</div>
                <div><strong>{{ __('theatre.start') }}:</strong> {{ optional($schedule->scheduled_start)->format('d M Y H:i') }}
                     · <strong>{{ __('theatre.end') }}:</strong> {{ optional($schedule->scheduled_end)->format('d M Y H:i') }}</div>
                <div><strong>{{ __('theatre.surgeon') }}:</strong> {{ $schedule->surgeon?->name }} · <strong>{{ __('theatre.anaesthetist') }}:</strong> {{ $schedule->anaesthetist?->name }}</div>
                @if ($schedule->assistantSurgeon)<div><strong>{{ __('theatre.assistant') }}:</strong> {{ $schedule->assistantSurgeon->name }}</div>@endif
                @if ($schedule->required_equipment)<div><strong>{{ __('theatre.equipment') }}:</strong> {{ $schedule->required_equipment }}</div>@endif
            </div>
        </div>
    @endif

    @if ($cl = $procedure->checklist)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.pre_op_checklist') }}</strong></div>
            <div class="card-body small">
                @foreach ([
                    'consent_signed'=>__('theatre.consent'),'fasting_confirmed'=>__('theatre.fasting'),'allergies_checked'=>__('theatre.allergies'),
                    'blood_available'=>__('theatre.blood'),'site_marked'=>__('theatre.site_marked'),'equipment_ready'=>__('theatre.equipment'),
                    'anaesthesia_review_done'=>__('theatre.anaesthesia_review')
                ] as $k=>$l)
                    <span class="me-3">{{ $l }}: <strong>{{ $cl->$k ? __('theatre.yes') : __('theatre.no') }}</strong></span>
                @endforeach
                @if ($cl->pre_op_diagnosis)<div class="mt-2"><strong>{{ __('theatre.pre_op_diagnosis') }}:</strong> {{ $cl->pre_op_diagnosis }}</div>@endif
                @if ($cl->notes)<div><strong>{{ __('theatre.notes') }}:</strong> {{ $cl->notes }}</div>@endif
            </div>
        </div>
    @endif

    @if ($procedure->vitals->count())
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.vitals') }}</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive"><table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('theatre.stage') }}</th><th>{{ __('theatre.temp') }}</th><th>{{ __('theatre.bp') }}</th><th>{{ __('theatre.pulse') }}</th><th>{{ __('theatre.rr') }}</th><th>{{ __('theatre.spo2') }}</th><th>{{ __('theatre.pain') }}</th><th>{{ __('theatre.by') }}</th><th>{{ __('theatre.at') }}</th></tr></thead>
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
            <div class="card-header"><strong>{{ __('theatre.anaesthesia_note') }}</strong></div>
            <div class="card-body small">
                <div><strong>{{ __('theatre.type') }}:</strong> {{ strtoupper($an->anaesthesia_type) }} · <strong>{{ __('theatre.by') }}:</strong> {{ $an->anaesthetist?->name }}</div>
                <div><strong>{{ __('theatre.time') }}:</strong> {{ optional($an->start_time)->format('d M H:i') }} – {{ optional($an->end_time)->format('d M H:i') }}</div>
                @foreach (['pre_assessment'=>__('theatre.pre_assessment'),'drugs_used'=>__('theatre.drugs_used'),'dosage_notes'=>__('theatre.dosage'),'airway_management'=>__('theatre.airway'),'monitoring_notes'=>__('theatre.monitoring'),'complications'=>__('theatre.complications'),'notes'=>__('theatre.notes')] as $k=>$l)
                    @if ($an->$k)<div><strong>{{ $l }}:</strong> {{ $an->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($op = $procedure->operativeNote)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.operative_note') }}</strong></div>
            <div class="card-body small">
                <div><strong>{{ __('theatre.surgeon') }}:</strong> {{ $op->surgeon?->name }} @if ($op->assistantSurgeon) · <strong>{{ __('theatre.assistant') }}:</strong> {{ $op->assistantSurgeon->name }} @endif</div>
                <div><strong>{{ __('theatre.time') }}:</strong> {{ optional($op->start_time)->format('d M H:i') }} – {{ optional($op->end_time)->format('d M H:i') }}</div>
                <div><strong>{{ __('theatre.procedure_performed') }}:</strong> {{ $op->procedure_performed }}</div>
                @foreach (['pre_op_diagnosis'=>__('theatre.pre_op_diagnosis'),'post_op_diagnosis'=>__('theatre.post_op_diagnosis'),'findings'=>__('theatre.findings'),'incision'=>__('theatre.incision'),'technique'=>__('theatre.technique'),'blood_loss'=>__('theatre.blood_loss'),'complications'=>__('theatre.complications'),'specimens'=>__('theatre.specimens'),'implants'=>__('theatre.implants'),'outcome'=>__('theatre.outcome'),'notes'=>__('theatre.notes')] as $k=>$l)
                    @if ($op->$k)<div><strong>{{ $l }}:</strong> {{ $op->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($po = $procedure->postOpNote)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.post_op_note') }}</strong></div>
            <div class="card-body small">
                <div><strong>{{ __('theatre.by') }}:</strong> {{ $po->recordedBy?->name }}</div>
                @foreach (['recovery_status'=>__('theatre.recovery_status'),'pain_score'=>__('theatre.pain_score'),'consciousness_level'=>__('theatre.consciousness'),'post_op_instructions'=>__('theatre.instructions'),'medications'=>__('theatre.medications'),'complications'=>__('theatre.complications'),'transfer_destination'=>__('theatre.transfer'),'notes'=>__('theatre.notes')] as $k=>$l)
                    @if ($po->$k !== null && $po->$k !== '')<div><strong>{{ $l }}:</strong> {{ $po->$k }}</div>@endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($procedure->billing_item_id)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('theatre.billing') }}</strong></div>
            <div class="card-body small">
                <div><strong>{{ __('theatre.invoice') }}:</strong> {{ $procedure->billingItem?->invoice?->invoice_number }}</div>
                <div><strong>{{ __('theatre.service_price') }}:</strong> {{ number_format((float) ($procedure->billingItem?->selected_price ?? 0), 2) }}</div>
                <div><strong>{{ __('common.status') }}:</strong> {{ $procedure->billingItem?->payment_status }}</div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('theatre.timeline') }}</strong></div>
        <div class="card-body">
            @include('theatre.partials.timeline', ['timeline' => $timeline])
        </div>
    </div>

</div>
@endsection
