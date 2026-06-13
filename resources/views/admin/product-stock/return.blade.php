@extends('layouts.app')

@section('title', __('stock.return_stock'))

@section('content')
<div class="container-fluid py-3" style="max-width: 700px;">
    <h4 class="mb-3"><i class="ti ti-arrow-back-up"></i> Return Stock to Main Store</h4>

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.product-stock.return') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">From Location *</label>
                    <select name="from_location_id" class="form-select" required>
                        <option value="">— Select —</option>
                        @foreach($locations as $l)
                            @if(!$l->is_main)
                                <option value="{{ $l->id }}" @selected(old('from_location_id') == $l->id)>{{ $l->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">To (usually Main Store) *</label>
                    <select name="to_location_id" class="form-select" required>
                        @foreach($locations as $l)
                            <option value="{{ $l->id }}"
                                @selected(old('to_location_id', $mainStore?->id) == $l->id)>
                                {{ $l->name }} @if($l->is_main) (Main) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Product *</label>
                <select name="product_id" class="form-select" required>
                    <option value="">— Select —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity *</label>
                <input type="number" step="0.0001" min="0" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason / Notes</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="500">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('admin.product-stock.balances') }}" class="btn btn-link">Cancel</a>
            <button class="btn btn-secondary"><i class="ti ti-check"></i> Save Return</button>
        </div>
    </form>
</div>
@endsection
