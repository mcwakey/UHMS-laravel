@extends('layouts.app')
@section('title', 'Service Rendering Reports')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-report-analytics me-2 text-primary"></i>Service Rendering Reports</h4>
        <p class="text-muted mb-0">Operational fulfilment counts for billed non-specialized services.</p>
    </div>
    <a href="{{ route('admin.service-renderings.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back to Worklist
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="text-muted small">Total</div><div class="h4 mb-0">{{ $summary['total'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-warning-subtle"><div class="card-body py-3"><div class="text-warning small">Pending</div><div class="h4 mb-0">{{ $summary['pending'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-info-subtle"><div class="card-body py-3"><div class="text-info small">In Progress</div><div class="h4 mb-0">{{ $summary['in_progress'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-success-subtle"><div class="card-body py-3"><div class="text-success small">Rendered</div><div class="h4 mb-0">{{ $summary['rendered'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-danger-subtle"><div class="card-body py-3"><div class="text-danger small">Not Rendered</div><div class="h4 mb-0">{{ $summary['not_rendered'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-secondary-subtle"><div class="card-body py-3"><div class="text-secondary small">Cancelled</div><div class="h4 mb-0">{{ $summary['cancelled'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.service-renderings.reports') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Payment</label>
                <select class="form-select form-select-sm" name="payment_status">
                    <option value="">All</option>
                    @foreach(['unpaid', 'partially_paid', 'paid', 'waived', 'cancelled', 'voided'] as $status)
                        <option value="{{ $status }}" @selected(($filters['payment_status'] ?? '') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Source</label>
                <select class="form-select form-select-sm" name="source">
                    <option value="">All</option>
                    @foreach(['opd' => 'OPD / Visit', 'emergency' => 'Emergency', 'admission' => 'Admission', 'consultation' => 'Consultation'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input class="form-control form-control-sm" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input class="form-control form-control-sm" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" type="submit">Apply</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.service-renderings.reports') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">By Department</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Department</th>
                                <th>Status</th>
                                <th class="text-end">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byDepartment as $department => $rows)
                                @foreach($rows as $row)
                                    <tr>
                                        <td>{{ $department }}</td>
                                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $row->status) }}</span></td>
                                        <td class="text-end">{{ $row->total }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="3"><x-empty-state message="No report data for the selected filters." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Rendered By Staff</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Staff</th>
                                <th class="text-end">Rendered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byStaff as $row)
                                <tr>
                                    <td>{{ $row->renderedBy?->full_name ?? $row->renderedBy?->name ?? 'Unknown' }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2"><x-empty-state message="No rendered services yet." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
