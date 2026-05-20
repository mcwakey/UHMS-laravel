@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-package me-2"></i>Products</h4>
        <small class="text-muted">{{ $products->total() }} products</small>
    </div>
    <div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="ti ti-plus me-1"></i>Add Product
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
            <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or code" value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-md-2">
                <select name="product_type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(($filters['product_type'] ?? $filters['type'] ?? '') === $t->value)>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((string)($filters['department_id'] ?? '') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="has_insurance_prices" class="form-select form-select-sm">
                    <option value="">Any pricing</option>
                    <option value="1" @selected(($filters['has_insurance_prices'] ?? '') === '1')>Has insurance prices</option>
                    <option value="0" @selected(($filters['has_insurance_prices'] ?? '') === '0')>No insurance prices</option>
                </select>
            </div>
            <div class="col-md-1"><button class="btn btn-primary btn-sm w-100" type="submit">Filter</button></div>
            <div class="col-md-2">
                <select name="is_billable" class="form-select form-select-sm">
                    <option value="">Any billable state</option>
                    <option value="1" @selected(($filters['is_billable'] ?? '') === '1')>Billable</option>
                    <option value="0" @selected(($filters['is_billable'] ?? '') === '0')>Non-billable</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Any supplier history</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string)($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Code</th><th>Type</th><th>Unit</th><th>Departments</th><th>Insurance Prices</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>@if($product->code)<code>{{ $product->code }}</code>@else <span class="text-muted">—</span> @endif</td>
                        <td><span class="badge bg-light text-dark">{{ $product->product_type?->label() ?? '—' }}</span></td>
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
                                <span class="badge bg-light text-dark border">Base</span>
                            @endif
                            @if($typeCount > 0)
                                <span class="badge bg-primary-subtle text-primary">{{ $typeCount }} type</span>
                            @endif
                            @if($providerCount > 0)
                                <span class="badge bg-purple-subtle text-purple">{{ $providerCount }} provider</span>
                            @endif
                            @if($product->base_price === null && $typeCount === 0 && $providerCount === 0)
                                <span class="text-muted">None</span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)<span class="badge bg-success-subtle text-success">Active</span>
                            @else<span class="badge bg-secondary-subtle text-secondary">Inactive</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-info" title="View / Stock"><i class="ti ti-eye"></i></a>
                            <button class="btn btn-sm btn-soft-info border" title="Insurance Prices"
                                data-bs-toggle="modal" data-bs-target="#productPricesModal-{{ $product->id }}">
                                <i class="ti ti-tag"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal-{{ $product->id }}"><i class="ti ti-edit"></i></button>
                            <form method="POST" action="{{ route('admin.products.toggle', $product) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-secondary"><i class="ti ti-power"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
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
            <div class="modal-header"><h5 class="modal-title">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('admin.products._form_fields', ['product' => null, 'departments' => $departments, 'types' => $types])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
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
