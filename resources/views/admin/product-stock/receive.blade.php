@extends('layouts.app')

@section('title', 'Receive Stock')

@section('content')
<div class="container-fluid py-3" style="max-width: 1100px;">
    <h4 class="mb-3"><i class="ti ti-arrow-down"></i> Receive Stock (Purchase / Opening)</h4>

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.product-stock.receive') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Stock Location *</label>
                    <input type="hidden" name="stock_location_id" value="{{ $mainStore->id }}">
                    <input type="text" class="form-control" value="{{ $mainStore->name }} (Main Store)" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input name="notes" class="form-control" value="{{ old('notes') }}" maxlength="500" placeholder="GRN / Supplier reference…">
                </div>
            </div>

            <table class="table table-sm" id="receiveTable">
                <thead>
                    <tr>
                        <th style="width:34%;">Product *</th>
                        <th style="width:14%;">Quantity *</th>
                        <th style="width:14%;">Unit Cost</th>
                        <th style="width:14%;">Batch No</th>
                        <th style="width:14%;">Expiry Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="row-item">
                        <td>
                            <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                <option value="">— pick product —</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0" name="items[0][quantity]" class="form-control form-control-sm" required></td>
                        <td><input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control form-control-sm"></td>
                        <td><input name="items[0][batch_no]" class="form-control form-control-sm" maxlength="100"></td>
                        <td><input type="date" name="items[0][expiry_date]" class="form-control form-control-sm"></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger remove-row">×</button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="ti ti-plus"></i> Add item</button>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('admin.product-stock.balances') }}" class="btn btn-link">Cancel</a>
            <button class="btn btn-success"><i class="ti ti-check"></i> Receive Stock</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function(){
    let idx = 1;
    const tbody = document.querySelector('#receiveTable tbody');
    document.getElementById('addRow').addEventListener('click', () => {
        const tpl = tbody.querySelector('.row-item').cloneNode(true);
        tpl.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/items\[\d+\]/, `items[${idx}]`);
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
        });
        tbody.appendChild(tpl);
        idx++;
    });
    tbody.addEventListener('click', (e) => {
        if (e.target.closest('.remove-row')) {
            const rows = tbody.querySelectorAll('.row-item');
            if (rows.length > 1) e.target.closest('.row-item').remove();
        }
    });
})();
</script>
@endpush
@endsection
