@extends('layouts.app')
@section('title', 'Blood Requests')

@php($groups = ['O-','O+','A-','A+','B-','B+','AB-','AB+'])
@php($components = ['WHOLE_BLOOD'=>'Whole Blood','PRBC'=>'Packed Red Cells','PLASMA'=>'Plasma / FFP','PLATELETS'=>'Platelets','CRYOPRECIPITATE'=>'Cryoprecipitate'])

@section('content')
<x-page-header title="Blood Requests" description="Recipient details, compatible units, crossmatch, issue, and transfusion outcomes." icon="ti-droplet">
    <x-slot:actions>
        @can('blood_bank.requests.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal"><i class="ti ti-plus me-1"></i>New Request</button>
        @endcan
        <a href="{{ route('admin.blood-bank.units.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-packages me-1"></i>Unit Inventory</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(['PENDING','APPROVED','PARTIALLY_ISSUED','ISSUED','COMPLETED','CANCELLED'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach($groups as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill"><i class="ti ti-filter me-1"></i>Filter</button>
                <a class="btn btn-outline-secondary flex-fill" href="{{ route('admin.blood-bank.requests.index') }}">Clear</a>
            </div>
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
                        @if($request->isExternal())<span class="badge bg-purple-lt"><i class="ti ti-building-hospital me-1"></i>External</span>@endif
                        <div class="small text-muted">
                            <i class="ti ti-user me-1"></i>{{ $request->recipientName() }}
                            @if($request->isExternal() && $recipient?->external_facility) · {{ $recipient->external_facility }}@endif
                            @if($request->visit) · {{ $request->visit->visit_number }}@endif
                            · {{ $request->requested_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="text-end small">
                        <div>Recipient: <strong>{{ $recipient?->blood_group ?? $request->blood_group }}</strong> · {{ str_replace('_',' ',$request->component_type) }} · {{ $request->units_issued }}/{{ $request->units_requested }} issued</div>
                        <div class="text-muted">Indication: {{ $recipient?->clinical_indication ?: $request->indication ?: '—' }}@if($recipient?->previous_transfusion_reaction) · <span class="text-danger">Prior reaction</span>@endif</div>
                    </div>
                </div>

                @if($request->status === 'PENDING')
                    <form method="POST" action="{{ route('admin.blood-bank.requests.approve', $request) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success"><i class="ti ti-check me-1"></i>Approve Request</button></form>
                @endif

                @if(in_array($request->status, ['APPROVED','PARTIALLY_ISSUED']))
                <div class="row g-3 mt-1">
                    {{-- Compatible unit suggestions --}}
                    <div class="col-lg-8">
                        <div class="small fw-semibold mb-1">Compatible Available Units
                            <span class="text-muted">(recipient {{ $recipient?->blood_group ?? $request->blood_group }} · {{ str_replace('_',' ',$request->component_type) }})</span>
                        </div>
                        @if($units->isEmpty())
                            <div class="text-muted small">No compatible screened units in stock.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead class="bg-light"><tr><th>Unit</th><th>Group</th><th>Expiry</th><th>Compatibility</th><th></th></tr></thead>
                                <tbody>
                                @foreach($units as $unit)
                                    <tr>
                                        <td>{{ $unit->unit_number }}@if($unit->is_exact_match) <span class="badge bg-success">exact</span>@endif</td>
                                        <td>{{ $unit->blood_group }}</td>
                                        <td>{{ $unit->expiry_date?->format('d M Y') }}<div class="small text-muted">{{ $unit->daysToExpiry() }}d</div></td>
                                        <td><x-status-badge :status="$unit->compatibility_status" domain="crossmatch" /></td>
                                        {{-- <td class="small text-muted" style="max-width:230px">{{ $unit->compatibility_reason }}</td> --}}
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
                    <div class="col-lg-4">
                        <div class="small fw-semibold mb-1">Crossmatches</div>
                        @forelse($request->crossmatches as $xm)
                            <div class="small mb-2 border-start border-3 ps-2 {{ $xm->compatibility_status === 'INCOMPATIBLE' ? 'border-danger' : ($xm->compatibility_status === 'COMPATIBLE_WITH_CAUTION' ? 'border-warning' : 'border-success') }}">
                                <div>
                                    <x-status-badge :status="$xm->result" domain="crossmatch" :label="($xm->unit->unit_number ?? $xm->blood_unit_id).' '.\Illuminate\Support\Str::title(str_replace('_',' ',$xm->result))" />
                                    @if(!$xm->verified_at)
                                        <form method="POST" action="{{ route('admin.blood-bank.crossmatches.verify', $xm) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-link btn-sm p-0">verify</button></form>
                                    @else <span class="text-success">✓ verified</span>@endif
                                </div>
                                @if($xm->compatibility_reason)<div class="text-muted">{{ $xm->compatibility_reason }}</div>@endif
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

@can('blood_bank.requests.create')
{{-- Create Request modal --}}
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.blood-bank.requests.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-droplet-plus me-2 text-primary"></i>Create Blood Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Recipient type --}}
                    <div class="mb-3">
                        <label class="form-label">Recipient</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="recipient_type" id="rtPatient" value="PATIENT" @checked(old('recipient_type','PATIENT')==='PATIENT')>
                            <label class="btn btn-outline-primary" for="rtPatient"><i class="ti ti-user me-1"></i>Facility Patient</label>
                            <input type="radio" class="btn-check" name="recipient_type" id="rtExternal" value="EXTERNAL" @checked(old('recipient_type')==='EXTERNAL')>
                            <label class="btn btn-outline-primary" for="rtExternal"><i class="ti ti-building-hospital me-1"></i>External / Referral</label>
                        </div>
                    </div>

                    {{-- Facility patient block --}}
                    <div id="patientBlock" class="row g-3 mb-2">
                        <div class="col-12">
                            <label class="form-label">Visit <span class="text-danger">*</span></label>
                            <select name="visit_id" id="requestVisit" class="form-select" style="width:100%">
                                <option value="">Search visit or patient…</option>
                            </select>
                            <div class="form-text">Search by visit number or patient name/number.</div>
                        </div>
                    </div>

                    {{-- External recipient block --}}
                    <div id="externalBlock" class="row g-3 mb-2 d-none">
                        <div class="col-md-6"><label class="form-label">Recipient Name <span class="text-danger">*</span></label><input name="external_name" class="form-control" value="{{ old('external_name') }}"></div>
                        <div class="col-md-3"><label class="form-label">Sex</label><select name="external_sex" class="form-select"><option value="">—</option>@foreach(['Male','Female','Other'] as $sx)<option value="{{ $sx }}" @selected(old('external_sex')===$sx)>{{ $sx }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">Age</label><input name="external_age" type="number" min="0" max="150" class="form-control" value="{{ old('external_age') }}"></div>
                        <div class="col-md-6"><label class="form-label">Referring Facility / Ward</label><input name="external_facility" class="form-control" value="{{ old('external_facility') }}"></div>
                        <div class="col-md-3"><label class="form-label">Contact</label><input name="external_contact" class="form-control" value="{{ old('external_contact') }}"></div>
                        <div class="col-md-3"><label class="form-label">External Ref</label><input name="external_reference" class="form-control" value="{{ old('external_reference') }}"></div>
                    </div>

                    <hr>
                    {{-- Order details --}}
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Blood Group <span class="text-danger">*</span></label><select name="blood_group" id="requestGroup" class="form-select" required>@foreach($groups as $g)<option value="{{ $g }}" @selected(old('blood_group')===$g)>{{ $g }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">Component</label><select name="component_type" class="form-select">@foreach($components as $val=>$lbl)<option value="{{ $val }}" @selected(old('component_type')===$val)>{{ $lbl }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Units <span class="text-danger">*</span></label><input type="number" min="1" name="units_requested" class="form-control" value="{{ old('units_requested',1) }}" required></div>
                        <div class="col-md-4"><label class="form-label">Urgency</label><select name="priority" class="form-select"><option value="ROUTINE">Routine</option><option value="URGENT">Urgent</option><option value="EMERGENCY">Emergency</option><option value="MASSIVE_TRANSFUSION">Massive transfusion</option></select></div>
                        <div class="col-md-6"><label class="form-label">Clinical Indication</label><input name="clinical_indication" class="form-control" list="indications" value="{{ old('clinical_indication') }}"><datalist id="indications">@foreach($clinicalIndications as $ind)<option value="{{ $ind }}">@endforeach</datalist></div>
                        <div class="col-md-6"><label class="form-label">Diagnosis</label><input name="diagnosis" class="form-control" value="{{ old('diagnosis') }}"></div>
                        <div class="col-md-3"><label class="form-label">Hb (g/dL)</label><input name="hb_level" class="form-control" value="{{ old('hb_level') }}"></div>
                        <div class="col-md-3"><label class="form-label">Pregnancy</label><select name="pregnancy_status" class="form-select"><option value="">N/A</option>@foreach(['NOT_PREGNANT'=>'Not pregnant','PREGNANT'=>'Pregnant','POSTPARTUM'=>'Postpartum'] as $val=>$lbl)<option value="{{ $val }}" @selected(old('pregnancy_status')===$val)>{{ $lbl }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Needed At</label><input type="datetime-local" name="needed_at" class="form-control" value="{{ old('needed_at') }}"></div>
                        <div class="col-md-6"><label class="form-label">Special Requirements</label><input name="special_requirements" class="form-control" value="{{ old('special_requirements') }}" placeholder="e.g. irradiated, leucodepleted"></div>
                        <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="previous_transfusion_reaction" value="1" id="ptr" @checked(old('previous_transfusion_reaction'))><label class="form-check-label" for="ptr">Previous transfusion reaction</label></div></div>
                        <div class="col-12"><label class="form-label">Previous Reaction / Transfusion History</label><input name="previous_transfusion_reaction_notes" class="form-control" value="{{ old('previous_transfusion_reaction_notes') }}"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Create Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('createRequestModal');
    var patientBlock = document.getElementById('patientBlock');
    var externalBlock = document.getElementById('externalBlock');
    var visit = document.getElementById('requestVisit');
    var group = document.getElementById('requestGroup');
    var nameInput = externalBlock.querySelector('[name="external_name"]');

    function applyType() {
        var external = document.getElementById('rtExternal').checked;
        externalBlock.classList.toggle('d-none', !external);
        patientBlock.classList.toggle('d-none', external);
        if (nameInput) nameInput.required = external;
        if (visit) visit.required = !external;
    }
    document.getElementById('rtPatient').addEventListener('change', applyType);
    document.getElementById('rtExternal').addEventListener('change', applyType);

    if (visit && window.jQuery && jQuery.fn.select2) {
        jQuery(visit).select2({
            dropdownParent: jQuery(modalEl),
            width: '100%',
            placeholder: 'Search visit or patient…',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('admin.blood-bank.requests.visit-search') }}',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return { results: (data || []).map(function (v) {
                        v.text = v.visit_number + ' — ' + (v.patient_name || 'Patient');
                        v.id = String(v.id);
                        return v;
                    }) };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            var bg = e.params.data.blood_group;
            if (bg && group) {
                var opt = Array.prototype.find.call(group.options, function (o) { return o.value === bg; });
                if (opt) group.value = bg;
            }
        });
    }

    applyType();
});
</script>
@endpush

@if($errors->any() && (old('blood_group') !== null || old('recipient_type') !== null))
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('createRequestModal');if(m&&window.bootstrap){bootstrap.Modal.getOrCreateInstance(m).show();}});</script>
@endpush
@endif
@endcan
@endsection
