@extends('layouts.app')
@section('title', 'Service Renderings')

@section('content')
<x-page-header title="Service Renderings" description="Track fulfilment of billed services that do not have a specialist workflow." icon="ti-clipboard-check">
    <x-slot:actions>
        @can('invoices.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="ti ti-plus me-1"></i>Add Service to Bill
            </button>
        @endcan
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
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1">{{ $summary['total'] ?? 0 }}</h3><p class="text-muted mb-0">Total</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-warning">{{ $summary['pending'] ?? 0 }}</h3><p class="text-muted mb-0">Pending</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-info">{{ $summary['in_progress'] ?? 0 }}</h3><p class="text-muted mb-0">In Progress</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-success">{{ $summary['rendered'] ?? 0 }}</h3><p class="text-muted mb-0">Rendered</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-danger">{{ $summary['not_rendered'] ?? 0 }}</h3><p class="text-muted mb-0">Not Rendered</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-secondary">{{ $summary['cancelled'] ?? 0 }}</h3><p class="text-muted mb-0">Cancelled</p></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.service-renderings.index') }}" class="row g-2 align-items-end" data-auto-filter-form="service-renderings-index">
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
                @include('partials.date-range-filter', [
                    'id' => 'serviceRenderingDateRangePicker',
                    'value' => $filters['date_range'] ?? '',
                    'labelClass' => 'small',
                    'submitOnApply' => true,
                ])
            </div>
            <div class="col-md-auto">
                <div class="d-flex gap-1">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="ti ti-filter me-1"></i>Filter</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.service-renderings.index') }}"><i class="ti ti-x"></i></a>
                </div>
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

@can('invoices.create')
{{-- Add Service to Bill --}}
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.service-renderings.store') }}" id="addServiceForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0"><i class="ti ti-clipboard-plus me-2 text-primary"></i>Add Service to Bill</h5>
                        <small class="text-muted">Bill a service to a patient's visit — a rendering task is created for the department automatically.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Patient Visit <span class="text-danger">*</span></label>
                            <select name="visit_id" id="addSvcVisit" class="form-select" style="width:100%" required>
                                <option value="">Search by visit number or patient…</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select id="addSvcDepartment" class="form-select" required>
                                <option value="">Choose a department…</option>
                                @foreach($renderableDepartments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @if($renderableDepartments->isEmpty())
                                <div class="form-text text-danger">No departments have rendering-tracked services configured yet.</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="service_id" id="addSvcService" class="form-select" style="width:100%" required disabled>
                                <option value="">Choose a department first…</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="addSvcQty" class="form-control" value="1" min="1" max="999">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Note <span class="text-muted small">(optional)</span></label>
                            <input name="notes" class="form-control" placeholder="e.g. reason / special instruction">
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle d-flex justify-content-between align-items-center">
                                <div class="small text-muted" id="addSvcSummary">Select a service to see the charge.</div>
                                <div class="fs-5 fw-bold" id="addSvcTotal">₵0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-receipt me-1"></i>Add &amp; Bill Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.querySelector('[data-auto-filter-form="service-renderings-index"]');
            if (!filterForm) {
                return;
            }

            let searchTimer = null;
            const searchInput = filterForm.querySelector('input[name="search"]');

            filterForm.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    filterForm.requestSubmit();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function () {
                        filterForm.requestSubmit();
                    }, 400);
                });
            }
        });
    </script>
    <script>
        // Add Service to Bill modal — visit + service search with live charge preview.
        document.addEventListener('DOMContentLoaded', function () {
            var $ = window.jQuery;
            var modal = document.getElementById('addServiceModal');
            if (!modal || !$ || !$.fn.select2) return;

            function money(n){ return '₵' + (Math.round(n * 100) / 100).toFixed(2); }
            var svcPrice = 0, svcName = '', visitText = '';

            $('#addSvcVisit').select2({
                dropdownParent: $(modal), width: '100%', placeholder: 'Search visit or patient…', minimumInputLength: 2,
                ajax: {
                    url: '{{ route('admin.service-renderings.visit-search') }}', dataType: 'json', delay: 250,
                    data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d }; }, cache: true
                }
            }).on('select2:select', function (e) { visitText = e.params.data.text || ''; updateSummary(); });

            $('#addSvcService').select2({
                dropdownParent: $(modal), width: '100%', placeholder: 'Search a service…', minimumInputLength: 0,
                ajax: {
                    url: '{{ route('admin.service-renderings.service-search') }}', dataType: 'json', delay: 250,
                    data: function (p) { return { q: p.term, department_id: document.getElementById('addSvcDepartment').value }; },
                    processResults: function (d) { return { results: d }; }, cache: true
                },
                templateResult: function (s) {
                    if (!s.id) return s.text;
                    return $('<span>').html(
                        '<span class="fw-medium">' + s.text + '</span> <span class="text-primary">' + money(s.price || 0) + '</span>'
                    );
                }
            }).on('select2:select', function (e) {
                svcPrice = parseFloat(e.params.data.price || 0); svcName = e.params.data.text || ''; updateSummary();
            });

            // Department-first: enable the service picker only after a department is chosen.
            $('#addSvcDepartment').on('change', function () {
                var hasDept = !!this.value;
                $('#addSvcService').prop('disabled', !hasDept).val(null).trigger('change');
                svcPrice = 0; svcName = ''; updateSummary();
            });

            document.getElementById('addSvcQty').addEventListener('input', updateSummary);

            function updateSummary() {
                var qty = parseInt(document.getElementById('addSvcQty').value || 1, 10);
                document.getElementById('addSvcTotal').textContent = money(svcPrice * qty);
                var el = document.getElementById('addSvcSummary');
                el.innerHTML = svcName
                    ? '<strong>' + svcName + '</strong> &times; ' + qty + (visitText ? ' &rarr; ' + visitText : '')
                    : 'Select a service to see the charge.';
            }
        });
    </script>
@endpush
