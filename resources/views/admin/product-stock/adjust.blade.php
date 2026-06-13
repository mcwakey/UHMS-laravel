@extends('layouts.app')

@section('title', __('stock.adjust_stock'))

@section('content')
<div class="container-fluid py-3" style="max-width: 700px;">
    <h4 class="mb-3"><i class="ti ti-adjustments"></i> Adjust Stock</h4>

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.product-stock.adjust') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Location *</label>
                <select name="stock_location_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected(old('stock_location_id') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Product *</label>
                <select name="product_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Adjustment Type *</label>
                    <select name="type" class="form-select" required>
                        @foreach($types as $val => $label)
                            <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Quantity *</label>
                    <input type="number" step="0.0001" min="0" name="quantity" class="form-control" required value="{{ old('quantity') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason *</label>
                <textarea name="reason" class="form-control" rows="3" maxlength="500" required>{{ old('reason') }}</textarea>
            </div>
            @can('stock.override_negative')
                <div class="mb-3">
                    <label class="form-check">
                        <input type="hidden" name="allow_negative" value="0">
                        <input type="checkbox" class="form-check-input" name="allow_negative" value="1">
                        <span class="form-check-label text-warning">Allow negative balance (override)</span>
                    </label>
                </div>
            @endcan
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('admin.product-stock.balances') }}" class="btn btn-link">Cancel</a>
            <button class="btn btn-warning"><i class="ti ti-check"></i> Save Adjustment</button>
        </div>
    </form>
</div>
@endsection
