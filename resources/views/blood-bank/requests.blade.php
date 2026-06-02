@extends('layouts.app')
@section('title', 'Blood Requests')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Requests</h4><p class="text-muted mb-0">Approve, crossmatch, issue, and record transfusion outcomes.</p></div>
    <a href="{{ route('admin.blood-bank.units.index') }}" class="btn btn-outline-secondary btn-sm">Unit Inventory</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Create Request</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blood-bank.requests.store') }}" class="row g-2">
            @csrf
            <div class="col-md-4"><label class="form-label">Visit</label><select name="visit_id" class="form-select" required><option value="">Select visit</option>@foreach($visits as $visit)<option value="{{ $visit->id }}">{{ $visit->visit_number }} — {{ $visit->patient->full_name ?? 'Patient' }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select" required>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Units</label><input type="number" min="1" name="units_requested" class="form-control" value="1" required></div>
            <div class="col-md-2"><label class="form-label">Priority</label><select name="priority" class="form-select"><option>ROUTINE</option><option>URGENT</option><option>EMERGENCY</option></select></div>
            <div class="col-md-2"><label class="form-label">Needed At</label><input type="datetime-local" name="needed_at" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Component</label><input name="component_type" class="form-control" value="WHOLE_BLOOD"></div>
            <div class="col-md-3"><label class="form-label">Diagnosis</label><input name="diagnosis" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Indication</label><input name="indication" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Create</button></div>
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
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Request</th><th>Patient</th><th>Blood Needed</th><th>Status</th><th>Crossmatch / Issue</th></tr></thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td class="fw-semibold">{{ $request->request_number }}<div class="small text-muted">{{ $request->requested_at?->format('d M Y H:i') }}</div></td>
                            <td>{{ $request->patient->full_name ?? '—' }}<div class="small text-muted">{{ $request->visit->visit_number ?? '' }}</div></td>
                            <td>{{ $request->blood_group }} {{ str_replace('_', ' ', $request->component_type) }}<div class="small text-muted">{{ $request->units_issued }}/{{ $request->units_requested }} issued · {{ $request->priority }}</div></td>
                            <td>
                                <span class="badge bg-{{ $request->status === 'PENDING' ? 'warning text-dark' : 'secondary' }}">{{ $request->status }}</span>
                                @if($request->status === 'PENDING')
                                    <form method="POST" action="{{ route('admin.blood-bank.requests.approve', $request) }}" class="mt-1">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Approve</button></form>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    <form method="POST" action="{{ route('admin.blood-bank.requests.crossmatches.store', $request) }}" class="d-flex gap-1">
                                        @csrf
                                        <input name="blood_unit_id" class="form-control form-control-sm" placeholder="Unit ID" required>
                                        <button class="btn btn-sm btn-outline-primary">Crossmatch</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.blood-bank.requests.issues.store', $request) }}" class="d-flex gap-1">
                                        @csrf
                                        <input name="blood_unit_id" class="form-control form-control-sm" placeholder="Unit ID" required>
                                        <input name="received_by_name" class="form-control form-control-sm" placeholder="Received by">
                                        <button class="btn btn-sm btn-outline-danger">Issue</button>
                                    </form>
                                    <div class="small text-muted">
                                        Crossmatches:
                                        @forelse($request->crossmatches as $xm)
                                            <span class="badge bg-{{ $xm->result === 'COMPATIBLE' ? 'success' : ($xm->result === 'INCOMPATIBLE' ? 'danger' : 'warning') }}">{{ $xm->unit->unit_number ?? $xm->blood_unit_id }} {{ $xm->result }}</span>
                                        @empty
                                            none
                                        @endforelse
                                    </div>
                                    @foreach($request->issues as $issue)
                                        <form method="POST" action="{{ route('admin.blood-bank.issues.transfuse', $issue) }}" class="d-flex gap-1 align-items-center">
                                            @csrf
                                            @method('PATCH')
                                            <span class="small">Issued {{ $issue->unit->unit_number ?? '' }}</span>
                                            <input name="reaction_notes" class="form-control form-control-sm" placeholder="Reaction notes">
                                            <button class="btn btn-sm btn-outline-success">Mark transfused</button>
                                        </form>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No blood requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
</div>
@endsection
