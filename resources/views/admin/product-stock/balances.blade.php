@extends('layouts.app')

@section('title', __('stock.stock_balances'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ti ti-database"></i> {{ __('stock.stock_balances') }}</h4>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiveStockModal"><i class="ti ti-arrow-down"></i> {{ __('stock.receive') }}</button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transferStockModal"><i class="ti ti-transfer"></i> {{ __('stock.transfer') }}</button>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal"><i class="ti ti-adjustments"></i> {{ __('stock.adjust') }}</button>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#returnStockModal"><i class="ti ti-arrow-back-up"></i> {{ __('stock.return_action') }}</button>
            <a href="{{ route('admin.product-stock.ledger') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-list"></i> {{ __('stock.ledger') }}</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ __('stock.location') }}</label>
                <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">{{ __('stock.all_locations') }}</option>
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected($locationId == $l->id)>
                            {{ $l->name }} @if($l->is_main) {{ __('stock.main_suffix') }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ __('stock.search') }}</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="{{ __('stock.search_name_code') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">{{ __('stock.product_type') }}</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">{{ __('stock.all') }}</option>
                    @foreach($typeOptions as $val => $label)
                        <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100">{{ __('stock.filter') }}</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('stock.product') }}</th>
                        <th>{{ __('stock.type') }}</th>
                        <th>{{ __('stock.unit') }}</th>
                        @foreach($locations as $location)
                            <th class="text-end text-nowrap">{{ $location->name }}</th>
                        @endforeach
                        <th class="text-end">{{ __('stock.total') }}</th>
                        <th>{{ __('stock.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $product = $row['product'];
                        $type = $product?->product_type;
                        $typeLabel = $type instanceof \App\Enums\ProductType ? $type->label() : ucfirst(str_replace('_', ' ', (string) $type));
                        $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.') ?: '0';
                    @endphp
                    <tr>
                        <td>{{ $product->name ?? '—' }}<br><small class="text-muted"><code>{{ $product->code ?? '—' }}</code></small></td>
                        <td><span class="badge bg-secondary">{{ $typeLabel ?: '—' }}</span></td>
                        <td>{{ $product->unit ?? 'unit' }}</td>
                        @foreach($locations as $location)
                            @php $cell = $row['cells'][$location->id]; @endphp
                            <td class="text-end text-nowrap">
                                <strong>{{ $formatQty($cell['quantity']) }}</strong>
                                <span class="badge bg-{{ $cell['status']['class'] }} ms-1">{{ $cell['status']['label'] }}</span>
                            </td>
                        @endforeach
                        <td class="text-end"><strong>{{ $formatQty($row['total']) }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $row['summary_class'] }}">{{ $row['summary'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 5 + $locations->count() }}" class="text-center text-muted py-4">No products found for the current filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $products->links() }}</div>
    </div>
</div>

<div class="modal fade" id="receiveStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.product-stock.receive') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-arrow-down me-1"></i>{{ __('stock.receive_stock') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="stock_location_id" value="{{ $mainStore->id }}">
                <div class="mb-3">
                    <label class="form-label">{{ __('stock.location') }}</label>
                    <input type="text" class="form-control" value="{{ $mainStore->name }} {{ __('stock.main_store_suffix') }}" disabled>
                </div>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label">{{ __('stock.product') }} *</label>
                        <select name="items[0][product_id]" class="form-select" required>
                            <option value="">{{ __('stock.select_product') }}</option>
                            @foreach($stockProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('stock.qty') }} *</label>
                        <input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('stock.unit_cost') }}</label>
                        <input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('stock.batch_no') }}</label>
                        <input type="text" name="items[0][batch_no]" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('stock.expiry_date') }}</label>
                        <input type="date" name="items[0][expiry_date]" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('stock.notes') }}</label>
                        <input type="text" name="notes" class="form-control" maxlength="500">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                <button class="btn btn-success">{{ __('stock.receive') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="transferStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.transfer') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-transfer me-1"></i>{{ __('stock.transfer_stock') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.from') }} *</label>
                        <select name="from_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected($location->is_main)>{{ $location->name }} @if($location->is_main) {{ __('stock.main_suffix') }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.to') }} *</label>
                        <select name="to_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) {{ __('stock.main_suffix') }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">{{ __('stock.product') }} *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">{{ __('stock.select_product') }}</option>
                        @foreach($stockProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.quantity') }} *</label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.notes') }}</label>
                    <textarea name="notes" rows="2" class="form-control" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                <button class="btn btn-primary">{{ __('stock.transfer') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.adjust') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-adjustments me-1"></i>{{ __('stock.adjust_stock') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.location') }} *</label>
                    <select name="stock_location_id" class="form-select" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) {{ __('stock.main_suffix') }} @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.product') }} *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">{{ __('stock.select_product') }}</option>
                        @foreach($stockProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.type') }} *</label>
                        <select name="type" class="form-select" required>
                            @foreach($adjustmentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.quantity') }} *</label>
                        <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">{{ __('stock.reason') }} *</label>
                    <textarea name="reason" rows="2" class="form-control" maxlength="500" required></textarea>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="allow_negative" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('stock.allow_negative_balance') }}</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                <button class="btn btn-warning">{{ __('stock.adjust') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="returnStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.return') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-arrow-back-up me-1"></i>{{ __('stock.return_to_main_store') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.from') }} *</label>
                        <select name="from_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) {{ __('stock.main_suffix') }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('stock.to') }} *</label>
                        <select name="to_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected($mainStore->id === $location->id)>{{ $location->name }} @if($location->is_main) {{ __('stock.main_suffix') }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">{{ __('stock.product') }} *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">{{ __('stock.select_product') }}</option>
                        @foreach($stockProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.quantity') }} *</label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('stock.notes') }}</label>
                    <textarea name="notes" rows="2" class="form-control" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('stock.cancel') }}</button>
                <button class="btn btn-secondary">{{ __('stock.return_action') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
