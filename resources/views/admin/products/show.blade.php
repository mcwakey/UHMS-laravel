@extends('layouts.app')
@section('title', $product->name . ' — Product Detail')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <a href="{{ route('admin.products.index') }}" class="text-muted text-decoration-none small"><i class="ti ti-arrow-left me-1"></i>Products</a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="ti ti-package me-2"></i>{{ $product->name }}
            @if($product->is_active)
                <span class="badge bg-success-subtle text-success fs-xs fw-normal ms-1">Active</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary fs-xs fw-normal ms-1">Inactive</span>
            @endif
        </h4>
        <small class="text-muted">{{ $product->product_type?->label() }}{{ $product->code ? ' · ' . $product->code : '' }}</small>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="productTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-details"><i class="ti ti-info-circle me-1"></i>Details</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-stock"><i class="ti ti-list-check me-1"></i>Stock Balances</a></li>
    @can('product.pricing.manage')
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-pricing"><i class="ti ti-tag me-1"></i>Pricing</a></li>
    @endcan
</ul>

<div class="tab-content">

    {{-- ══════════════════════ TAB: DETAILS ══════════════════════ --}}
    <div class="tab-pane fade show active" id="tab-details">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold py-2 small">Product Information</div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-sm-4 text-muted">Name</dt><dd class="col-sm-8">{{ $product->name }}</dd>
                            <dt class="col-sm-4 text-muted">Code</dt><dd class="col-sm-8">{{ $product->code ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Type</dt><dd class="col-sm-8">{{ $product->product_type?->label() ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Unit</dt><dd class="col-sm-8">{{ $product->unit ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Reorder Level</dt><dd class="col-sm-8">{{ $product->reorder_level ?? '—' }}</dd>
                            <dt class="col-sm-4 text-muted">Cost Price</dt><dd class="col-sm-8">@if($product->default_cost) GH₵ {{ number_format($product->default_cost, 2) }} @else <span class="text-muted">—</span> @endif</dd>
                            <dt class="col-sm-4 text-muted">Description</dt><dd class="col-sm-8">{{ $product->description ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold py-2 small">Billing Settings</div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-sm-5 text-muted">Billable</dt>
                            <dd class="col-sm-7">
                                @if($product->is_billable)
                                    <span class="badge bg-success-subtle text-success">Yes — can appear on invoices</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">No — stock use only</span>
                                @endif
                            </dd>
                            <dt class="col-sm-5 text-muted">Base Price (Cash)</dt>
                            <dd class="col-sm-7">
                                @if($product->base_price !== null)
                                    <strong>GH₵ {{ number_format($product->base_price, 2) }}</strong>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </dd>
                        </dl>
                        @can('product.pricing.manage')
                        <hr class="my-2">
                        <form method="POST" action="{{ route('admin.products.pricing.base.update', $product) }}">
                            @csrf @method('PATCH')
                            <div class="row g-2 align-items-end">
                                <div class="col-7">
                                    <label class="form-label small mb-1">Base Price (GH₵)</label>
                                    <input type="number" step="0.01" min="0" name="base_price" class="form-control form-control-sm"
                                           value="{{ old('base_price', $product->base_price) }}" placeholder="0.00">
                                </div>
                                <div class="col-5">
                                    <div class="form-check mt-3">
                                        <input type="checkbox" class="form-check-input" name="is_billable" value="1" id="is_billable"
                                               @checked(old('is_billable', $product->is_billable))>
                                        <label class="form-check-label small" for="is_billable">Is Billable</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-sm">Update Billing Settings</button>
                                </div>
                            </div>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header fw-semibold py-2 small">Linked Departments</div>
                    <div class="card-body py-2">
                        @forelse($product->departments as $dept)
                            <span class="badge bg-info-subtle text-info me-1 mb-1">{{ $dept->name }}</span>
                        @empty
                            <span class="text-muted small">No departments linked.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════ TAB: STOCK BALANCES ══════════════════════ --}}
    <div class="tab-pane fade" id="tab-stock">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle small">
                        <thead class="table-light">
                            <tr><th>Location</th><th class="text-end">Balance</th><th>Unit</th></tr>
                        </thead>
                        <tbody>
                        @forelse($product->stockBalances as $bal)
                            <tr>
                                <td>{{ $bal->stockLocation?->name ?? '—' }}</td>
                                <td class="text-end @if($bal->quantity <= ($product->reorder_level ?? 0)) text-danger fw-semibold @endif">
                                    {{ number_format($bal->quantity, 2) }}
                                </td>
                                <td>{{ $product->unit ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-empty-state message="No stock records found." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════ TAB: PRICING ══════════════════════ --}}
    @can('product.pricing.manage')
    <div class="tab-pane fade" id="tab-pricing">
        @php
            $typeDefaults   = $product->prices->whereNull('insurance_provider_id')->keyBy('insurance_type');
            $providerPrices = $product->prices->whereNotNull('insurance_provider_id')->values();
            $basePrice      = (float) ($product->base_price ?? 0);
        @endphp

        <form method="POST" action="{{ route('admin.products.pricing.store', $product) }}">
            @csrf

            {{-- Default prices per type --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <span class="fw-semibold"><i class="ti ti-shield-check me-1"></i>Default Prices by Insurance Type</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Applied to all patients with that insurance type (overrides base price).
                        Leave blank to fall back to base price (&#8373;{{ number_format($basePrice, 2) }}).
                    </p>
                    <div class="row g-3">
                        @foreach($insuranceTypes as $type)
                            @php $existing = $typeDefaults[$type->value] ?? null; @endphp
                            <div class="col-md-3">
                                <label class="form-label fw-medium">
                                    <x-status-badge :status="$type" />
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8373;</span>
                                    <input type="number" name="type_prices[{{ $type->value }}]"
                                        class="form-control"
                                        value="{{ $existing ? number_format($existing->price, 2, '.', '') : '' }}"
                                        placeholder="{{ number_format($basePrice, 2) }}"
                                        step="0.01" min="0">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Provider-specific overrides --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="ti ti-building-hospital me-1"></i>Provider-Specific Overrides</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-provider-row"
                            data-target="providerPrices-{{ $product->id }}"
                            data-types='@json(collect($insuranceTypes)->map(fn($t)=>["value"=>$t->value,"label"=>$t->label()]))'
                            data-providers='@json($insuranceProviders->map(fn($p)=>["id"=>$p->id,"name"=>$p->name,"type"=>$p->type]))'>
                        <i class="ti ti-plus me-1"></i>Add Provider Override
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Negotiated rates for specific insurance companies. These override the type default above.
                    </p>
                    <div id="providerPrices-{{ $product->id }}">
                        @foreach($providerPrices as $idx => $pp)
                            <div class="row g-2 align-items-end mb-2 provider-price-row">
                                <div class="col-md-4">
                                    <label class="form-label small">Type</label>
                                    <select name="provider_prices[{{ $idx }}][insurance_type]" class="form-select form-select-sm type-select" required>
                                        @foreach($insuranceTypes as $type)
                                            <option value="{{ $type->value }}" {{ $pp->insurance_type === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Provider</label>
                                    <select name="provider_prices[{{ $idx }}][insurance_provider_id]" class="form-select form-select-sm provider-select" required>
                                        @foreach($insuranceProviders as $prov)
                                            <option value="{{ $prov->id }}" data-type="{{ $prov->type }}" {{ $pp->insurance_provider_id == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Price (&#8373;)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">&#8373;</span>
                                        <input type="number" name="provider_prices[{{ $idx }}][price]" class="form-control" value="{{ number_format($pp->price, 2, '.', '') }}" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end pb-1">
                                    <a href="#"
                                       onclick="event.preventDefault(); if(confirm('Remove this price?')){ document.getElementById('delPrice-{{ $product->id }}-{{ $pp->id }}').submit(); }"
                                       class="btn btn-sm btn-outline-danger" aria-label="Delete" title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Insurance Prices
                </button>
            </div>
        </form>

        {{-- ── Pricing summary ── --}}
        <div class="card bg-light border-0">
            <div class="card-body py-2 small text-muted">
                <i class="ti ti-info-circle me-1"></i>
                <strong>Billing resolution order:</strong>
                Provider-specific price → Insurance type default → Base price (cash fallback).
                <br>
                <strong>Note:</strong> <code>insurance_covered</code> = (base_price − insurance_price) × qty.
                This is displayed for reference only and does not reduce what the patient pays.
            </div>
        </div>

        {{-- Out-of-form delete helpers (a form must not contain another form) --}}
        @foreach($providerPrices as $pp)
            <form id="delPrice-{{ $product->id }}-{{ $pp->id }}" method="POST"
                  action="{{ route('admin.products.pricing.delete', [$product, $pp]) }}" class="d-none">
                @csrf @method('DELETE')
            </form>
        @endforeach
    </div>
    @endcan

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-open Pricing tab if #tab-pricing hash in URL
    const hash = window.location.hash;
    if (hash) {
        const trigger = document.querySelector('[href="' + hash + '"]');
        if (trigger) {
            new bootstrap.Tab(trigger).show();
        }
    }

    // ─── Add Provider Override (services-style) ─────────────────────────
    document.querySelectorAll('.add-provider-row').forEach(function (btn) {
        const targetIdInit  = btn.dataset.target;
        const containerInit = document.getElementById(targetIdInit);
        let rowCounter = containerInit ? containerInit.querySelectorAll('.provider-price-row').length + 1000 : 1000;

        btn.addEventListener('click', function () {
            const targetId  = this.dataset.target;
            const container = document.getElementById(targetId);
            const types     = JSON.parse(this.dataset.types);
            const providers = JSON.parse(this.dataset.providers);
            const idx       = rowCounter++;

            const typeOptions = types.map(t => `<option value="${escH(t.value)}">${escH(t.label)}</option>`).join('');
            const provOptions = providers.map(p => `<option value="${p.id}" data-type="${escH(p.type || '')}">${escH(p.name)}</option>`).join('');

            const row = document.createElement('div');
            row.className = 'row g-2 align-items-end mb-2 provider-price-row';
            row.innerHTML = `
                <div class="col-md-4">
                    <label class="form-label small">Type</label>
                    <select name="provider_prices[${idx}][insurance_type]" class="form-select form-select-sm type-select" required>${typeOptions}</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Provider</label>
                    <select name="provider_prices[${idx}][insurance_provider_id]" class="form-select form-select-sm provider-select" required>${provOptions}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Price (&#8373;)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">&#8373;</span>
                        <input type="number" name="provider_prices[${idx}][price]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end pb-1">
                    <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
                </div>`;
            container.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
            bindCascade(row);
            filterProviders(row);
        });
    });

    // ── Cascade: filter Provider options by selected Insurance Type ──
    function bindCascade(row) {
        const typeSel = row.querySelector('.type-select');
        if (!typeSel || typeSel.dataset.cascadeBound) return;
        typeSel.dataset.cascadeBound = '1';
        typeSel.addEventListener('change', () => filterProviders(row));
    }
    function filterProviders(row) {
        const typeSel = row.querySelector('.type-select');
        const provSel = row.querySelector('.provider-select');
        if (!typeSel || !provSel) return;
        const t = typeSel.value;
        let firstVisible = null;
        let currentStillValid = false;
        Array.from(provSel.options).forEach(opt => {
            const optType = opt.dataset.type || '';
            const match = (optType === '' || optType === t);
            opt.hidden = !match;
            opt.disabled = !match;
            if (match && !firstVisible) firstVisible = opt;
            if (match && opt.selected) currentStillValid = true;
        });
        if (!currentStillValid && firstVisible) {
            provSel.value = firstVisible.value;
        }
    }
    document.querySelectorAll('.provider-price-row').forEach(r => { bindCascade(r); filterProviders(r); });

    function escH(text) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(text)));
        return d.innerHTML;
    }
});
</script>
@endpush
