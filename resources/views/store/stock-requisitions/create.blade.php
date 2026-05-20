@extends('layouts.app')
@section('title', 'New Stock Requisition')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <h4 class="fw-bold mb-0">New Department Stock Requisition</h4>
    <a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('admin.store.stock-requisitions.store') }}" class="card">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Requesting Department *</label>
                <select name="department_id" class="form-select" required>
                    <option value="">Select department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $defaultDepartmentId) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}">
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Requested Products</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 320px;">Product</th>
                        <th>Qty Requested</th>
                        <th>Line Notes</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(range(0, 5) as $i)
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm">
                                <option value="">Select product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(old("items.$i.product_id") == $product->id)>
                                        {{ $product->name }} @if($product->code) ({{ $product->code }}) @endif
                                        @if($product->departments->isNotEmpty()) - {{ $product->departments->pluck('name')->join(', ') }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][quantity_requested]" class="form-control form-control-sm" step="0.0001" min="0.0001" value="{{ old("items.$i.quantity_requested") }}"></td>
                        <td><input type="text" name="items[{{ $i }}][notes]" class="form-control form-control-sm" maxlength="500" value="{{ old("items.$i.notes") }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Submit Requisition</button>
    </div>
</form>
@endsection
