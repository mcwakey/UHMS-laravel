@extends('layouts.app')
@section('title', 'Stock Return')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Stock Return</h4>
        <small class="text-muted">Record items returned to stock (patient/ward return), or returned out to supplier.</small>
    </div>
    <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card"><div class="card-body">
<form method="POST" action="{{ route('admin.store.stock.returns.store') }}">@csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Drug <span class="text-danger">*</span></label>
            <select name="drug_id" class="form-select select2" required>
                <option value="">Select drug...</option>
                @foreach($drugs as $d)
                <option value="{{ $d->id }}" {{ old('drug_id') == $d->id ? 'selected' : '' }}>{{ $d->name }} ({{ $d->unit }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Location <span class="text-danger">*</span></label>
            <select name="stock_location_id" class="form-select" required>
                <option value="">Select location...</option>
                @foreach($locations as $l)
                <option value="{{ $l->id }}" {{ old('stock_location_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Return Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select" required>
                <option value="in"  {{ old('type')==='in'  ? 'selected' : '' }}>Return IN (returned to us)</option>
                <option value="out" {{ old('type')==='out' ? 'selected' : '' }}>Return OUT (to supplier)</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Batch No</label>
            <input type="text" name="batch_no" class="form-control" value="{{ old('batch_no') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Reason <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g. Patient returned unused..." required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Unit Cost (optional)</label>
            <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" value="{{ old('unit_cost') }}">
        </div>

        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
        </div>
    </div>

    <hr class="my-4">
    <button type="submit" class="btn btn-info text-white"><i class="ti ti-check me-1"></i>Record Return</button>
    <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-light">Cancel</a>
</form>
</div></div>
@endsection

@push('scripts')
<script>
$(function() { if ($.fn.select2) $('.select2').select2({ width: '100%' }); });
</script>
@endpush
