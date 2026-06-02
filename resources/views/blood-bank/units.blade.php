@extends('layouts.app')
@section('title', 'Blood Units')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Units</h4><p class="text-muted mb-0">Inventory and safety state for every unit.</p></div>
    <a href="{{ route('admin.blood-bank.donations.index') }}" class="btn btn-outline-secondary btn-sm">Donations</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Component</label><input name="component_type" class="form-control" value="{{ $filters['component_type'] ?? '' }}" placeholder="WHOLE_BLOOD"></div>
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option>@foreach(['QUARANTINED','AVAILABLE','RESERVED','CROSSMATCHED','ISSUED','TRANSFUSED','EXPIRED','DISCARDED'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary w-100">Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.blood-bank.units.index') }}">Clear</a></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Unit</th><th>Group</th><th>Component</th><th>Screening</th><th>Status</th><th>Storage</th><th>Expiry</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse($units as $unit)
                        <tr>
                            <td class="fw-semibold">{{ $unit->unit_number }}<div class="small text-muted">{{ $unit->donation->donation_number ?? 'Manual unit' }}</div></td>
                            <td>{{ $unit->blood_group }}</td>
                            <td>{{ str_replace('_', ' ', $unit->component_type) }}</td>
                            <td><x-status-badge :status="$unit->screening_status" domain="screening" /></td>
                            <td><x-status-badge :status="$unit->status" domain="blood_unit" /></td>
                            <td>{{ $unit->storageLocation->name ?? '—' }}</td>
                            <td class="{{ $unit->expiry_date?->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $unit->expiry_date?->format('d M Y') }}</td>
                            <td class="text-end">
                                @if(!in_array($unit->status, ['ISSUED','TRANSFUSED','DISCARDED'], true))
                                    <form method="POST" action="{{ route('admin.blood-bank.units.discard', $unit) }}" class="d-inline-flex gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <input name="discard_reason" class="form-control form-control-sm" placeholder="Discard reason" required>
                                        <button class="btn btn-outline-danger btn-sm">Discard</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($units->hasPages())<div class="card-footer">{{ $units->links() }}</div>@endif
</div>
@endsection
