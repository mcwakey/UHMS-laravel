@extends('layouts.app')
@section('title', 'Service Rendering')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-clipboard-check me-2 text-primary"></i>{{ $rendering->service?->name ?? 'Service Rendering' }}</h4>
        <p class="text-muted mb-0">{{ $rendering->patient?->full_name ?? 'Unknown patient' }} / {{ $rendering->visit?->visit_number ?? 'No visit' }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($rendering->visit)
            <a href="{{ route('admin.visits.preview', $rendering->visit) }}" class="btn btn-outline-info btn-sm">
                <i class="ti ti-eye-search me-1"></i>Visit Preview
            </a>
        @endif
        <a href="{{ route('admin.service-renderings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

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

@php
    $paymentStatus = $rendering->invoiceItem?->payment_status ?? 'unpaid';
    $isUnrenderedButPayable = $rendering->status === \App\Models\ServiceRendering::STATUS_NOT_RENDERED
        && ! in_array($paymentStatus, ['cancelled', 'voided', 'waived'], true)
        && (float) ($rendering->invoiceItem?->patient_payable ?? 0) > 0;
@endphp

@if($isUnrenderedButPayable)
    <div class="alert alert-warning">
        This service is documented as not rendered, but the invoice item is still financially active. Billing or cashier staff should review the invoice item separately.
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <span class="text-muted small">Rendering Status</span>
                        <div><span class="badge bg-{{ $rendering->status_color }} fs-6">{{ str_replace('_', ' ', $rendering->status) }}</span></div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small">Payment Status</span>
                        <div><span class="badge bg-{{ $paymentStatus === 'paid' ? 'success' : ($paymentStatus === 'partially_paid' ? 'warning text-dark' : 'danger') }}">{{ ucwords(str_replace('_', ' ', $paymentStatus)) }}</span></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Patient</div>
                        <div class="fw-semibold">{{ $rendering->patient?->full_name ?? 'Unknown patient' }}</div>
                        <small class="text-muted">{{ $rendering->patient?->patient_number }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Visit</div>
                        <div class="fw-semibold">{{ $rendering->visit?->visit_number ?? 'No visit number' }}</div>
                        <small class="text-muted">{{ $rendering->visit?->visit_type?->label() ?? '' }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Service</div>
                        <div class="fw-semibold">{{ $rendering->service?->name ?? 'Service' }}</div>
                        <small class="text-muted">{{ $rendering->invoiceItem?->description }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Department</div>
                        <div class="fw-semibold">{{ $rendering->department?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Invoice</div>
                        <div class="fw-semibold">{{ $rendering->invoiceItem?->invoice?->invoice_number ?? 'No invoice' }}</div>
                        <small class="text-muted">Item #{{ $rendering->invoice_item_id }}</small>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Amount</div>
                        <div class="fw-semibold">GHS {{ number_format((float) ($rendering->invoiceItem?->patient_payable ?? 0), 2) }}</div>
                        <small class="text-muted">Rendering never changes invoice payment state.</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Started By</div>
                        <div>{{ $rendering->startedBy?->full_name ?? $rendering->startedBy?->name ?? 'Not started' }}</div>
                        <small class="text-muted">{{ $rendering->started_at?->format('d M Y H:i') }}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Rendered By</div>
                        <div>{{ $rendering->renderedBy?->full_name ?? $rendering->renderedBy?->name ?? 'Not rendered' }}</div>
                        <small class="text-muted">{{ $rendering->rendered_at?->format('d M Y H:i') }}</small>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Context</div>
                        <div>
                            @if($rendering->emergencyCase)
                                <span class="badge bg-danger-subtle text-danger">{{ $rendering->emergencyCase->emergency_number }}</span>
                            @elseif($rendering->admission)
                                <span class="badge bg-primary-subtle text-primary">{{ $rendering->admission->admission_number }}</span>
                            @elseif($rendering->consultationRoute)
                                <span class="badge bg-info-subtle text-info">Consultation Route #{{ $rendering->consultationRoute->id }}</span>
                            @else
                                <span class="badge bg-light text-dark">Visit Service</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($rendering->result_summary || $rendering->notes || $rendering->reason_not_rendered)
                    <hr>
                    @if($rendering->result_summary)
                        <h6 class="fw-semibold">Result Summary</h6>
                        <p class="mb-3">{{ $rendering->result_summary }}</p>
                    @endif
                    @if($rendering->reason_not_rendered)
                        <h6 class="fw-semibold">Reason Not Rendered</h6>
                        <p class="mb-3">{{ $rendering->reason_not_rendered }}</p>
                    @endif
                    @if($rendering->notes)
                        <h6 class="fw-semibold">Notes</h6>
                        <p class="mb-0">{{ $rendering->notes }}</p>
                    @endif
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Audit Trail</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rendering->logs->sortByDesc('created_at') as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                    <td>{{ str_replace('_', ' ', $log->action) }}</td>
                                    <td>{{ $log->from_status ? str_replace('_', ' ', $log->from_status).' -> ' : '' }}{{ str_replace('_', ' ', $log->to_status ?? '') }}</td>
                                    <td>{{ $log->performedBy?->full_name ?? $log->performedBy?->name ?? 'System' }}</td>
                                    <td>{{ $log->reason ?: $log->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No rendering log entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        @can('service_rendering.start')
            @if($rendering->can_be_started)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">Start Rendering</h6>
                        <form method="POST" action="{{ route('admin.service-renderings.start', $rendering) }}">
                            @csrf
                            <textarea class="form-control mb-2" name="notes" rows="2" placeholder="Optional start note"></textarea>
                            <button class="btn btn-info w-100" type="submit">Start Service</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.mark_rendered')
            @if($rendering->can_be_closed)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">Mark Rendered</h6>
                        <form method="POST" action="{{ route('admin.service-renderings.mark-rendered', $rendering) }}">
                            @csrf
                            <label class="form-label small">Rendered At</label>
                            <input type="datetime-local" class="form-control mb-2" name="rendered_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                            <label class="form-label small">Result Summary</label>
                            <textarea class="form-control mb-2" name="result_summary" rows="3" placeholder="What was done, result, or outcome"></textarea>
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-success w-100" type="submit">Mark Rendered</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.mark_not_rendered')
            @if($rendering->can_be_closed)
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-semibold">Mark Not Rendered</h6>
                        <form method="POST" action="{{ route('admin.service-renderings.mark-not-rendered', $rendering) }}">
                            @csrf
                            <label class="form-label small">Reason</label>
                            <textarea class="form-control mb-2" name="reason_not_rendered" rows="3" required></textarea>
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">Mark Not Rendered</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('service_rendering.edit_notes')
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold">Update Notes</h6>
                    <form method="POST" action="{{ route('admin.service-renderings.notes', $rendering) }}">
                        @csrf
                        @method('PATCH')
                        <label class="form-label small">Result Summary</label>
                        <textarea class="form-control mb-2" name="result_summary" rows="3">{{ old('result_summary', $rendering->result_summary) }}</textarea>
                        <label class="form-label small">Notes</label>
                        <textarea class="form-control mb-2" name="notes" rows="3">{{ old('notes', $rendering->notes) }}</textarea>
                        <button class="btn btn-outline-primary w-100" type="submit">Save Notes</button>
                    </form>
                </div>
            </div>
        @endcan

        @can('service_rendering.cancel')
            @if($rendering->status !== \App\Models\ServiceRendering::STATUS_CANCELLED)
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="fw-semibold text-danger">Cancel Rendering</h6>
                        <form method="POST" action="{{ route('admin.service-renderings.cancel', $rendering) }}">
                            @csrf
                            <label class="form-label small">Reason</label>
                            <textarea class="form-control mb-2" name="reason" rows="3" required></textarea>
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control mb-2" name="notes" rows="2"></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">Cancel Rendering</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
