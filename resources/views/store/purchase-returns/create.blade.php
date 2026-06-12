@extends('layouts.app')
@section('title', 'New Purchase Return')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <h4 class="fw-bold mb-0">New Purchase Return</h4>
    <a href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('admin.store.purchase-returns.store') }}" class="card" id="returnForm">
    @csrf
    <input type="hidden" name="supplier_id" id="supplierId" value="{{ old('supplier_id') }}">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
                <select name="purchase_order_id" id="poSelect" class="form-select select2" required>
                    <option value="">Select purchase order</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}"
                            data-supplier-id="{{ $po->supplier_id }}"
                            data-supplier-name="{{ $po->supplier?->name }}"
                            @selected(old('purchase_order_id') == $po->id)>
                            {{ $po->po_number }} — {{ $po->supplier?->name }} ({{ $po->order_date?->format('d M Y') }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">A return must reference a received purchase order.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Supplier</label>
                <input type="text" id="supplierName" class="form-control" value="" readonly placeholder="Auto-filled from the PO">
            </div>
            <div class="col-md-4">
                <label class="form-label">Return From <span class="text-danger">*</span></label>
                <select name="stock_location_id" class="form-select" required>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('stock_location_id') == $location->id || $location->is_main)>{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Return Date <span class="text-danger">*</span></label>
                <input type="date" name="return_date" class="form-control" value="{{ old('return_date', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Damaged / expired / wrong item">
            </div>
        </div>

        <hr>
        <h6 class="mb-2">Items to Return</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px;">Product</th>
                        <th style="width: 120px;" class="text-end">Received</th>
                        <th style="width: 130px;">Return Qty</th>
                        <th style="width: 140px;">Unit Cost (GH₵)</th>
                        <th style="width: 130px;">Batch</th>
                        <th style="width: 150px;">Expiry</th>
                        <th style="width: 130px;" class="text-end">Line Total</th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr id="placeholderRow">
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="ti ti-clipboard-search fs-3 d-block mb-2"></i>
                            Select a purchase order above to load its received items.
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                        <td class="text-end fw-bold" id="grandTotal">GH₵ 0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mb-0">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-control" maxlength="1000">{{ old('notes') }}</textarea>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" id="submitBtn" disabled>Create Return</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const poItemsUrlTemplate = "{{ route('admin.store.purchase-returns.po-items', '__PO__') }}";

    // The theme auto-inits .select2 on ready; only init here if it hasn't been.
    if ($.fn.select2 && !$('#poSelect').data('select2')) {
        $('#poSelect').select2({ width: '100%', placeholder: 'Select purchase order' });
    }

    function fmt(n) { return (parseFloat(n) || 0).toFixed(2); }

    function recalc() {
        let grand = 0;
        $('#itemsBody .return-row').each(function () {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const cost = parseFloat($(this).find('.cost-input').val()) || 0;
            const total = qty * cost;
            $(this).find('.line-total').text(fmt(total));
            grand += total;
        });
        $('#grandTotal').text('GH₵ ' + fmt(grand));
    }

    function buildRows(items) {
        const $body = $('#itemsBody').empty();
        if (!items.length) {
            $body.append('<tr><td colspan="7" class="text-center text-muted py-4">This purchase order has no received items to return.</td></tr>');
            $('#submitBtn').prop('disabled', true);
            return;
        }
        items.forEach(function (it, i) {
            const code = it.product_code ? ' (' + it.product_code + ')' : '';
            const row = `<tr class="return-row">
                <td>
                    <div class="fw-medium">${$('<span>').text(it.product_name || '—').html()}${code}</div>
                    <input type="hidden" name="items[${i}][purchase_order_item_id]" value="${it.purchase_order_item_id}">
                    <input type="hidden" name="items[${i}][product_id]" value="${it.product_id}">
                </td>
                <td class="text-end">${fmt(it.quantity_received)}</td>
                <td><input type="number" name="items[${i}][quantity]" class="form-control form-control-sm qty-input" step="0.0001" min="0" max="${it.quantity_received}" value="" placeholder="0"></td>
                <td><input type="number" name="items[${i}][unit_cost]" class="form-control form-control-sm cost-input" step="0.01" min="0" value="${fmt(it.unit_cost)}"></td>
                <td><input type="text" name="items[${i}][batch_no]" class="form-control form-control-sm" maxlength="100" value="${it.batch_no ? $('<span>').text(it.batch_no).html() : ''}"></td>
                <td><input type="date" name="items[${i}][expiry_date]" class="form-control form-control-sm" value="${it.expiry_date || ''}"></td>
                <td class="text-end line-total">0.00</td>
            </tr>`;
            $body.append(row);
        });
        $('#submitBtn').prop('disabled', false);
        recalc();
    }

    $('#poSelect').on('change', function () {
        const id = $(this).val();
        const opt = $(this).find('option:selected');
        $('#supplierId').val(opt.data('supplier-id') || '');
        $('#supplierName').val(opt.data('supplier-name') || '');

        if (!id) {
            $('#itemsBody').html('<tr id="placeholderRow"><td colspan="7" class="text-center text-muted py-4">Select a purchase order above to load its received items.</td></tr>');
            $('#submitBtn').prop('disabled', true);
            $('#grandTotal').text('GH₵ 0.00');
            return;
        }

        $('#itemsBody').html('<tr><td colspan="7" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-1"></span>Loading items…</td></tr>');
        $.getJSON(poItemsUrlTemplate.replace('__PO__', id))
            .done(function (data) {
                $('#supplierId').val(data.supplier_id || $('#supplierId').val());
                $('#supplierName').val(data.supplier_name || $('#supplierName').val());
                buildRows(data.items || []);
            })
            .fail(function () {
                $('#itemsBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load purchase order items.</td></tr>');
                $('#submitBtn').prop('disabled', true);
            });
    });

    $(document).on('input', '.qty-input, .cost-input', recalc);

    // Only submit rows with a positive return quantity; renumber to contiguous indexes.
    $('#returnForm').on('submit', function (e) {
        let validIdx = 0;
        $('#itemsBody .return-row').each(function () {
            const $row = $(this);
            const qty = parseFloat($row.find('.qty-input').val()) || 0;
            if (qty <= 0) {
                $row.find('input').prop('disabled', true);
            } else {
                $row.find('input').prop('disabled', false);
                $row.find('[name]').each(function () {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + validIdx + ']');
                });
                validIdx++;
            }
        });

        if (validIdx === 0) {
            e.preventDefault();
            $('#itemsBody .return-row').find('input').prop('disabled', false);
            alert(@json(__('stock.alert_enter_return_quantity')));
            return false;
        }
    });

    // Restore items if the form came back with old() input after a validation error.
    @if(old('purchase_order_id'))
        $('#poSelect').trigger('change');
    @endif
});
</script>
@endpush
