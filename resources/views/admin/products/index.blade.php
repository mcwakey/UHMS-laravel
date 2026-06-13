@extends('layouts.app')
@section('title', __('products.title'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-package me-2"></i>{{ __('products.title') }}</h4>
        <small class="text-muted">{{ trans_choice('products.count', $products->total(), ['count' => $products->total()]) }}</small>
    </div>
    <div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="ti ti-plus me-1"></i>{{ __('products.add_product') }}
        </button>
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(isset($errors) && $errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('products.search_name_or_code') }}" value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-md-2">
                <select name="product_type" class="form-select form-select-sm">
                    <option value="">{{ __('products.all_types') }}</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(($filters['product_type'] ?? $filters['type'] ?? '') === $t->value)>{{ $t->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">{{ __('common.all_departments') }}</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((string)($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('products.any_status') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('statuses.default.active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('statuses.default.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="has_insurance_prices" class="form-select form-select-sm">
                    <option value="">{{ __('products.any_pricing') }}</option>
                    <option value="1" @selected(($filters['has_insurance_prices'] ?? '') === '1')>{{ __('products.has_insurance_prices') }}</option>
                    <option value="0" @selected(($filters['has_insurance_prices'] ?? '') === '0')>{{ __('products.no_insurance_prices') }}</option>
                </select>
            </div>
            <div class="col-md-1"><button class="btn btn-primary btn-sm w-100" type="submit">{{ __('common.filter') }}</button></div>
            <div class="col-md-2">
                <select name="is_billable" class="form-select form-select-sm">
                    <option value="">{{ __('products.any_billable_state') }}</option>
                    <option value="1" @selected(($filters['is_billable'] ?? '') === '1')>{{ __('products.billable') }}</option>
                    <option value="0" @selected(($filters['is_billable'] ?? '') === '0')>{{ __('products.non_billable') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">{{ __('products.any_supplier_history') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string)($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm w-100">{{ __('common.reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>{{ __('common.name') }}</th><th>{{ __('common.code') }}</th><th>{{ __('common.type') }}</th><th>{{ __('products.unit') }}</th><th>{{ __('common.departments') }}</th><th>{{ __('products.insurance_prices') }}</th><th>{{ __('common.status') }}</th><th class="text-end">{{ __('common.actions') }}</th></tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>@if($product->code)<code>{{ $product->code }}</code>@else <span class="text-muted">—</span> @endif</td>
                        <td><span class="badge bg-light text-dark">{{ $product->product_type?->translatedLabel() ?? '—' }}</span></td>
                        <td>{{ $product->unit ?? '—' }}</td>
                        <td>
                            @foreach($product->departments as $d)
                                <span class="badge bg-info-subtle text-info">{{ $d->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @php
                                $typeCount = (int) ($product->insurance_type_prices_count ?? $product->prices->whereNull('insurance_provider_id')->where('is_active', true)->count());
                                $providerCount = (int) ($product->provider_prices_count ?? $product->prices->whereNotNull('insurance_provider_id')->where('is_active', true)->count());
                            @endphp
                            @if($product->base_price !== null)
                                <span class="badge bg-light text-dark border">{{ __('products.base') }}</span>
                            @endif
                            @if($typeCount > 0)
                                <span class="badge bg-primary-subtle text-primary">{{ trans_choice('products.type_price_count', $typeCount, ['count' => $typeCount]) }}</span>
                            @endif
                            @if($providerCount > 0)
                                <span class="badge bg-purple-subtle text-purple">{{ trans_choice('products.provider_price_count', $providerCount, ['count' => $providerCount]) }}</span>
                            @endif
                            @if($product->base_price === null && $typeCount === 0 && $providerCount === 0)
                                <span class="text-muted">{{ __('common.none') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)<span class="badge bg-success-subtle text-success">{{ __('statuses.default.active') }}</span>
                            @else<span class="badge bg-secondary-subtle text-secondary">{{ __('statuses.default.inactive') }}</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info" title="{{ __('products.view_stock') }}"><i class="ti ti-eye"></i></a>
                            <button class="btn btn-sm btn-soft-info border" title="{{ __('products.insurance_prices') }}"
                                data-bs-toggle="modal" data-bs-target="#productPricesModal-{{ $product->id }}">
                                <i class="ti ti-tag"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal-{{ $product->id }}" aria-label="{{ __('products.edit') }}" title="{{ __('products.edit') }}"><i class="ti ti-edit"></i></button>
                            <form method="POST" action="{{ route('admin.products.toggle', $product) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button aria-label="{{ __('products.power') }}" title="{{ __('products.power') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-power"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state :message="__('products.no_products_found')" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $products->links() }}</div>
</div>

{{-- Edit Modals --}}
@foreach($products as $product)
    @include('admin.products._edit_modal', ['product' => $product, 'departments' => $departments, 'types' => $types])
    @include('admin.products._prices_modal', [
        'product'            => $product,
        'insuranceTypes'     => $insuranceTypes,
        'insuranceProviders' => $insuranceProviders,
    ])
@endforeach

{{-- Add Modal --}}
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.products.store') }}">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('products.add_product') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('admin.products._form_fields', ['product' => null, 'departments' => $departments, 'types' => $types])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('products.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('products.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productI18n = @json([
        'type' => __('products.type'),
        'provider' => __('products.provider'),
        'price' => __('common.price'),
        'delete' => __('products.delete'),
    ]);
    document.querySelectorAll('.add-provider-row').forEach(function (btn) {
        const targetIdInit = btn.dataset.target;
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
                    <label class="form-label small">${escH(productI18n.type)}</label>
                    <select name="provider_prices[${idx}][insurance_type]" class="form-select form-select-sm type-select" required>${typeOptions}</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">${escH(productI18n.provider)}</label>
                    <select name="provider_prices[${idx}][insurance_provider_id]" class="form-select form-select-sm provider-select" required>${provOptions}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">${escH(productI18n.price)} (&#8373;)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">&#8373;</span>
                        <input type="number" name="provider_prices[${idx}][price]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end pb-1">
                    <button aria-label="${escH(productI18n.delete)}" title="${escH(productI18n.delete)}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
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
            // Show providers whose type matches OR which have no type (legacy/global)
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
    // Init for existing server-rendered rows
    document.querySelectorAll('.provider-price-row').forEach(r => { bindCascade(r); filterProviders(r); });

    function escH(text) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(text)));
        return d.innerHTML;
    }
});
</script>
@endpush
