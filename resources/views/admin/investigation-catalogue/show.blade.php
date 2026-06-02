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
                                    <option value="">— No header —</option>
                                    @foreach($headers as $h)
                                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Input Type</label>
                                <select name="input_type" class="form-select form-select-sm">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="select">Select</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="boolean">Boolean</option>
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
                No products linked to <strong>{{ $service->department->name ?? 'this department' }}</strong>.
                Link consumable/reagent products to this department first under
                <a href="{{ route('admin.products.index') }}">Products</a>.
            </div>
        @else
            <form method="POST" action="{{ route('admin.investigation-catalogue.consumables.store', $service) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-5">
                    <label class="form-label small mb-1">Product *</label>
                    <select name="product_id" class="form-select form-select-sm" required>
                        <option value="">— Select product —</option>
                        @foreach($availableProducts as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}@if($p->unit) ({{ $p->unit }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Default Qty *</label>
                    <input type="number" step="0.0001" min="0.0001" name="default_quantity" value="1" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check small mt-3">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="consReq">
                        <label class="form-check-label" for="consReq">Required</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary btn-sm"><i class="ti ti-plus"></i></button>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Default Qty</th>
                        <th>Required?</th>
                        <th>Notes</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($serviceConsumables ?? [] as $sc)
                    <tr>
                        <td>{{ $sc->product->name ?? '—' }} <small class="text-muted">{{ $sc->product->unit ?? '' }}</small></td>
                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $sc->default_quantity, 4, '.', ''), '0'), '.') }}</td>
                        <td>{!! $sc->is_required ? '<span class="badge bg-danger-subtle text-danger">Required</span>' : '<span class="text-muted small">Optional</span>' !!}</td>
                        <td class="small text-muted">{{ $sc->notes }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.investigation-catalogue.consumables.destroy', [$service, $sc->product_id]) }}" onsubmit="return confirm('Remove consumable?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="ti ti-trash"></i></button>
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
    var app = document.getElementById('catalogue-app');
    if (!app) return;

    var csrf            = app.dataset.csrf;
    var headersStoreUrl = app.dataset.headersStoreUrl;
    var critsStoreUrl   = app.dataset.criteriaStoreUrl;
    var headersBase     = app.dataset.headersBaseUrl;
    var critsBase       = app.dataset.criteriaBaseUrl;

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

    /* ==== HEADERS ==== */
    document.getElementById('addHeaderForm').addEventListener('submit', function(e){
        e.preventDefault();
        var form = e.target;
        ajax(headersStoreUrl, 'POST', new FormData(form))
            .then(function(d){
                var h = d.header;
                var html = '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center" data-header-id="' + h.id + '">'
                    + '<div><div class="fw-medium">' + escapeHtml(h.name) + '</div>'
                    + (h.description ? '<small class="text-muted">' + escapeHtml(h.description) + '</small>' : '')
                    + '</div>'
                    + '<button class="btn btn-xs btn-outline-danger delete-header-btn"><i class="ti ti-trash"></i></button>'
                    + '</div>';
                var list = document.getElementById('headersList');
                var empty = document.getElementById('headersEmpty');
                if (empty) empty.remove();
                list.insertAdjacentHTML('beforeend', html);

                // Add to dropdown in criterion form
                var sel = document.getElementById('addCriterionHeader');
                if (sel) {
                    var opt = document.createElement('option');
                    opt.value = h.id;
                    opt.textContent = h.name;
                    sel.appendChild(opt);
                }

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
                var msg = err && err.errors ? Object.values(err.errors).flat().join('\n') : (err && err.message ? err.message : 'Failed.');
                toast(msg, 'danger');
            });
    });

    document.getElementById('headersList').addEventListener('click', function(e){
        var btn = e.target.closest('.delete-header-btn');
        if (!btn) return;
        if (!confirm('Delete this header? Criteria under it will be moved to "Unsorted".')) return;
        var row = btn.closest('[data-header-id]');
        var id = row.dataset.headerId;
        ajax(headersBase + '/' + id, 'DELETE', new FormData())
            .then(function(){
                row.remove();
                // Remove from dropdown
                var sel = document.getElementById('addCriterionHeader');
                if (sel) {
                    var opt = sel.querySelector('option[value="' + id + '"]');
                    if (opt) opt.remove();
                }
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
                }
                form.reset();
                toast('Criterion added.');
            })
            .catch(function(err){
                var msg = err && err.errors ? Object.values(err.errors).flat().join('\n') : (err && err.message ? err.message : 'Failed.');
                toast(msg, 'danger');
            });
    });

    document.getElementById('criteriaList').addEventListener('click', function(e){
        var btn = e.target.closest('.delete-crit-btn');
        if (!btn) return;
        if (!confirm('Delete this criterion?')) return;
        var row = btn.closest('[data-criterion-id]');
        var id = row.dataset.criterionId;
        ajax(critsBase + '/' + id, 'DELETE', new FormData())
            .then(function(){ row.remove(); toast('Criterion deleted.'); })
            .catch(function(){ toast('Failed to delete.', 'danger'); });
    });

    function renderCriterion(c) {
        var meta = [];
        if (c.unit) meta.push('Unit: ' + escapeHtml(c.unit));
        if (c.reference_range) meta.push('Range: ' + escapeHtml(c.reference_range));
        if (c.input_type) meta.push('Type: ' + escapeHtml(c.input_type));
        if (c.is_required) meta.push('<span class="text-danger">Required</span>');
        return '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start" data-criterion-id="' + c.id + '" data-header-id="' + (c.header_id || '') + '">'
            + '<div class="flex-grow-1"><div class="fw-medium">' + escapeHtml(c.name) + '</div>'
            + '<small class="text-muted">' + meta.join(' &middot; ') + '</small></div>'
            + '<button class="btn btn-xs btn-outline-danger delete-crit-btn ms-2"><i class="ti ti-trash"></i></button>'
            + '</div>';
    }
})();
</script>
@endpush
@endsection
