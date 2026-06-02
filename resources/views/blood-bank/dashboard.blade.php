@extends('layouts.app')
@section('title', 'Blood Bank Dashboard')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-droplet-filled text-danger me-2"></i>Blood Bank Dashboard</h4>
        <p class="text-muted mb-0">Inventory, requests, expiring units, and issue safety at a glance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.blood-bank.requests.index') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>Request Blood</a>
        <a href="{{ route('admin.blood-bank.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-report-analytics me-1"></i>Reports</a>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Available Units', $summary['available_units'] ?? 0, 'success'],
        ['Quarantined', $summary['quarantined_units'] ?? 0, 'warning'],
        ['Pending Requests', $summary['pending_requests'] ?? 0, 'primary'],
        ['Issued Today', $summary['issued_today'] ?? 0, 'info'],
        ['Expiring Soon', $summary['expiring_soon'] ?? 0, 'danger'],
    ] as [$label, $value, $color])
        <div class="col-6 col-xl">
            <div class="card border-start border-{{ $color }} border-3">
                <div class="card-body py-3">
                    <div class="small text-muted">{{ $label }}</div>
                    <div class="h4 mb-0">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Inventory By Group</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.units.index') }}">All Units</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>Group</th><th>Component</th><th>Status</th><th class="text-end">Units</th></tr></thead>
                        <tbody>
                            @forelse($inventoryByGroup as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->blood_group }}</td>
                                    <td>{{ str_replace('_', ' ', $row->component_type) }}</td>
                                    <td><span class="badge bg-secondary">{{ $row->status }}</span></td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No blood inventory recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pending / Active Requests</h5>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.blood-bank.requests.index') }}">Open Worklist</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>Request</th><th>Patient</th><th>Blood</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($pendingRequests as $request)
                                <tr>
                                    <td>{{ $request->request_number }}</td>
                                    <td>{{ $request->patient->full_name ?? '—' }}</td>
                                    <td>{{ $request->blood_group }} {{ str_replace('_', ' ', $request->component_type) }} x{{ $request->units_requested }}</td>
                                    <td><span class="badge bg-warning text-dark">{{ $request->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No active blood requests.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h5 class="card-title mb-0 text-danger">Expiring Soon</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>Unit</th><th>Group</th><th>Location</th><th>Expiry</th></tr></thead>
                        <tbody>
                            @forelse($expiringUnits as $unit)
                                <tr>
                                    <td>{{ $unit->unit_number }}</td>
                                    <td>{{ $unit->blood_group }}</td>
                                    <td>{{ $unit->storageLocation->name ?? '—' }}</td>
                                    <td class="text-danger fw-semibold">{{ $unit->expiry_date?->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No units expiring in the next 7 days.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
