@extends('layouts.app')
@section('title', 'Create Purchase Order')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Create Purchase Order</h4>
    </div>
    <div>
        <a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Purchase Order Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.store.purchase-orders.store') }}" id="poForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select select2" required>
                        <option value="">Select Supplier...</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expected Delivery Date</label>
                    <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>

            <!-- Order Items -->
            <h6 class="mb-2">Order Items <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Drug</th>
                            <th style="width: 120px;">Quantity</th>
                            <th style="width: 140px;">Unit Cost (GH₵)</th>
                            <th style="width: 140px;">Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][drug_id]" class="form-select form-select-sm drug-select" required>
                                    <option value="">Select Drug...</option>
                                    @foreach($drugs as $drug)
                                        <option value="{{ $drug->id }}">{{ $drug->name }} ({{ $drug->generic_name }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity_ordered]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
                            <td><input type="number" name="items[0][unit_cost]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
                            <td class="row-total text-end align-middle fw-medium">0.00</td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                            <td class="text-end fw-bold" id="grandTotal">GH₵ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn">
                <i class="ti ti-plus me-1"></i>Add Item
            </button>

            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-plus me-1"></i>Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let itemIndex = 1;

    const drugOptions = `<option value="">Select Drug...</option>@foreach($drugs as $drug)<option value="{{ $drug->id }}">{{ $drug->name }} ({{ $drug->generic_name }})</option>@endforeach`;

    function initDrugSelect($select) {
        if ($.fn.select2 && !$select.data('select2')) {
            $select.select2({ width: '100%', placeholder: 'Select Drug...', allowClear: true });
        }
    }

    function renumberRows() {
        $('.item-row').each(function(index) {
            $(this).find('[name]').each(function() {
                this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
        itemIndex = $('.item-row').length;
    }

    function syncDrugOptions() {
        const selected = $('.drug-select').map(function() { return $(this).val(); }).get().filter(Boolean);

        $('.drug-select').each(function() {
            const current = $(this).val();
            $(this).find('option').each(function() {
                const value = $(this).attr('value');
                $(this).prop('disabled', value && value !== current && selected.includes(value));
            });
        });
    }

    $('.drug-select').each(function() { initDrugSelect($(this)); });

    $('#addItemBtn').on('click', function() {
        const row = `<tr class="item-row">
            <td><select name="items[${itemIndex}][drug_id]" class="form-select form-select-sm drug-select" required>${drugOptions}</select></td>
            <td><input type="number" name="items[${itemIndex}][quantity_ordered]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
            <td><input type="number" name="items[${itemIndex}][unit_cost]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
            <td class="row-total text-end align-middle fw-medium">0.00</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        initDrugSelect($('#itemsBody .drug-select').last());
        itemIndex++;
        syncDrugOptions();
    });

    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            const $select = $(this).closest('tr').find('.drug-select');
            if ($.fn.select2 && $select.data('select2')) $select.select2('destroy');
            $(this).closest('tr').remove();
            renumberRows();
            syncDrugOptions();
            calculateTotal();
        }
    });

    $(document).on('change', '.drug-select', function() {
        syncDrugOptions();
    });

    $(document).on('input', '.qty-input, .price-input', function() {
        calculateTotal();
    });

    $('#poForm').on('submit', function() {
        $('.item-row').each(function() {
            const hasDrug = $(this).find('.drug-select').val();
            const isOnlyRow = $('.item-row').length === 1;
            if (!hasDrug && !isOnlyRow) {
                const $select = $(this).find('.drug-select');
                if ($.fn.select2 && $select.data('select2')) $select.select2('destroy');
                $(this).remove();
            }
        });

        renumberRows();
        syncDrugOptions();
        calculateTotal();
    });

    function calculateTotal() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const price = parseFloat($(this).find('.price-input').val()) || 0;
            const total = qty * price;
            $(this).find('.row-total').text(total.toFixed(2));
            grandTotal += total;
        });
        $('#grandTotal').text('GH₵ ' + grandTotal.toFixed(2));
    }

    syncDrugOptions();
});
</script>
@endpush
