@extends('layouts.app')
@section('title', 'Blood Donations')

@section('content')
<x-page-header title="Blood Donations" description="Record collection and screening outcomes. Units stay quarantined until screening passes." icon="ti-droplet">
    <x-slot:actions>
        <a href="{{ route('admin.blood-bank.donors.index') }}" class="btn btn-outline-secondary btn-sm">Donors</a>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Record Donation</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.donations.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><label class="form-label">Donor</label><select name="donor_id" class="form-select" required><option value="">Select donor</option>@foreach($donors as $donor)<option value="{{ $donor->id }}">{{ $donor->donor_number }} — {{ $donor->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select" required>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Component</label><select name="component_type" class="form-select"><option value="WHOLE_BLOOD">Whole Blood</option><option value="PRBC">Packed Red Cells</option><option value="PLASMA">Plasma / FFP</option><option value="PLATELETS">Platelets</option><option value="CRYOPRECIPITATE">Cryoprecipitate</option></select></div>
            <div class="col-md-2"><label class="form-label">Volume ml</label><input name="volume_ml" type="number" class="form-control" value="450"></div>
            <div class="col-md-3"><label class="form-label">Storage</label><select name="storage_location_id" class="form-select"><option value="">Unassigned</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Donation Date</label><input type="datetime-local" name="donation_date" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
            <div class="col-12 small text-muted">Only <strong>eligible</strong> donors are listed. Use override (with reason) to collect from a non-eligible donor.</div>
            <div class="col-md-3 d-flex align-items-center pt-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="override" value="1" id="ovr"><label class="form-check-label small" for="ovr">Override eligibility</label></div></div>
            <div class="col-md-5"><input name="override_reason" class="form-control" placeholder="Override reason (if overriding)"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Record</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Donation Worklist</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Donation</th><th>Donor</th><th>Unit</th><th>Screening</th><th>Collected</th><th class="text-end">Override</th></tr></thead>
                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td>{{ $donation->donation_number }}</td>
                            <td>{{ $donation->donor->full_name ?? '—' }}<div class="small text-muted">{{ $donation->blood_group }}</div></td>
                            <td>{{ $donation->unit->unit_number ?? '—' }}<div class="small text-muted">{{ $donation->unit->status ?? '' }}</div></td>
                            <td><x-status-badge :status="$donation->screening_status" domain="screening" /></td>
                            <td>{{ $donation->donation_date?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.blood-bank.donations.screening', $donation) }}" class="d-inline-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="screening_status" class="form-select form-select-sm" required>
                                        @foreach(['PASSED','FAILED','INCONCLUSIVE','PENDING'] as $status)<option value="{{ $status }}" @selected($donation->screening_status === $status)>{{ $status }}</option>@endforeach
                                    </select>
                                    <input name="screening_notes" class="form-control form-control-sm" placeholder="Notes">
                                    <button class="btn btn-sm btn-outline-primary">Set</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="6" class="py-2">
                                <div class="small fw-semibold mb-1">Infectious-disease screening panel</div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 bg-white">
                                        <thead><tr><th>Test</th><th>Result</th><th>Performed By</th><th>Verified</th><th class="text-end">Record</th></tr></thead>
                                        <tbody>
                                        @foreach($donation->tests as $test)
                                            <tr>
                                                <td>{{ $test->test_name }}@if($test->mandatory) <span class="text-danger" title="mandatory">*</span>@endif</td>
                                                <td><x-status-badge :status="$test->result" domain="screening" size="sm" /></td>
                                                <td class="small">{{ $test->performedBy->full_name ?? '—' }}</td>
                                                <td class="small">@if($test->verified_at)<span class="text-success">✓ {{ $test->verifiedBy->full_name ?? '' }}</span>@else<form method="POST" action="{{ route('admin.blood-bank.donations.tests.verify', $test) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-link btn-sm p-0" @disabled($test->result === 'NOT_DONE')>verify</button></form>@endif</td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('admin.blood-bank.donations.tests.store', $donation) }}" class="d-inline-flex gap-1 justify-content-end">
                                                        @csrf
                                                        <input type="hidden" name="test_code" value="{{ $test->test_code }}">
                                                        <select name="result" class="form-select form-select-sm" style="width:auto">
                                                            @foreach(['NOT_DONE','NEGATIVE','NON_REACTIVE','POSITIVE','REACTIVE','INCONCLUSIVE'] as $r)<option value="{{ $r }}" @selected($test->result === $r)>{{ str_replace('_',' ',$r) }}</option>@endforeach
                                                        </select>
                                                        <button class="btn btn-sm btn-outline-secondary">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="ti-droplet-off" title="No donations" message="No donations recorded yet." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donations->hasPages())<div class="card-footer">{{ $donations->links() }}</div>@endif
</div>
@endsection
