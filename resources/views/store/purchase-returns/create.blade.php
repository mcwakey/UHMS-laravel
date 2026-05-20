@extends('layouts.app')
@section('title', 'New Purchase Return')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <h4 class="fw-bold mb-0">New Purchase Return</h4>
    <a href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('admin.store.purchase-returns.store') }}" class="card">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Supplier *</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Purchase Order</label>
                <select name="purchase_order_id" class="form-select">
                    <option value="">Optional</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}" @selected(old('purchase_order_id') == $po->id)>{{ $po->po_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Return From *</label>
                <select name="stock_location_id" class="form-select" required>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('stock_location_id') == $location->id || $location->is_main)>{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Return Date *</label>
                <input type="date" name="return_date" class="form-control" value="{{ old('return_date', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}">
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Items</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px;">Product</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(range(0, 4) as $i)
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm">
                                <option value="">Select product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old("items.$i.product_id") == $product->id)>{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" step="0.0001" min="0.0001" value="{{ old("items.$i.quantity") }}"></td>
                        <td><input type="number" name="items[{{ $i }}][unit_cost]" class="form-control form-control-sm" step="0.01" min="0" value="{{ old("items.$i.unit_cost") }}"></td>
                        <td><input type="text" name="items[{{ $i }}][batch_no]" class="form-control form-control-sm" maxlength="100" value="{{ old("items.$i.batch_no") }}"></td>
                        <td><input type="date" name="items[{{ $i }}][expiry_date]" class="form-control form-control-sm" value="{{ old("items.$i.expiry_date") }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-0">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Return</button>
    </div>
</form>
@endsection
