@extends('layouts.app')
@section('title', __('services.renderings_title'))

@section('content')
<x-page-header :title="__('services.renderings_title')" icon="ti-clipboard-check" >
    <x-slot:actions>
        @can('invoices.create')
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="ti ti-plus me-1"></i>{{ __('services.add_service_to_bill') }}
            </button>
        @endcan
        <!-- @can('service_rendering.reports')
            <a href="{{ $workspaceRoutes->route('admin.service-renderings.reports', request()->query()) }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-report-analytics me-1"></i>{{ __('services.reports') }}
            </a>
        @endcan -->
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

<div class="row g-3 mb-1">
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1">{{ $summary['total'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.total') }}</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-warning">{{ $summary['pending'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.pending') }}</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-info">{{ $summary['in_progress'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.in_progress') }}</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-success">{{ $summary['rendered'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.rendered') }}</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-danger">{{ $summary['not_rendered'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.not_rendered') }}</p></div></div></div>
    <div class="col-6 col-xl-2"><div class="card"><div class="card-body text-center"><h3 class="mb-1 text-secondary">{{ $summary['cancelled'] ?? 0 }}</h3><p class="text-muted mb-0">{{ __('services.cancelled') }}</p></div></div></div>
</div>

<x-filter-bar
    :action="$workspaceRoutes->route('admin.service-renderings.index')"
    :reset-url="$workspaceRoutes->route('admin.service-renderings.index')"
    ajax
    ajax-target="#serviceRenderingsIndexResults"
>
    <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $renderings->perPage() }}" data-filter-per-page-input>

    <div class="col-md-3">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('services.rendering_search_placeholder') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('common.status') }}</label>
        <select class="form-select" name="status">
            <option value="">{{ __('common.all') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __("statuses.default.$status") }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('common.department') }}</label>
        <select class="form-select" name="department_id">
            <option value="">{{ __('common.all') }}</option>
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
    <x-slot:actions>
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" class="btn btn-outline-secondary btn-icon" href="{{ $workspaceRoutes->route('admin.service-renderings.index') }}" data-filter-reset>
            <i class="ti ti-x"></i>
        </a>
    </x-slot:actions>
</x-filter-bar>

<div id="serviceRenderingsIndexResults">
<x-data-table
    id="serviceRenderingsDataTable"
    :paginator="$renderings"
    show-summary
    show-per-page
    :current-per-page="$filters['per_page'] ?? $renderings->perPage()"
    :per-page-options="[10, 15, 25, 50, 100]"
>
    <x-slot:head>
        <tr>
            <th>{{ __('services.patient_visit') }}</th>
            <th>{{ __('services.service_name') }}</th>
            <th>{{ __('common.department') }}</th>
            <th>{{ __('services.invoice') }}</th>
            <th>{{ __('services.payment') }}</th>
            <th>{{ __('services.rendering') }}</th>
            <th>{{ __('services.rendered_by') }}</th>
            <th class="text-end">{{ __('common.actions') }}</th>
        </tr>
    </x-slot:head>

    @forelse($renderings as $rendering)
        <tr>
            <td>
                <div class="fw-semibold">{{ $rendering->patient?->full_name ?? __('services.unknown_patient') }}</div>
                <small class="text-muted">{{ $rendering->patient?->patient_number }} @if($rendering->visit) / {{ $rendering->visit->visit_number }} @endif</small>
                @if($rendering->emergencyCase)
                    <div><span class="badge bg-danger-subtle text-danger">{{ $rendering->emergencyCase->emergency_number }}</span></div>
                @elseif($rendering->admission)
                    <div><span class="badge bg-primary-subtle text-primary">{{ $rendering->admission->admission_number }}</span></div>
                @endif
            </td>
            <td>
                <div class="fw-semibold">{{ $rendering->service?->name ?? __('services.service_name') }}</div>
                <small class="text-muted">{{ $rendering->invoiceItem?->description }}</small>
            </td>
            <td>{{ $rendering->department?->name ?? __('services.unassigned') }}</td>
            <td>
                <div>{{ $rendering->invoiceItem?->invoice?->invoice_number ?? __('services.no_invoice') }}</div>
                <small class="text-muted">{{ __('services.invoice_item', ['id' => $rendering->invoice_item_id]) }}</small>
            </td>
            <td>
                @php $payment = $rendering->invoiceItem?->payment_status ?? 'unpaid'; @endphp
                <span class="badge bg-{{ $payment === 'paid' ? 'success' : ($payment === 'partially_paid' ? 'warning text-dark' : 'danger') }}">{{ __("statuses.default.$payment") }}</span>
            </td>
            <td>
                <span class="badge bg-{{ $rendering->status_color }}">{{ __("statuses.default.$rendering->status") }}</span>
                <div class="small text-muted">{{ $rendering->created_at?->format('d M Y H:i') }}</div>
            </td>
            <td>
                <div>{{ $rendering->renderedBy?->full_name ?? $rendering->renderedBy?->name ?? __('services.not_rendered_by_anyone') }}</div>
                <small class="text-muted">{{ $rendering->rendered_at?->format('d M Y H:i') }}</small>
            </td>
            <td class="text-end">
                <div class="d-flex flex-wrap justify-content-end gap-1">
                    <a href="{{ $workspaceRoutes->route('admin.service-renderings.show', $rendering) }}" class="btn btn-sm btn-outline-primary">{{ __('services.open') }}</a>
                    @can('service_rendering.start')
                        @if($rendering->can_be_started)
                            <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.start', $rendering) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-info">{{ __('services.start') }}</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="8"><x-empty-state :message="__('services.no_renderings_found')" /></td></tr>
    @endforelse
</x-data-table>
</div>

@can('invoices.create')
{{-- Add Service to Bill --}}
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $workspaceRoutes->route('admin.service-renderings.store') }}" id="addServiceForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0"><i class="ti ti-clipboard-plus me-2 text-primary"></i>{{ __('services.add_service_to_bill') }}</h5>
                        <small class="text-muted">Bill a service to a patient's visit — a rendering task is created for the department automatically.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('services.patient_visit') }} <span class="text-danger">*</span></label>
                            <select name="visit_id" id="addSvcVisit" class="form-select" style="width:100%" required>
                                <option value="">{{ __('services.search_visit_or_patient') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('common.department') }} <span class="text-danger">*</span></label>
                            <select id="addSvcDepartment" class="form-select" required>
                                <option value="">{{ __('services.choose_department') }}</option>
                                @foreach($renderableDepartments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @if($renderableDepartments->isEmpty())
                                <div class="form-text text-danger">{{ __('services.no_renderable_departments') }}</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('services.service_name') }} <span class="text-danger">*</span></label>
                            <select name="service_id" id="addSvcService" class="form-select" style="width:100%" required disabled>
                                <option value="">{{ __('services.choose_department_first') }}</option>
                            </select>
                        </div>
                        <!-- <div class="col-md-4">
                            <label class="form-label">{{ __('services.quantity') }}</label>
                        </div> -->
                        <div class="col-md-12">
                            <label class="form-label">{{ __('services.note_optional') }}</label>
                            <input type="hidden" name="quantity" id="addSvcQty" class="form-control" value="1">
                            <input name="notes" class="form-control" placeholder="{{ __('services.note_placeholder') }}">
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle d-flex justify-content-between align-items-center">
                                <div class="small text-muted" id="addSvcSummary">{{ __('services.select_service_charge') }}</div>
                                <div class="fs-5 fw-bold" id="addSvcTotal">₵0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-receipt me-1"></i>{{ __('services.add_bill_service') }}</button>
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
                    url: '{{ $workspaceRoutes->route('admin.service-renderings.visit-search') }}', dataType: 'json', delay: 250,
                    data: function (p) { return { q: p.term }; }, processResults: function (d) { return { results: d }; }, cache: true
                }
            }).on('select2:select', function (e) { visitText = e.params.data.text || ''; updateSummary(); });

            $('#addSvcService').select2({
                dropdownParent: $(modal), width: '100%', placeholder: 'Search a service…', minimumInputLength: 0,
                ajax: {
                    url: '{{ $workspaceRoutes->route('admin.service-renderings.service-search') }}', dataType: 'json', delay: 250,
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
