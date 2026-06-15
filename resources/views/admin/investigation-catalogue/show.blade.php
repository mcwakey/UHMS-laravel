@extends('layouts.app')

@section('title', 'Configure: ' . $service->name)

@section('content')
<div class="container-fluid py-3" id="catalogue-app"
     data-service-id="{{ $service->id }}"
     data-csrf="{{ csrf_token() }}"
     data-headers-store-url="{{ route('admin.investigation-catalogue.headers.store', $service) }}"
     data-criteria-store-url="{{ route('admin.investigation-catalogue.criteria.store', $service) }}"
     data-headers-base-url="{{ url('admin/investigation-catalogue/headers') }}"
     data-criteria-base-url="{{ url('admin/investigation-catalogue/criteria') }}">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('admin.investigation-catalogue.index') }}" class="text-muted small text-decoration-none"><i class="ti ti-arrow-left me-1"></i>Back to catalogue</a>
            <h4 class="mb-0 mt-1">{{ $service->name }}</h4>
            <small class="text-muted">
                {{ $service->department->name ?? '—' }}
                <span class="badge bg-light text-muted ms-1">{{ ucfirst($service->department->type?->value ?? '') }}</span>
                @if($service->code) &middot; <code>{{ $service->code }}</code> @endif
                &middot; KES {{ number_format((float) $service->price, 2) }}
            </small>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="ti ti-check me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="ti ti-alert-circle me-1"></i>{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- OVERALL RESULT TYPE CONFIGURATION --}}
    @php $ort = $service->overallResultType(); @endphp
    <div class="card mb-3" id="overallResultCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="ti ti-adjustments-check me-1"></i>{{ __('investigations.overall_result_type') }}</h6>
            <span class="badge bg-light text-muted">{{ $service->overallResultTypeLabel() }}</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">{{ __('investigations.overall_result_help') }}</p>
            <form method="POST" action="{{ route('admin.investigation-catalogue.overall-result.update', $service) }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-4">
                    <label class="form-label small mb-1">{{ __('investigations.overall_result_type') }} <span class="text-danger">*</span></label>
                    <select name="overall_result_type" id="overallResultType" class="form-select form-select-sm">
                        @foreach(\App\Models\ServiceCatalog::overallResultTypes() as $value => $labelKey)
                            <option value="{{ $value }}" @selected($ort === $value)>{{ __($labelKey) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Numeric options --}}
                <div class="col-md-8 ort-group" data-ort="numeric">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('investigations.overall_result_unit') }}</label>
                            <input type="text" name="overall_result_unit" class="form-control form-control-sm" value="{{ old('overall_result_unit', $service->overall_result_unit) }}" placeholder="mmol/L">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('investigations.minimum_normal_value') }}</label>
                            <input type="number" step="any" name="overall_result_min_value" class="form-control form-control-sm" value="{{ old('overall_result_min_value', $service->overall_result_min_value) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">{{ __('investigations.maximum_normal_value') }}</label>
                            <input type="number" step="any" name="overall_result_max_value" class="form-control form-control-sm" value="{{ old('overall_result_max_value', $service->overall_result_max_value) }}">
                        </div>
                    </div>
                </div>

                {{-- Positive / Negative labels --}}
                <div class="col-md-8 ort-group" data-ort="positive_negative">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('investigations.positive_label') }}</label>
                            <input type="text" name="overall_result_positive_label" class="form-control form-control-sm" value="{{ old('overall_result_positive_label', $service->overall_result_positive_label) }}" placeholder="{{ __('investigations.positive') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('investigations.negative_label') }}</label>
                            <input type="text" name="overall_result_negative_label" class="form-control form-control-sm" value="{{ old('overall_result_negative_label', $service->overall_result_negative_label) }}" placeholder="{{ __('investigations.negative') }}">
                        </div>
                    </div>
                </div>

                {{-- True / False labels --}}
                <div class="col-md-8 ort-group" data-ort="boolean">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('investigations.true_label') }}</label>
                            <input type="text" name="overall_result_true_label" class="form-control form-control-sm" value="{{ old('overall_result_true_label', $service->overall_result_true_label) }}" placeholder="{{ __('investigations.true') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">{{ __('investigations.false_label') }}</label>
                            <input type="text" name="overall_result_false_label" class="form-control form-control-sm" value="{{ old('overall_result_false_label', $service->overall_result_false_label) }}" placeholder="{{ __('investigations.false') }}">
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>{{ __('investigations.save_overall_result') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        {{-- LEFT: HEADERS --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="ti ti-list-tree me-1"></i>Headers / Categories</h6>
                </div>
                <div class="card-body">
                    <form id="addHeaderForm" class="border rounded p-2 bg-light mb-3">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Header Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Blood Counts">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Optional...">
                        </div>
                        <button class="btn btn-primary btn-sm w-100"><i class="ti ti-plus me-1"></i>Add Header</button>
                    </form>

                    <div id="headersList">
                        @forelse($headers as $h)
                            @include('admin.investigation-catalogue._header', ['h' => $h])
                        @empty
                            <div class="text-center text-muted py-3" id="headersEmpty">
                                <i class="ti ti-list fs-2 d-block mb-2"></i>No headers yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: CRITERIA --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="ti ti-list-check me-1"></i>Result Criteria</h6>
                </div>
                <div class="card-body">
                    <form id="addCriterionForm" class="border rounded p-2 bg-light mb-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Hemoglobin">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Unit</label>
                                <input type="text" name="unit" class="form-control form-control-sm" placeholder="g/dL">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Reference Range</label>
                                <input type="text" name="reference_range" class="form-control form-control-sm" placeholder="13.5–17.5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Header</label>
                                <select name="header_id" class="form-select form-select-sm" id="addCriterionHeader">
                                    <option value="">{{ __('investigations.no_header') }}</option>
                                    @foreach($headers as $h)
                                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">{{ __('investigations.input_type') }}</label>
                                <select name="input_type" class="form-select form-select-sm">
                                    <option value="text">{{ __('investigations.input_text') }}</option>
                                    <option value="number">{{ __('investigations.input_number') }}</option>
                                    <option value="select">{{ __('investigations.input_select') }}</option>
                                    <option value="textarea">{{ __('investigations.input_textarea') }}</option>
                                    <option value="boolean">{{ __('investigations.input_boolean') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Default Value</label>
                                <input type="text" name="default_value" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Options (comma separated, for Select)</label>
                                <input type="text" name="options" class="form-control form-control-sm" placeholder="Positive, Negative, Inconclusive">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <div class="form-check small">
                                    <input class="form-check-input" type="checkbox" name="is_required" value="1" id="critReq">
                                    <label class="form-check-label" for="critReq">Required</label>
                                </div>
                                <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-plus me-1"></i>Add</button>
                            </div>
                        </div>
                    </form>

                    <div id="criteriaList">
                        {{-- Group: criteria under each header --}}
                        @foreach($headers as $h)
                            <div class="mb-3 criteria-group" data-header-id="{{ $h->id }}">
                                <h6 class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">{{ $h->name }}</h6>
                                @forelse($h->criteria as $c)
                                    @include('admin.investigation-catalogue._criterion', ['c' => $c])
                                @empty
                                    <div class="text-muted small ps-2">No criteria in this header.</div>
                                @endforelse
                            </div>
                        @endforeach

                        <div class="mb-3 criteria-group" data-header-id="">
                            <h6 class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">— Unsorted —</h6>
                            @forelse($unsorted_criteria as $c)
                                @include('admin.investigation-catalogue._criterion', ['c' => $c])
                            @empty
                                <div class="text-muted small ps-2" id="unsortedEmpty">No unsorted criteria.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Header editor --}}
<div class="modal fade" id="editHeaderModal" tabindex="-1" aria-labelledby="editHeaderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editHeaderForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHeaderModalLabel"><i class="ti ti-edit me-1"></i>Edit Header</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">Header Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="191">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" min="0">
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editHeaderActive">
                                <label class="form-check-label" for="editHeaderActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Criterion editor --}}
<div class="modal fade" id="editCriterionModal" tabindex="-1" aria-labelledby="editCriterionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCriterionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCriterionModalLabel"><i class="ti ti-edit me-1"></i>Edit Criterion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="191">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference Range</label>
                            <input type="text" name="reference_range" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Header</label>
                            <select name="header_id" class="form-select" id="editCriterionHeader">
                                <option value="">{{ __('investigations.no_header') }}</option>
                                @foreach($headers as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('investigations.input_type') }}</label>
                            <select name="input_type" class="form-select">
                                <option value="text">{{ __('investigations.input_text') }}</option>
                                <option value="number">{{ __('investigations.input_number') }}</option>
                                <option value="select">{{ __('investigations.input_select') }}</option>
                                <option value="textarea">{{ __('investigations.input_textarea') }}</option>
                                <option value="boolean">{{ __('investigations.input_boolean') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Value</label>
                            <input type="text" name="default_value" class="form-control" maxlength="191">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Options <small class="text-muted">(comma separated)</small></label>
                            <input type="text" name="options" class="form-control" placeholder="Positive, Negative, Inconclusive">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" min="0">
                        </div>
                        <div class="col-12 d-flex gap-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_required" value="1" class="form-check-input" id="editCriterionRequired">
                                <label class="form-check-label" for="editCriterionRequired">Required</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editCriterionActive">
                                <label class="form-check-label" for="editCriterionActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Default Consumables --}}
@can('service_consumable.manage')
<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-package me-1"></i> Default Consumables</h5>
        <small class="text-muted">Pre-loaded during result entry; actual usage deducts stock.</small>
    </div>
    <div class="card-body">
        @if(($availableProducts ?? collect())->isEmpty())
            <div class="alert alert-warning small">
                {!! __('investigations.no_products_alert', [
                    'dept' => '<strong>'.e($service->department->name ?? __('investigations.this_department')).'</strong>',
                    'link' => '<a href="'.route('admin.products.index').'">'.e(__('investigations.products')).'</a>',
                ]) !!}
            </div>
        @else
            <form method="POST" action="{{ route('admin.investigation-catalogue.consumables.store', $service) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label small mb-1">{{ __('stock.product') }} *</label>
                    <select name="product_id" class="form-select form-select-sm" required>
                        <option value="">{{ __('investigations.select_product') }}</option>
                        @foreach($availableProducts as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}@if($p->unit) ({{ $p->unit }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('investigations.default_qty') }} *</label>
                    <input type="number" step="0.0001" min="0.0001" name="default_quantity" value="1" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check small mt-3">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="consReq">
                        <label class="form-check-label" for="consReq">{{ __('common.required') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('common.notes') }}">
                </div>
                <div class="col-md-1 d-grid">
                    <button aria-label="{{ __('common.add') }}" title="{{ __('common.add') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i></button>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('stock.product') }}</th>
                        <th class="text-end">{{ __('investigations.default_qty') }}</th>
                        <th>{{ __('investigations.required_q') }}</th>
                        <th>{{ __('common.notes') }}</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($serviceConsumables ?? [] as $sc)
                    <tr>
                        <td>{{ $sc->product->name ?? '—' }} <small class="text-muted">{{ $sc->product->unit ?? '' }}</small></td>
                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $sc->default_quantity, 4, '.', ''), '0'), '.') }}</td>
                        <td>{!! $sc->is_required ? '<span class="badge bg-danger-subtle text-danger">'.e(__('common.required')).'</span>' : '<span class="text-muted small">'.e(__('common.optional')).'</span>' !!}</td>
                        <td class="small text-muted">{{ $sc->notes }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.investigation-catalogue.consumables.destroy', [$service, $sc->product_id]) }}" onsubmit="return confirm('{{ __('investigations.remove_consumable_confirm') }}')">
                                @csrf @method('DELETE')
                                <button aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state message="No default consumables configured." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
(function() {
    /* ==== OVERALL RESULT TYPE: show only the fields for the selected type ==== */
    var ortSelect = document.getElementById('overallResultType');
    if (ortSelect) {
        var ortGroups = document.querySelectorAll('#overallResultCard .ort-group');
        var syncOrt = function() {
            var val = ortSelect.value;
            ortGroups.forEach(function(g) {
                g.style.display = (g.getAttribute('data-ort') === val) ? '' : 'none';
            });
        };
        ortSelect.addEventListener('change', syncOrt);
        syncOrt();
    }

    var app = document.getElementById('catalogue-app');
    if (!app) return;

    var csrf            = app.dataset.csrf;
    var headersStoreUrl = app.dataset.headersStoreUrl;
    var critsStoreUrl   = app.dataset.criteriaStoreUrl;
    var headersBase     = app.dataset.headersBaseUrl;
    var critsBase       = app.dataset.criteriaBaseUrl;
    var editHeaderModal = new bootstrap.Modal(document.getElementById('editHeaderModal'));
    var editCritModal   = new bootstrap.Modal(document.getElementById('editCriterionModal'));

    function ajax(url, method, body) {
        var fd = body instanceof FormData ? body : null;
        var opts = {
            method: method,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        };
        if (fd) {
            fd.append('_token', csrf);
            if (method !== 'POST') fd.append('_method', method);
            opts.method = 'POST';
            opts.body = fd;
        }
        return fetch(url, opts).then(function(r){ return r.json().then(function(d){ if (!r.ok) throw d; return d; }); });
    }

    function toast(msg, type) {
        var t = document.createElement('div');
        t.className = 'alert alert-' + (type || 'success') + ' position-fixed bottom-0 end-0 m-3 shadow';
        t.style.cssText = 'z-index:9999;max-width:300px;font-size:.85rem;';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function(){ t.remove(); }, 2500);
    }

    function escapeHtml(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(s)));
        return d.innerHTML;
    }

    function errorMessage(err) {
        return err && err.errors
            ? Object.values(err.errors).flat().join('\n')
            : (err && err.message ? err.message : 'Failed.');
    }

    function renderHeader(h) {
        return '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center" data-header-id="' + h.id + '">'
            + '<div><div class="fw-medium header-name"></div><small class="text-muted header-description"></small></div>'
            + '<div class="d-flex gap-1 ms-2">'
            + '<button type="button" aria-label="Edit" title="Edit" class="btn btn-xs btn-outline-primary edit-header-btn"><i class="ti ti-edit"></i></button>'
            + '<button type="button" aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger delete-header-btn"><i class="ti ti-trash"></i></button>'
            + '</div></div>';
    }

    function syncHeaderRow(row, h) {
        row.dataset.name = h.name || '';
        row.dataset.description = h.description || '';
        row.dataset.sortOrder = h.sort_order == null ? '' : h.sort_order;
        row.dataset.isActive = h.is_active ? '1' : '0';
        row.classList.toggle('opacity-50', !h.is_active);
        row.querySelector('.header-name').textContent = h.name || '';
        row.querySelector('.header-description').textContent = h.description || '';
        row.querySelector('.header-description').classList.toggle('d-none', !h.description);
    }

    /* ==== HEADERS ==== */
    document.getElementById('addHeaderForm').addEventListener('submit', function(e){
        e.preventDefault();
        var form = e.target;
        ajax(headersStoreUrl, 'POST', new FormData(form))
            .then(function(d){
                var h = d.header;
                var list = document.getElementById('headersList');
                var empty = document.getElementById('headersEmpty');
                if (empty) empty.remove();
                list.insertAdjacentHTML('beforeend', renderHeader(h));
                syncHeaderRow(list.lastElementChild, h);

                // Add to dropdown in criterion form
                [document.getElementById('addCriterionHeader'), document.getElementById('editCriterionHeader')].forEach(function(sel) {
                    if (!sel) return;
                    var opt = document.createElement('option');
                    opt.value = h.id;
                    opt.textContent = h.name;
                    sel.appendChild(opt);
                });

                // Add new empty criteria-group section
                var critList = document.getElementById('criteriaList');
                var grpHtml = '<div class="mb-3 criteria-group" data-header-id="' + h.id + '">'
                    + '<h6 class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">' + escapeHtml(h.name) + '</h6>'
                    + '<div class="text-muted small ps-2">No criteria in this header.</div>'
                    + '</div>';
                critList.insertAdjacentHTML('afterbegin', grpHtml);

                form.reset();
                toast('Header added.');
            })
            .catch(function(err){
                toast(errorMessage(err), 'danger');
            });
    });

    document.getElementById('headersList').addEventListener('click', function(e){
        var editBtn = e.target.closest('.edit-header-btn');
        if (editBtn) {
            var editRow = editBtn.closest('[data-header-id]');
            var editForm = document.getElementById('editHeaderForm');
            editForm.elements.id.value = editRow.dataset.headerId;
            editForm.elements.name.value = editRow.dataset.name || '';
            editForm.elements.description.value = editRow.dataset.description || '';
            editForm.elements.sort_order.value = editRow.dataset.sortOrder || '';
            editForm.elements.is_active.checked = editRow.dataset.isActive === '1';
            editHeaderModal.show();
            return;
        }

        var btn = e.target.closest('.delete-header-btn');
        if (!btn) return;
        if (!confirm('Delete this header? Criteria under it will be moved to "Unsorted".')) return;
        var row = btn.closest('[data-header-id]');
        var id = row.dataset.headerId;
        ajax(headersBase + '/' + id, 'DELETE', new FormData())
            .then(function(){
                row.remove();
                // Remove from dropdown
                [document.getElementById('addCriterionHeader'), document.getElementById('editCriterionHeader')].forEach(function(sel) {
                    if (!sel) return;
                    var opt = sel.querySelector('option[value="' + id + '"]');
                    if (opt) opt.remove();
                });
                // Move criteria from this group into Unsorted
                var grp = document.querySelector('.criteria-group[data-header-id="' + id + '"]');
                if (grp) {
                    var unsortedGrp = document.querySelector('.criteria-group[data-header-id=""]');
                    grp.querySelectorAll('[data-criterion-id]').forEach(function(c){
                        c.dataset.headerId = '';
                        if (unsortedGrp) unsortedGrp.appendChild(c);
                    });
                    grp.remove();
                }
                toast('Header deleted.');
            })
            .catch(function(){ toast('Failed to delete.', 'danger'); });
    });

    document.getElementById('editHeaderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        var id = form.elements.id.value;
        var fd = new FormData(form);
        fd.delete('id');
        fd.set('is_active', form.elements.is_active.checked ? '1' : '0');

        ajax(headersBase + '/' + id, 'PUT', fd)
            .then(function(d) {
                var row = document.querySelector('#headersList [data-header-id="' + id + '"]');
                syncHeaderRow(row, d.header);

                [document.getElementById('addCriterionHeader'), document.getElementById('editCriterionHeader')].forEach(function(sel) {
                    if (!sel) return;
                    var opt = sel.querySelector('option[value="' + id + '"]');
                    if (opt) opt.textContent = d.header.name;
                });

                var groupTitle = document.querySelector('.criteria-group[data-header-id="' + id + '"] h6');
                if (groupTitle) groupTitle.textContent = d.header.name;
                editHeaderModal.hide();
                toast('Header updated.');
            })
            .catch(function(err) { toast(errorMessage(err), 'danger'); });
    });

    /* ==== CRITERIA ==== */
    document.getElementById('addCriterionForm').addEventListener('submit', function(e){
        e.preventDefault();
        var form = e.target;
        var fd = new FormData(form);
        // Convert "options" CSV to array entries
        var optsRaw = fd.get('options') || '';
        fd.delete('options');
        if (optsRaw.trim()) {
            optsRaw.split(',').forEach(function(opt, i){
                var v = opt.trim();
                if (v) fd.append('options[' + i + ']', v);
            });
        }
        ajax(critsStoreUrl, 'POST', fd)
            .then(function(d){
                var c = d.criterion;
                var html = renderCriterion(c);
                var grp = document.querySelector('.criteria-group[data-header-id="' + (c.header_id || '') + '"]');
                if (grp) {
                    var ph = grp.querySelector('.text-muted.small.ps-2');
                    if (ph) ph.remove();
                    grp.insertAdjacentHTML('beforeend', html);
                    syncCriterionRow(grp.lastElementChild, c);
                }
                form.reset();
                toast('Criterion added.');
            })
            .catch(function(err){
                toast(errorMessage(err), 'danger');
            });
    });

    document.getElementById('criteriaList').addEventListener('click', function(e){
        var editBtn = e.target.closest('.edit-crit-btn');
        if (editBtn) {
            var editRow = editBtn.closest('[data-criterion-id]');
            var editForm = document.getElementById('editCriterionForm');
            var options = [];
            try {
                options = JSON.parse(editRow.dataset.options || '[]');
            } catch (ignore) {}

            editForm.elements.id.value = editRow.dataset.criterionId;
            editForm.elements.name.value = editRow.dataset.name || '';
            editForm.elements.unit.value = editRow.dataset.unit || '';
            editForm.elements.reference_range.value = editRow.dataset.referenceRange || '';
            editForm.elements.default_value.value = editRow.dataset.defaultValue || '';
            editForm.elements.header_id.value = editRow.dataset.headerId || '';
            editForm.elements.input_type.value = editRow.dataset.inputType || 'text';
            editForm.elements.options.value = options.join(', ');
            editForm.elements.sort_order.value = editRow.dataset.sortOrder || '';
            editForm.elements.is_required.checked = editRow.dataset.isRequired === '1';
            editForm.elements.is_active.checked = editRow.dataset.isActive === '1';
            editCritModal.show();
            return;
        }

        var btn = e.target.closest('.delete-crit-btn');
        if (!btn) return;
        if (!confirm(@json(__('investigations.delete_criterion_confirm')))) return;
        var row = btn.closest('[data-criterion-id]');
        var id = row.dataset.criterionId;
        ajax(critsBase + '/' + id, 'DELETE', new FormData())
            .then(function(){ row.remove(); toast('Criterion deleted.'); })
            .catch(function(){ toast('Failed to delete.', 'danger'); });
    });

    document.getElementById('editCriterionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        var id = form.elements.id.value;
        var fd = new FormData(form);
        var options = fd.get('options') || '';
        fd.delete('id');
        fd.delete('options');
        options.split(',').map(function(value) { return value.trim(); }).filter(Boolean).forEach(function(value, index) {
            fd.append('options[' + index + ']', value);
        });
        if (!options.trim()) fd.append('options', '');
        fd.set('is_required', form.elements.is_required.checked ? '1' : '0');
        fd.set('is_active', form.elements.is_active.checked ? '1' : '0');

        ajax(critsBase + '/' + id, 'PUT', fd)
            .then(function(d) {
                var row = document.querySelector('[data-criterion-id="' + id + '"]');
                var targetGroup = document.querySelector('.criteria-group[data-header-id="' + (d.criterion.header_id || '') + '"]');

                if (targetGroup && row.closest('.criteria-group') !== targetGroup) {
                    var placeholder = targetGroup.querySelector('.text-muted.small.ps-2');
                    if (placeholder) placeholder.remove();
                    targetGroup.appendChild(row);
                }

                syncCriterionRow(row, d.criterion);
                editCritModal.hide();
                toast('Criterion updated.');
            })
            .catch(function(err) { toast(errorMessage(err), 'danger'); });
    });

    function renderCriterion(c) {
        return '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start" data-criterion-id="' + c.id + '" data-header-id="' + (c.header_id || '') + '">'
            + '<div class="flex-grow-1"><div class="fw-medium criterion-name"></div><small class="text-muted criterion-meta"></small></div>'
            + '<div class="d-flex gap-1 ms-2">'
            + '<button type="button" aria-label="Edit" title="Edit" class="btn btn-xs btn-outline-primary edit-crit-btn"><i class="ti ti-edit"></i></button>'
            + '<button type="button" aria-label="Delete" title="Delete" class="btn btn-xs btn-outline-danger delete-crit-btn"><i class="ti ti-trash"></i></button>'
            + '</div>'
            + '</div>';
    }

    function syncCriterionRow(row, c) {
        var options = Array.isArray(c.options) ? c.options : [];
        var meta = [];
        if (c.unit) meta.push('Unit: ' + c.unit);
        if (c.reference_range) meta.push('Range: ' + c.reference_range);
        if (c.input_type) meta.push('Type: ' + c.input_type);
        if (c.is_required) meta.push('Required');

        row.dataset.headerId = c.header_id || '';
        row.dataset.name = c.name || '';
        row.dataset.unit = c.unit || '';
        row.dataset.referenceRange = c.reference_range || '';
        row.dataset.defaultValue = c.default_value || '';
        row.dataset.inputType = c.input_type || 'text';
        row.dataset.options = JSON.stringify(options);
        row.dataset.sortOrder = c.sort_order == null ? '' : c.sort_order;
        row.dataset.isRequired = c.is_required ? '1' : '0';
        row.dataset.isActive = c.is_active ? '1' : '0';
        row.classList.toggle('opacity-50', !c.is_active);
        row.querySelector('.criterion-name').textContent = c.name || '';
        row.querySelector('.criterion-meta').textContent = meta.join(' · ');
        if (c.is_required) row.querySelector('.criterion-meta').classList.add('text-danger');
        else row.querySelector('.criterion-meta').classList.remove('text-danger');
    }
})();
</script>
@endpush
@endsection
