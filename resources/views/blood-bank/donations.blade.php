@extends('layouts.app')
@section('title', __('blood_bank.blood_donations'))

@php($groups = ['O-','O+','A-','A+','B-','B+','AB-','AB+'])
@php($components = ['WHOLE_BLOOD'=>'Whole Blood','PRBC'=>'Packed Red Cells','PLASMA'=>'Plasma / FFP','PLATELETS'=>'Platelets','CRYOPRECIPITATE'=>'Cryoprecipitate'])

@section('content')
<x-page-header :title="__('blood_bank.blood_donations')" description="Record collection and screening. Units stay quarantined until screening passes." icon="ti-droplet">
    <x-slot:actions>
        @can('blood_bank.donations.record')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordDonationModal"><i class="ti ti-droplet-plus me-1"></i>{{ __('blood_bank.record_donation') }}</button>
        @endcan
        @can('blood_bank.settings.manage')
            <a href="{{ route('admin.blood-bank.storage.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-fridge me-1"></i>{{ __('blood_bank.storage_locations') }}</a>
        @endcan
        <a href="{{ route('admin.blood-bank.donors.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-users me-1"></i>{{ __('blood_bank.donors') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('blood_bank.donation_worklist') }}</h5>
        <form class="d-flex gap-2">
            <select name="screening_status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">{{ __('blood_bank.all_screening_states') }}</option>
                @foreach(['PENDING','PASSED','FAILED','INCONCLUSIVE'] as $s)<option value="{{ $s }}" @selected(($filters['screening_status'] ?? '')===$s)>{{ $s }}</option>@endforeach
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>{{ __('blood_bank.donation') }}</th><th>{{ __('blood_bank.donor') }}</th><th>{{ __('blood_bank.group') }}</th><th>{{ __('blood_bank.unit') }}</th><th>{{ __('blood_bank.screening') }}</th><th>{{ __('blood_bank.collected') }}</th><th class="text-end">{{ __('blood_bank.action') }}</th></tr></thead>
                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td class="fw-semibold">{{ $donation->donation_number }}</td>
                            <td>{{ $donation->donor->full_name ?? '—' }}</td>
                            <td><span class="badge bg-red-lt">{{ $donation->blood_group }}</span></td>
                            <td>{{ $donation->unit->unit_number ?? '—' }}<div class="small text-muted">{{ str_replace('_',' ',$donation->unit->status ?? '') }}</div></td>
                            <td><x-status-badge :status="$donation->screening_status" domain="screening" /></td>
                            <td>{{ $donation->donation_date?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.donations.show', $donation) }}"><i class="ti ti-microscope me-1"></i>Screening &amp; Tests</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="ti-droplet-off" :title="__('blood_bank.no_donations')" :message="__('blood_bank.no_donations_message')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donations->hasPages())<div class="card-footer">{{ $donations->links() }}</div>@endif
</div>

@can('blood_bank.donations.record')
{{-- Record Donation modal --}}
<div class="modal fade" id="recordDonationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.blood-bank.donations.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-droplet-plus me-2 text-primary"></i>{{ __('blood_bank.record_donation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">{{ __('blood_bank.donor') }} <span class="text-danger">*</span></label>
                            <select name="donor_id" id="recordDonor" class="form-select" required>
                                <option value="">{{ __('blood_bank.search_donor') }}</option>
                                @foreach($donors as $donor)
                                    <option value="{{ $donor->id }}"
                                        data-blood-group="{{ $donor->blood_group }}"
                                        data-eligible="{{ $donor->canDonate() ? 1 : 0 }}"
                                        data-screening="{{ $donor->screening_status }}"
                                        @selected(old('donor_id')==$donor->id)>
                                        {{ $donor->donor_number }} — {{ $donor->full_name }} ({{ str_replace('_',' ',$donor->screening_status ?? 'REGISTERED') }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="donorEligHint" class="small mt-1"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">{{ __('blood_bank.blood_group') }} <span class="text-danger">*</span></label>
                            <select name="blood_group" id="recordBloodGroup" class="form-select" required>
                                @foreach($groups as $g)<option value="{{ $g }}" @selected(old('blood_group')===$g)>{{ $g }}</option>@endforeach
                            </select>
                            <div class="form-text">Auto-filled from the donor profile; adjust if needed.</div>
                        </div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.component') }}</label><select name="component_type" class="form-select">@foreach($components as $val=>$lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.volume_ml') }}</label><input name="volume_ml" type="number" class="form-control" value="450"></div>
                        <div class="col-md-4"><label class="form-label">{{ __('blood_bank.storage_locations') }}</label><select name="storage_location_id" class="form-select"><option value="">{{ __('blood_bank.unassigned') }}</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.donation_date') }}</label><input type="datetime-local" name="donation_date" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">{{ __('blood_bank.expiry_date') }} <span class="text-muted small">({{ __('blood_bank.auto_if_blank') }})</span></label><input type="date" name="expiry_date" class="form-control"></div>
                        <div class="col-12"><label class="form-label">{{ __('blood_bank.notes') }}</label><input name="notes" class="form-control"></div>

                        @can('blood_bank.donor.override_eligibility')
                        <div class="col-12">
                            <div class="border rounded p-2" id="overrideBox">
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="override" value="1" id="ovr" @checked(old('override'))><label class="form-check-label fw-semibold" for="ovr">{{ __('blood_bank.override_eligibility') }}</label></div>
                                <input name="override_reason" id="overrideReason" class="form-control form-control-sm mt-2" value="{{ old('override_reason') }}" placeholder="Reason (required when collecting from a non-eligible donor)">
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Record &amp; Quarantine Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var donor = document.getElementById('recordDonor');
    var group = document.getElementById('recordBloodGroup');
    var hint = document.getElementById('donorEligHint');
    var overrideBox = document.getElementById('overrideBox');
    var overrideChk = document.getElementById('ovr');

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(donor).select2({ dropdownParent: jQuery('#recordDonationModal'), width: '100%', placeholder: @json(__('blood_bank.search_donor')) });
    }

    function sync() {
        if (!donor) return;
        var opt = donor.options[donor.selectedIndex];
        if (!opt || !opt.value) { if (hint) hint.innerHTML = ''; return; }
        var bg = opt.getAttribute('data-blood-group');
        var eligible = opt.getAttribute('data-eligible') === '1';
        if (bg && group) { group.value = bg; }
        if (hint) {
            hint.innerHTML = eligible
                ? '<span class="text-success"><i class="ti ti-circle-check me-1"></i>{{ __('blood_bank.eligible_donor') }}</span>'
                : '<span class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Not eligible — collecting requires override with a reason.</span>';
        }
        if (overrideBox && overrideChk && !eligible) {
            overrideBox.classList.add('border-danger');
            overrideChk.checked = true;
        } else if (overrideBox && overrideChk) {
            overrideBox.classList.remove('border-danger');
        }
    }
    if (donor) { jQuery(donor).on('change', sync); donor.addEventListener('change', sync); sync(); }
});
</script>
@endpush

@if(old('donor_id') !== null)
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('recordDonationModal');if(m&&window.bootstrap){bootstrap.Modal.getOrCreateInstance(m).show();}});</script>
@endpush
@endif
@endcan
@endsection
