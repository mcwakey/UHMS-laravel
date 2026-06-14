@extends('layouts.app')
@section('title', __('blood_bank.blood_requests'))

@php($groups = ['O-','O+','A-','A+','B-','B+','AB-','AB+'])
@php($components = ['WHOLE_BLOOD'=>'Whole Blood','PRBC'=>'Packed Red Cells','PLASMA'=>'Plasma / FFP','PLATELETS'=>'Platelets','CRYOPRECIPITATE'=>'Cryoprecipitate'])

@section('content')
<x-page-header :title="__('blood_bank.blood_requests')" description="Recipient details, compatible units, crossmatch, issue, and transfusion outcomes." icon="ti-droplet">
    <x-slot:actions>
        @can('blood_bank.requests.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal"><i class="ti ti-plus me-1"></i>{{ __('blood_bank.new_request') }}</button>
        @endcan
        <a href="{{ route('admin.blood-bank.units.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-packages me-1"></i>{{ __('blood_bank.unit_inventory') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">{{ __('medication_administration.status') }}</label><select name="status" class="form-select"><option value="">{{ __('blood_bank.all') }}</option>@foreach(['PENDING','APPROVED','PARTIALLY_ISSUED','ISSUED','COMPLETED','CANCELLED'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">{{ __('blood_bank.blood_group') }}</label><select name="blood_group" class="form-select"><option value="">{{ __('blood_bank.all') }}</option>@foreach($groups as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a class="btn btn-outline-secondary flex-fill" href="{{ route('admin.blood-bank.requests.index') }}">{{ __('blood_bank.clear') }}</a>
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
                        @if($request->isExternal())<span class="badge bg-purple-lt"><i class="ti ti-building-hospital me-1"></i>{{ __('blood_bank.external') }}</span>@endif
                        <div class="small text-muted">
                            <i class="ti ti-user me-1"></i>{{ $request->recipientName() }}
                            @if($request->isExternal() && $recipient?->external_facility) · {{ $recipient->external_facility }}@endif
                            @if($request->visit) · {{ $request->visit->visit_number }}@endif
                            · {{ $request->requested_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                    <div class="text-end small">
                        <div>{{ __('blood_bank.recipient') }}: <strong>{{ $recipient?->blood_group ?? $request->blood_group }}</strong> · {{ str_replace('_',' ',$request->component_type) }} · {{ $request->units_issued }}/{{ $request->units_requested }} {{ __('blood_bank.issued') }}</div>
                        <div class="text-muted">{{ __('blood_bank.indication') }}: {{ $recipient?->clinical_indication ?: $request->indication ?: '—' }}@if($recipient?->previous_transfusion_reaction) · <span class="text-danger">{{ __('blood_bank.prior_reaction') }}</span>@endif</div>
                    </div>
                </div>

                @if($request->isExternal() && $request->bloodInvoice)
                    @php($inv = $request->bloodInvoice)
                    <div class="alert {{ $inv->is_paid ? 'alert-success' : 'alert-warning' }} d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 mb-2">
                        <div class="small">
                            <i class="ti ti-cash-register me-1"></i><strong>Cash invoice {{ $inv->invoice_number }}</strong>
                            · Total {{ $inv->formatted_total }} · Balance {{ $inv->formatted_balance }}
                            · <span class="badge {{ $inv->is_paid ? 'bg-success' : 'bg-warning text-dark' }}">{{ $inv->is_paid ? 'PAID' : 'UNPAID' }}</span>
                        </div>
                        @can('invoices.view')
                            <a href="{{ route('admin.billing.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-receipt me-1"></i>{{ __('blood_bank.view_collect_payment') }}</a>
                        @endcan
                    </div>
                @endif

                @if($request->status === 'PENDING')
                    <form method="POST" action="{{ route('admin.blood-bank.requests.approve', $request) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success"><i class="ti ti-check me-1"></i>{{ __('blood_bank.approve_request') }}</button></form>
                @endif

                @if(in_array($request->status, ['APPROVED','PARTIALLY_ISSUED']))
                <div class="row g-3 mt-1">
                    {{-- Compatible unit suggestions --}}
                    <div class="col-lg-8">
                        <div class="small fw-semibold mb-1">Compatible Available Units
                            <span class="text-muted">(recipient {{ $recipient?->blood_group ?? $request->blood_group }} · {{ str_replace('_',' ',$request->component_type) }})</span>
                        </div>
                        @if($units->isEmpty())
                            <div class="text-muted small">{{ __('blood_bank.no_compatible_units') }}</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead class="bg-light"><tr><th>{{ __('blood_bank.unit') }}</th><th>{{ __('blood_bank.group') }}</th><th>{{ __('blood_bank.expiry') }}</th><th>{{ __('blood_bank.compatibility') }}</th><th></th></tr></thead>
                                <tbody>
                                @foreach($units as $unit)
                                    <tr>
                                        <td>{{ $unit->unit_number }}@if($unit->is_exact_match) <span class="badge bg-success">exact</span>@endif</td>
                                        <td>{{ $unit->blood_group }}</td>
                                        <td>{{ $unit->expiry_date?->format('d M Y') }}<div class="small text-muted">{{ $unit->daysToExpiry() }}d</div></td>
                                        <td><x-status-badge :status="$unit->compatibility_status" domain="crossmatch" /></td>
                                        {{-- <td class="small text-muted" style="max-width:230px">{{ $unit->compatibility_reason }}</td> --}}
                                        <td class="text-nowrap">
                                            <form method="POST" action="{{ route('admin.blood-bank.requests.crossmatches.store', $request) }}" class="d-inline">@csrf<input type="hidden" name="blood_unit_id" value="{{ $unit->id }}"><button class="btn btn-sm btn-outline-primary">{{ __('blood_bank.crossmatch') }}</button></form>
                                            <form method="POST" action="{{ route('admin.blood-bank.requests.issues.store', $request) }}" class="d-inline">@csrf<input type="hidden" name="blood_unit_id" value="{{ $unit->id }}"><button class="btn btn-sm btn-outline-danger">{{ __('blood_bank.issue') }}</button></form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Emergency release --}}
                        <details class="small">
                            <summary class="text-danger">{{ __('blood_bank.emergency_release') }}</summary>
                            <form method="POST" action="{{ route('admin.blood-bank.requests.issues.store', $request) }}" class="row g-1 mt-1">
                                @csrf
                                <input type="hidden" name="emergency" value="1">
                                <div class="col-md-3"><input name="blood_unit_id" class="form-control form-control-sm" placeholder="Unit ID" required></div>
                                <div class="col-md-4"><select name="emergency_release_type" class="form-select form-select-sm" required><option value="">Release type…</option>@foreach($emergencyReleaseTypes as $t)<option value="{{ $t }}">{{ str_replace('_',' ',$t) }}</option>@endforeach</select></div>
                                <div class="col-md-3"><input name="emergency_release_reason" class="form-control form-control-sm" placeholder="Reason (required)" required></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-danger w-100">{{ __('blood_bank.release') }}</button></div>
                            </form>
                        </details>
                    </div>

                    {{-- Crossmatch + issue history --}}
                    <div class="col-lg-4">
                        <div class="small fw-semibold mb-1">{{ __('blood_bank.crossmatches') }}</div>
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
                            <div class="text-muted small mb-2">{{ __('blood_bank.no_crossmatches_yet') }}</div>
                        @endforelse

                        <div class="small fw-semibold mt-2 mb-1">{{ __('blood_bank.issued_transfusion') }}</div>
                        @foreach($request->issues as $issue)
                            <div class="border rounded p-2 mb-1">
                                <div class="small">Unit {{ $issue->unit->unit_number ?? '' }} · <x-status-badge :status="$issue->transfusion_status" domain="blood_issue" size="sm" />@if($issue->is_emergency_release) <span class="badge bg-dark">ER</span>@endif</div>
                                @if(!in_array($issue->transfusion_status, ['TRANSFUSED','REACTION_RECORDED']))
                                <form method="POST" action="{{ route('admin.blood-bank.issues.transfuse', $issue) }}" class="row g-1 mt-1">
                                    @csrf @method('PATCH')
                                    <div class="col-5"><select name="reaction_type" class="form-select form-select-sm"><option value="">{{ __('blood_bank.no_reaction') }}</option>@foreach(\App\Models\BloodIssue::REACTION_TYPES as $rt)<option value="{{ $rt }}">{{ str_replace('_',' ',$rt) }}</option>@endforeach</select></div>
                                    <div class="col-4"><input name="reaction_notes" class="form-control form-control-sm" placeholder="Reaction notes"></div>
                                    <div class="col-3"><button class="btn btn-sm btn-outline-success w-100">{{ __('blood_bank.record') }}</button></div>
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
            <x-empty-state icon="ti-droplet-off" :title="__('blood_bank.no_active_requests')" :message="__('blood_bank.no_blood_requests_found')" />
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
                    <h5 class="modal-title"><i class="ti ti-droplet-plus me-2 text-primary"></i>{{ __('blood_bank.create_blood_request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Recipient type --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('blood_bank.recipient') }}</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="recipient_type" id="rtPatient" value="PATIENT" @checked(old('recipient_type','PATIENT')==='PATIENT')>
                            <label class="btn btn-outline-primary" for="rtPatient"><i class="ti ti-user me-1"></i>{{ __('blood_bank.facility_patient') }}</label>
                            <input type="radio" class="btn-check" name="recipient_type" id="rtExternal" value="EXTERNAL" @checked(old('recipient_type')==='EXTERNAL')>
                            <label class="btn btn-outline-primary" for="rtExternal"><i class="ti ti-building-hospital me-1"></i>{{ __('blood_bank.external_referral') }}</label>
                        </div>
                    </div>

                    {{-- Facility patient block --}}
                    <div id="patientBlock" class="row g-3 mb-2">
                        <div class="col-12">
                            <label class="form-label">{{ __('blood_bank.visit') }} <span class="text-danger">*</span></label>
                            <select name="visit_id" id="requestVisit" class="form-select" style="width:100%">
                                <option value="">{{ __('blood_bank.search_visit_patient') }}</option>
                            </select>
                            <div class="form-text">{{ __('blood_bank.search_visit_help') }}</div>
                        </div>
                    </div>

                    {{-- External recipient block --}}
                    <div id="externalBlock" class="row g-3 mb-2 d-none">
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.recipient_name') }} <span class="text-danger">*</span></label><input name="external_name" class="form-control" value="{{ old('external_name') }}"></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.sex') }}</label><select name="external_sex" class="form-select"><option value="">—</option>@foreach(['Male','Female','Other'] as $sx)<option value="{{ $sx }}" @selected(old('external_sex')===$sx)>{{ $sx }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.age') }}</label><input name="external_age" type="number" min="0" max="150" class="form-control" value="{{ old('external_age') }}"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.referring_facility_ward') }}</label><input name="external_facility" class="form-control" value="{{ old('external_facility') }}"></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.contact') }}</label><input name="external_contact" class="form-control" value="{{ old('external_contact') }}"></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.external_ref') }}</label><input name="external_reference" class="form-control" value="{{ old('external_reference') }}"></div>
                        <div class="col-12"><div class="small text-muted"><i class="ti ti-cash-register me-1"></i>{{ __('blood_bank.external_invoice_help') }}</div></div>
                    </div>

                    <hr>
                    {{-- Order details --}}
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.blood_group') }} <span class="text-danger">*</span></label><select name="blood_group" id="requestGroup" class="form-select" required>@foreach($groups as $g)<option value="{{ $g }}" @selected(old('blood_group')===$g)>{{ $g }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.component') }}</label><select name="component_type" class="form-select">@foreach($components as $val=>$lbl)<option value="{{ $val }}" @selected(old('component_type')===$val)>{{ $lbl }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">{{ __('blood_bank.units') }} <span class="text-danger">*</span></label><input type="number" min="1" name="units_requested" class="form-control" value="{{ old('units_requested',1) }}" required></div>
                        <div class="col-md-4"><label class="form-label">{{ __('lab.urgency') }}</label><select name="priority" class="form-select"><option value="ROUTINE">{{ __('lab.routine') }}</option><option value="URGENT">{{ __('lab.urgent') }}</option><option value="EMERGENCY">{{ __('lab.emergency') }}</option><option value="MASSIVE_TRANSFUSION">Massive transfusion</option></select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.clinical_indication') }}</label><input name="clinical_indication" class="form-control" list="indications" value="{{ old('clinical_indication') }}"><datalist id="indications">@foreach($clinicalIndications as $ind)<option value="{{ $ind }}">@endforeach</datalist></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.diagnosis') }}</label><input name="diagnosis" class="form-control" value="{{ old('diagnosis') }}"></div>
                        <div class="col-md-3"><label class="form-label">Hb (g/dL)</label><input name="hb_level" class="form-control" value="{{ old('hb_level') }}"></div>
                        <div class="col-md-3"><label class="form-label">{{ __('blood_bank.pregnancy') }}</label><select name="pregnancy_status" class="form-select"><option value="">N/A</option>@foreach(['NOT_PREGNANT'=>__('blood_bank.not_pregnant'),'PREGNANT'=>__('blood_bank.pregnant'),'POSTPARTUM'=>__('blood_bank.postpartum')] as $val=>$lbl)<option value="{{ $val }}" @selected(old('pregnancy_status')===$val)>{{ $lbl }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.needed_at') }}</label><input type="datetime-local" name="needed_at" class="form-control" value="{{ old('needed_at') }}"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.special_requirements') }}</label><input name="special_requirements" class="form-control" value="{{ old('special_requirements') }}" placeholder="{{ __('blood_bank.special_requirements_placeholder') }}"></div>
                        <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="previous_transfusion_reaction" value="1" id="ptr" @checked(old('previous_transfusion_reaction'))><label class="form-check-label" for="ptr">{{ __('blood_bank.previous_transfusion_reaction') }}</label></div></div>
                        <div class="col-12"><label class="form-label">{{ __('blood_bank.previous_reaction_history') }}</label><input name="previous_transfusion_reaction_notes" class="form-control" value="{{ old('previous_transfusion_reaction_notes') }}"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('blood_bank.create_request') }}</button>
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
            placeholder: @json(__('blood_bank.search_visit_patient')),
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
