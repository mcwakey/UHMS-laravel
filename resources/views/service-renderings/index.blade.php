@extends('layouts.app')
@section('title', 'Service Renderings')

@section('content')
<x-page-header title="Service Renderings" description="Track fulfilment of billed services that do not have a specialist workflow." icon="ti-clipboard-check">
    <x-slot:actions>
        @can('service_rendering.reports')
            <a href="{{ route('admin.service-renderings.reports', request()->query()) }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-report-analytics me-1"></i>Reports
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

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
        <form method="GET" action="{{ route('admin.service-renderings.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input class="form-control form-control-sm" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Patient, visit, invoice, service">
            </div>
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
                <label class="form-label small">Department</label>
                <select class="form-select form-select-sm" name="department_id">
                    <option value="">All</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string)($filters['department_id'] ?? '') === (string)$department->id)>{{ $department->name }}</option>
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
            <div class="col-md-1 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" type="submit">Filter</button>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input class="form-control form-control-sm" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input class="form-control form-control-sm" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Rendered By</label>
                <select class="form-select form-select-sm" name="rendered_by">
                    <option value="">Anyone</option>
                    @foreach($staff as $person)
                        <option value="{{ $person->id }}" @selected((string)($filters['rendered_by'] ?? '') === (string)$person->id)>{{ trim($person->first_name . ' ' . $person->last_name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Service</label>
                <select class="form-select form-select-sm" name="service_id">
                    <option value="">All services</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected((string)($filters['service_id'] ?? '') === (string)$service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a class="btn btn-outline-secondary btn-sm w-100" href="{{ route('admin.service-renderings.index') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Patient / Visit</th>
                        <th>Service</th>
                        <th>Department</th>
                        <th>Invoice</th>
                        <th>Payment</th>
                        <th>Rendering</th>
                        <th>Rendered By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($renderings as $rendering)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $rendering->patient?->full_name ?? 'Unknown patient' }}</div>
                                <small class="text-muted">{{ $rendering->patient?->patient_number }} @if($rendering->visit) / {{ $rendering->visit->visit_number }} @endif</small>
                                @if($rendering->emergencyCase)
                                    <div><span class="badge bg-danger-subtle text-danger">{{ $rendering->emergencyCase->emergency_number }}</span></div>
                                @elseif($rendering->admission)
                                    <div><span class="badge bg-primary-subtle text-primary">{{ $rendering->admission->admission_number }}</span></div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $rendering->service?->name ?? 'Service' }}</div>
                                <small class="text-muted">{{ $rendering->invoiceItem?->description }}</small>
                            </td>
                            <td>{{ $rendering->department?->name ?? 'Unassigned' }}</td>
                            <td>
                                <div>{{ $rendering->invoiceItem?->invoice?->invoice_number ?? 'No invoice' }}</div>
                                <small class="text-muted">Item #{{ $rendering->invoice_item_id }}</small>
                            </td>
                            <td>
                                @php $payment = $rendering->invoiceItem?->payment_status ?? 'unpaid'; @endphp
                                <span class="badge bg-{{ $payment === 'paid' ? 'success' : ($payment === 'partially_paid' ? 'warning text-dark' : 'danger') }}">{{ ucwords(str_replace('_', ' ', $payment)) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $rendering->status_color }}">{{ str_replace('_', ' ', $rendering->status) }}</span>
                                <div class="small text-muted">{{ $rendering->created_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                <div>{{ $rendering->renderedBy?->full_name ?? $rendering->renderedBy?->name ?? 'Not rendered' }}</div>
                                <small class="text-muted">{{ $rendering->rendered_at?->format('d M Y H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('admin.service-renderings.show', $rendering) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    @can('service_rendering.start')
                                        @if($rendering->can_be_started)
                                            <form method="POST" action="{{ route('admin.service-renderings.start', $rendering) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-info">Start</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state message="No service renderings found." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($renderings->hasPages())
        <div class="card-footer">{{ $renderings->links() }}</div>
    @endif
</div>
@endsection
