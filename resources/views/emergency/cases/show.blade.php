@extends('layouts.app')
@section('title', 'ER Case — ' . $case->emergency_number)

@section('content')
<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <h1 class="h3 mb-1">
                <i class="ti ti-ambulance text-danger"></i>
                ER Case <code>{{ $case->emergency_number }}</code>
            </h1>
            <div class="text-muted">
                Patient:
                <strong>{{ $case->patient->full_name }}</strong>
                ({{ $case->patient->patient_number }})
                · Arrived {{ optional($case->arrival_time)->format('Y-m-d H:i') }}
                · Visit #<a href="{{ route('admin.visits.show', $case->visit) }}">{{ $case->visit->visit_number }}</a>
            </div>
        </div>
        <div class="text-end">
            @if($case->triage_category)
                <span class="badge bg-{{ $case->triage_category->color() }} fs-6">Triage: {{ $case->triage_category->label() }}</span>
            @endif
            <span class="badge bg-light text-dark fs-6">Status: {{ $case->status->label() }}</span>
            @if($case->disposition)
                <span class="badge bg-{{ $case->disposition->color() }} fs-6">Disposition: {{ $case->disposition->label() }}</span>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <ul class="nav nav-tabs" id="erTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-triage">Triage & Assignment</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-orders">Services / Procedures</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-consumables">Consumables</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-billing">Billing</button></li>
        <li class="nav-item"><button class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-disposition">Disposition</button></li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white">
        {{-- OVERVIEW --}}
        <div class="tab-pane fade show active" id="tab-overview">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6>Arrival</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Arrival Mode</dt><dd class="col-sm-7">{{ $case->arrival_mode->label() }}</dd>
                        <dt class="col-sm-5">Brought By</dt><dd class="col-sm-7">{{ $case->brought_by ?? '—' }}</dd>
                        <dt class="col-sm-5">Accompanied By</dt><dd class="col-sm-7">{{ $case->accompanied_by ?? '—' }}</dd>
                        <dt class="col-sm-5">Referral Source</dt><dd class="col-sm-7">{{ $case->referral_source ?? '—' }}</dd>
                        <dt class="col-sm-5">Chief Complaint</dt><dd class="col-sm-7">{{ $case->chief_complaint ?? '—' }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h6>Assignment</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Treatment Area</dt><dd class="col-sm-7">{{ optional($case->treatmentArea)->name ?? '—' }}</dd>
                        <dt class="col-sm-5">Doctor</dt><dd class="col-sm-7">{{ optional($case->assignedDoctor)->name ?? '—' }}</dd>
                        <dt class="col-sm-5">Nurse</dt><dd class="col-sm-7">{{ optional($case->assignedNurse)->name ?? '—' }}</dd>
                        <dt class="col-sm-5">Registered By</dt><dd class="col-sm-7">{{ optional($case->registeredBy)->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- TRIAGE --}}
        <div class="tab-pane fade" id="tab-triage">
            @can('emergency.triage.create')
            <form method="POST" action="{{ route('admin.emergency.cases.triage', $case) }}" class="mb-4">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Triage Category</label>
                        <select name="triage_category" class="form-select" required>
                            <option value="">— Select —</option>
                            @foreach($triages as $t)
                                <option value="{{ $t->value }}" @selected($case->triage_category?->value === $t->value)>{{ $t->label() }} (target {{ $t->targetMinutes() }}m)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-warning"><i class="ti ti-stethoscope"></i> Save Triage</button></div>
                </div>
            </form>
            @endcan

            @can('emergency.case.update')
            <form method="POST" action="{{ route('admin.emergency.cases.assign', $case) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Assigned Doctor</label>
                        <select name="assigned_doctor_id" class="form-select">
                            <option value="">— none —</option>
                            @foreach($doctors as $u)
                                <option value="{{ $u->id }}" @selected($case->assigned_doctor_id===$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned Nurse</label>
                        <select name="assigned_nurse_id" class="form-select">
                            <option value="">— none —</option>
                            @foreach($nurses as $u)
                                <option value="{{ $u->id }}" @selected($case->assigned_nurse_id===$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary">Save Assignment</button></div>
                </div>
            </form>
            @endcan
        </div>

        {{-- SERVICES --}}
        <div class="tab-pane fade" id="tab-orders">
            @can('emergency.orders.create')
            <form method="POST" action="{{ route('admin.emergency.cases.bill-service', $case) }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Service / Procedure</label>
                        <select name="service_id" class="form-select" required>
                            <option value="">— select a service —</option>
                            @foreach($services as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} — KSh {{ number_format($s->price ?? 0, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" name="quantity" value="1" min="1" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Description (optional)</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <div class="col-md-1"><button class="btn btn-primary">Bill</button></div>
                </div>
            </form>
            <small class="text-muted">All services are billed to the single visit invoice via <code>BillingService</code>.</small>
            @endcan
        </div>

        {{-- CONSUMABLES --}}
        <div class="tab-pane fade" id="tab-consumables">
            @can('emergency.consumables.consume')
            <form method="POST" action="{{ route('admin.emergency.cases.consume-product', $case) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">— select a product —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit ?? 'unit' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                    <div class="col-md-1"><button class="btn btn-warning">Consume</button></div>
                </div>
            </form>
            <small class="text-muted">
                Stock is deducted from the <strong>Emergency Store</strong> via <code>ConsumableUsageService</code>;
                the product is also billed to the visit invoice (if billable).
            </small>
            @endcan
        </div>

        {{-- BILLING --}}
        <div class="tab-pane fade" id="tab-billing">
            @php $invoice = $case->visit->invoices->first(); @endphp
            @if($invoice)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Invoice <code>{{ $invoice->invoice_number }}</code></h6>
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">Open Invoice</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Item</th><th>Source</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description ?? $item->name }}</td>
                                <td><code>{{ $item->source_type }}#{{ $item->source_id }}</code></td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">KSh {{ number_format($item->line_total ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No invoice yet — it will be created automatically when the first billable item is added.</p>
            @endif
        </div>

        {{-- DISPOSITION --}}
        <div class="tab-pane fade" id="tab-disposition">
            @can('emergency.disposition.set')
            @if($case->disposition)
                <div class="alert alert-info">
                    <strong>{{ $case->disposition->label() }}</strong>
                    by {{ optional($case->dispositionBy)->name }} at {{ optional($case->disposition_at)->format('Y-m-d H:i') }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.emergency.cases.disposition', $case) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Disposition <span class="text-danger">*</span></label>
                        <select name="disposition" id="dispSelect" class="form-select" required>
                            <option value="">— Select —</option>
                            @foreach($dispositions as $d)
                                <option value="{{ $d->value }}">{{ $d->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control">
                    </div>

                    {{-- Admit fields --}}
                    <div class="col-md-4 disp-admit d-none">
                        <label class="form-label">Bed</label>
                        <select name="bed_id" class="form-select">
                            <option value="">— select bed —</option>
                            @foreach($beds as $b)
                                <option value="{{ $b->id }}">{{ optional($b->ward)->name }} — {{ $b->bed_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 disp-admit d-none">
                        <label class="form-label">Admission Type</label>
                        <input type="text" name="admission_type" class="form-control" value="emergency_admission">
                    </div>
                    <div class="col-md-4 disp-admit d-none">
                        <label class="form-label">Admitting Diagnosis</label>
                        <input type="text" name="admitting_diagnosis" class="form-control">
                    </div>

                    {{-- Discharge summary --}}
                    <div class="col-md-12 disp-discharge d-none">
                        <label class="form-label">Discharge Summary</label>
                        <textarea name="discharge_summary" rows="3" class="form-control"></textarea>
                    </div>

                    {{-- Referral --}}
                    <div class="col-md-6 disp-refer d-none">
                        <label class="form-label">Referral Facility</label>
                        <input type="text" name="referral_facility" class="form-control">
                    </div>
                    <div class="col-md-6 disp-refer d-none">
                        <label class="form-label">Referral Reason</label>
                        <input type="text" name="referral_reason" class="form-control">
                    </div>

                    {{-- Death --}}
                    <div class="col-md-4 disp-death d-none">
                        <label class="form-label">Death Time</label>
                        <input type="datetime-local" name="death_time" class="form-control">
                    </div>
                    <div class="col-md-8 disp-death d-none">
                        <label class="form-label">Cause of Death</label>
                        <input type="text" name="death_cause" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-danger"><i class="ti ti-flag-check"></i> Save Disposition</button>
                </div>
            </form>
            @endcan
        </div>
    </div>
</div>

<script>
    (function(){
        const sel = document.getElementById('dispSelect');
        if (!sel) return;
        const map = {
            'admitted_to_ward':            ['.disp-admit'],
            'discharged_home':             ['.disp-discharge'],
            'left_against_medical_advice': ['.disp-discharge'],
            'absconded':                   ['.disp-discharge'],
            'referred_out':                ['.disp-refer', '.disp-discharge'],
            'deceased':                    ['.disp-death'],
        };
        function refresh() {
            document.querySelectorAll('.disp-admit, .disp-discharge, .disp-refer, .disp-death').forEach(n => n.classList.add('d-none'));
            (map[sel.value] || []).forEach(s => document.querySelectorAll(s).forEach(n => n.classList.remove('d-none')));
        }
        sel.addEventListener('change', refresh);
        refresh();
    })();
</script>
@endsection
