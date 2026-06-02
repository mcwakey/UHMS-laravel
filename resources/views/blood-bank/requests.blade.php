@extends('layouts.app')
@section('title', 'Blood Requests')

@section('content')
<x-page-header title="Blood Requests" description="Recipient details, compatible-unit suggestions, crossmatch, issue, and transfusion outcomes." icon="ti-droplet">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.units.index') }}" class="btn btn-outline-secondary btn-sm">Unit Inventory</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Create Request &amp; Recipient Details</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.requests.store') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><label class="form-label">Visit</label><select name="visit_id" class="form-select" required><option value="">Select visit</option>@foreach($visits as $visit)<option value="{{ $visit->id }}">{{ $visit->visit_number }} — {{ $visit->patient->full_name ?? 'Patient' }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Recipient Group</label><select name="blood_group" class="form-select" required>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Component</label><select name="component_type" class="form-select"><option value="WHOLE_BLOOD">Whole Blood</option><option value="PRBC">Packed Red Cells</option><option value="PLASMA">Plasma / FFP</option><option value="PLATELETS">Platelets</option><option value="CRYOPRECIPITATE">Cryoprecipitate</option></select></div>
            <div class="col-md-2"><label class="form-label">Units</label><input type="number" min="1" name="units_requested" class="form-control" value="1" required></div>
            <div class="col-md-2"><label class="form-label">Urgency</label><select name="priority" class="form-select"><option>ROUTINE</option><option>URGENT</option><option>EMERGENCY</option><option value="MASSIVE_TRANSFUSION">MASSIVE TRANSFUSION</option></select></div>
            <div class="col-md-3"><label class="form-label">Clinical Indication</label><input name="clinical_indication" class="form-control" list="indications"><datalist id="indications">@foreach($clinicalIndications as $ind)<option value="{{ $ind }}">@endforeach</datalist></div>
            <div class="col-md-3"><label class="form-label">Diagnosis</label><input name="diagnosis" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Hb (g/dL)</label><input name="hb_level" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Pregnancy</label><select name="pregnancy_status" class="form-select"><option value="">N/A</option><option>NOT_PREGNANT</option><option>PREGNANT</option><option>POSTPARTUM</option></select></div>
            <div class="col-md-2"><label class="form-label">Needed At</label><input type="datetime-local" name="needed_at" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Special Requirements</label><input name="special_requirements" class="form-control" placeholder="e.g. irradiated, leucodepleted"></div>
            <div class="col-md-3 d-flex align-items-center pt-3"><div class="form-check"><input type="checkbox" class="form-check-input" name="previous_transfusion_reaction" value="1" id="ptr"><label class="form-check-label" for="ptr">Previous transfusion reaction</label></div></div>
            <div class="col-md-6"><label class="form-label">Previous Reaction / Transfusion History</label><input name="previous_transfusion_reaction_notes" class="form-control"></div>
            <div class="col-12"><button class="btn btn-primary">Create Request</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(['PENDING','APPROVED','PARTIALLY_ISSUED','ISSUED','COMPLETED','CANCELLED'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Filter</button></div>
            <div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="{{ route('admin.blood-bank.requests.index') }}">Clear</a></div>
        </form>
    </div>
    <div class="card-body">
        @forelse($requests as $request)
            @php($recipient = $request->recipient)
            @php($units = $suggestions[$request->id] ?? collect())
            <div class="border rounded p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div>
                        <span class="fw-semibold">{{ $request->request_number }}</span>
                        <x-status-badge :status="$request->status" domain="blood_request" />
                        <x-status-badge :status="$request->priority" domain="priority" />
                        <div class="small text-muted">{{ $request->patient->full_name ?? '—' }} · {{ $request->visit->visit_number ?? '' }} · {{ $request->requested_at?->format('d M Y H:i') }}</div>
                    </div>
                    <div class="text-end small">
                        <div>Recipient: <strong>{{ $recipient?->blood_group ?? $request->blood_group }}</strong> · {{ str_replace('_',' ',$request->component_type) }} · {{ $request->units_issued }}/{{ $request->units_requested }} issued</div>
                        <div class="text-muted">Indication: {{ $recipient?->clinical_indication ?: $request->indication ?: '—' }}@if($recipient?->previous_transfusion_reaction) · <span class="text-danger">Prior reaction</span>@endif</div>
                    </div>
                </div>

                @if($request->status === 'PENDING')
                    <form method="POST" action="{{ route('admin.blood-bank.requests.approve', $request) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Approve Request</button></form>
                @endif

                @if(in_array($request->status, ['APPROVED','PARTIALLY_ISSUED']))
                <div class="row g-3 mt-1">
                    {{-- Compatible unit suggestions --}}
                    <div class="col-lg-7">
                        <div class="small fw-semibold mb-1">Compatible Available Units
                            <span class="text-muted">(recipient {{ $recipient?->blood_group ?? $request->blood_group }})</span>
                        </div>
                        @if($units->isEmpty())
                            <div class="text-muted small">No compatible screened units in stock.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead class="bg-light"><tr><th>Unit</th><th>Group</th><th>Component</th><th>Expiry</th><th>Compatibility</th><th></th></tr></thead>
                                <tbody>
                                @foreach($units as $unit)
                                    <tr>
                                        <td>{{ $unit->unit_number }}@if($unit->is_exact_match) <span class="badge bg-success">exact</span>@endif</td>
                                        <td>{{ $unit->blood_group }}</td>
                                        <td>{{ str_replace('_',' ',$unit->component_type) }}</td>
                                        <td>{{ $unit->expiry_date?->format('d M Y') }}<div class="small text-muted">{{ $unit->daysToExpiry() }}d</div></td>
                                        <td><x-status-badge :status="$unit->compatibility_status" domain="crossmatch" /></td>
                                        <td class="text-nowrap">
                                            <form method="POST" action="{{ route('admin.blood-bank.requests.crossmatches.store', $request) }}" class="d-inline">@csrf<input type="hidden" name="blood_unit_id" value="{{ $unit->id }}"><button class="btn btn-sm btn-outline-primary">Crossmatch</button></form>
                                            <form method="POST" action="{{ route('admin.blood-bank.requests.issues.store', $request) }}" class="d-inline">@csrf<input type="hidden" name="blood_unit_id" value="{{ $unit->id }}"><button class="btn btn-sm btn-outline-danger">Issue</button></form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Emergency release --}}
                        <details class="small">
                            <summary class="text-danger">Emergency release (uncrossmatched / override)</summary>
                            <form method="POST" action="{{ route('admin.blood-bank.requests.issues.store', $request) }}" class="row g-1 mt-1">
                                @csrf
                                <input type="hidden" name="emergency" value="1">
                                <div class="col-md-3"><input name="blood_unit_id" class="form-control form-control-sm" placeholder="Unit ID" required></div>
                                <div class="col-md-4"><select name="emergency_release_type" class="form-select form-select-sm" required><option value="">Release type…</option>@foreach($emergencyReleaseTypes as $t)<option value="{{ $t }}">{{ str_replace('_',' ',$t) }}</option>@endforeach</select></div>
                                <div class="col-md-3"><input name="emergency_release_reason" class="form-control form-control-sm" placeholder="Reason (required)" required></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-danger w-100">Release</button></div>
                            </form>
                        </details>
                    </div>

                    {{-- Crossmatch + issue history --}}
                    <div class="col-lg-5">
                        <div class="small fw-semibold mb-1">Crossmatches</div>
                        @forelse($request->crossmatches as $xm)
                            <div class="small mb-1">
                                <x-status-badge :status="$xm->result" domain="crossmatch" :label="($xm->unit->unit_number ?? $xm->blood_unit_id).' '.\Illuminate\Support\Str::title(str_replace('_',' ',$xm->result))" />
                                <span class="text-muted">{{ str_replace('_',' ',$xm->compatibility_status) }}</span>
                                @if(!$xm->verified_at)
                                    <form method="POST" action="{{ route('admin.blood-bank.crossmatches.verify', $xm) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-link btn-sm p-0">verify</button></form>
                                @else <span class="text-success">✓ verified</span>@endif
                            </div>
                        @empty
                            <div class="text-muted small mb-2">No crossmatches yet.</div>
                        @endforelse

                        <div class="small fw-semibold mt-2 mb-1">Issued / Transfusion</div>
                        @foreach($request->issues as $issue)
                            <div class="border rounded p-2 mb-1">
                                <div class="small">Unit {{ $issue->unit->unit_number ?? '' }} · <x-status-badge :status="$issue->transfusion_status" domain="blood_issue" size="sm" />@if($issue->is_emergency_release) <span class="badge bg-dark">ER</span>@endif</div>
                                @if(!in_array($issue->transfusion_status, ['TRANSFUSED','REACTION_RECORDED']))
                                <form method="POST" action="{{ route('admin.blood-bank.issues.transfuse', $issue) }}" class="row g-1 mt-1">
                                    @csrf @method('PATCH')
                                    <div class="col-5"><select name="reaction_type" class="form-select form-select-sm"><option value="">No reaction</option>@foreach(\App\Models\BloodIssue::REACTION_TYPES as $rt)<option value="{{ $rt }}">{{ str_replace('_',' ',$rt) }}</option>@endforeach</select></div>
                                    <div class="col-4"><input name="reaction_notes" class="form-control form-control-sm" placeholder="Reaction notes"></div>
                                    <div class="col-3"><button class="btn btn-sm btn-outline-success w-100">Record</button></div>
                                </form>
                                @else
                                    <div class="small text-muted">{{ str_replace('_',' ',$issue->outcome ?? '') }}@if($issue->reaction_occurred) · <span class="text-danger">{{ str_replace('_',' ',$issue->reaction_type) }}</span>@endif</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @empty
            <x-empty-state icon="ti-droplet-off" title="No blood requests" message="No blood requests match your filters." />
        @endforelse
    </div>
    @if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
</div>
@endsection
