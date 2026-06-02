@extends('layouts.app')
@section('title', 'Blood Donations')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Donations</h4><p class="text-muted mb-0">Record collection and screening outcomes. Units stay quarantined until screening passes.</p></div>
    <a href="{{ route('admin.blood-bank.donors.index') }}" class="btn btn-outline-secondary btn-sm">Donors</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Record Donation</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.donations.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><label class="form-label">Donor</label><select name="donor_id" class="form-select" required><option value="">Select donor</option>@foreach($donors as $donor)<option value="{{ $donor->id }}">{{ $donor->donor_number }} — {{ $donor->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select" required>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Component</label><input name="component_type" class="form-control" value="WHOLE_BLOOD"></div>
            <div class="col-md-2"><label class="form-label">Volume ml</label><input name="volume_ml" type="number" class="form-control" value="450"></div>
            <div class="col-md-3"><label class="form-label">Storage</label><select name="storage_location_id" class="form-select"><option value="">Unassigned</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Donation Date</label><input type="datetime-local" name="donation_date" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Record</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Donation Worklist</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Donation</th><th>Donor</th><th>Unit</th><th>Screening</th><th>Collected</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($donations as $donation)
                        <tr>
                            <td>{{ $donation->donation_number }}</td>
                            <td>{{ $donation->donor->full_name ?? '—' }}<div class="small text-muted">{{ $donation->blood_group }}</div></td>
                            <td>{{ $donation->unit->unit_number ?? '—' }}<div class="small text-muted">{{ $donation->unit->status ?? '' }}</div></td>
                            <td><span class="badge bg-{{ $donation->screening_status === 'PASSED' ? 'success' : ($donation->screening_status === 'FAILED' ? 'danger' : 'warning') }}">{{ $donation->screening_status }}</span></td>
                            <td>{{ $donation->donation_date?->format('d M Y H:i') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.blood-bank.donations.screening', $donation) }}" class="d-inline-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="screening_status" class="form-select form-select-sm" required>
                                        @foreach(['PASSED','FAILED','INCONCLUSIVE','PENDING'] as $status)<option value="{{ $status }}" @selected($donation->screening_status === $status)>{{ $status }}</option>@endforeach
                                    </select>
                                    <input name="screening_notes" class="form-control form-control-sm" placeholder="Notes">
                                    <button class="btn btn-sm btn-outline-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No donations recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($donations->hasPages())<div class="card-footer">{{ $donations->links() }}</div>@endif
</div>
@endsection
