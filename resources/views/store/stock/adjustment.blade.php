@extends('layouts.app')
@section('title', 'New Stock Adjustment')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">New Stock Adjustment</h4>
        <small class="text-muted">For corrections, damaged or expired stock — add as many lines as you need.</small>
    </div>
    <div>
        <a href="{{ route('admin.store.stock.adjustments.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.store.stock.adjustments.store') }}" id="adjustForm">
            @csrf
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Location <span class="text-danger">*</span></label>
                    <select name="stock_location_id" class="form-select" required>
                        <option value="">Select location…</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('stock_location_id') == $location->id)>{{ $location->name }}@if($location->is_main) (Main Store)@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="e.g. Physical count variance" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}" placeholder="Optional">
                </div>
            </div>

            <h6 class="mb-2">Adjustment Lines <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 260px;">Product</th>
                            <th style="width: 170px;">Type</th>
                            <th style="width: 120px;">Quantity</th>
                            <th style="width: 140px;">Unit Cost</th>
                            <th style="width: 130px;">Batch</th>
                            <th style="width: 150px;">Expiry</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select">
                                    <option value="">Select product…</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="items[0][type]" class="form-select form-select-sm">
                                    <option value="out">Out (remove)</option>
                                    <option value="in">In (add)</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" step="0.0001" min="0.0001"></td>
                            <td><input type="number" name="items[0][unit_cost]" class="form-control form-control-sm" step="0.01" min="0"></td>
                            <td><input type="text" name="items[0][batch_no]" class="form-control form-control-sm" maxlength="100"></td>
                            <td><input type="date" name="items[0][expiry_date]" class="form-control form-control-sm"></td>
                            <td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn"><i class="ti ti-plus me-1"></i>Add Line</button>

            <div>
                <button type="submit" class="btn btn-primary"><i class="ti ti-adjustments me-1"></i>Record Adjustments</button>
                <a href="{{ route('admin.store.stock.adjustments.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let itemIndex = 1;
    const productOptionsHtml = $('#itemsBody .product-select').first().html();
    const typeOptionsHtml = $('#itemsBody select[name="items[0][type]"]').first().html();

    function initProduct($select) {
        if ($.fn.select2 && !$select.data('select2')) {
            $select.select2({ width: '100%', placeholder: 'Select product…' });
        }
    }
    function syncProductOptions() {
        const selected = $('.product-select').map(function () { return $(this).val(); }).get().filter(Boolean);
        $('.product-select').each(function () {
            const current = $(this).val();
            $(this).find('option').each(function () {
                const value = $(this).attr('value');
                $(this).prop('disabled', value && value !== current && selected.includes(value));
            });
        });
    }
    $('.product-select').each(function () { initProduct($(this)); });

    $('#addItemBtn').on('click', function () {
        const row = `<tr class="item-row">
            <td><select name="items[${itemIndex}][product_id]" class="form-select form-select-sm product-select">${productOptionsHtml}</select></td>
            <td><select name="items[${itemIndex}][type]" class="form-select form-select-sm">${typeOptionsHtml}</select></td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm" step="0.0001" min="0.0001"></td>
            <td><input type="number" name="items[${itemIndex}][unit_cost]" class="form-control form-control-sm" step="0.01" min="0"></td>
            <td><input type="text" name="items[${itemIndex}][batch_no]" class="form-control form-control-sm" maxlength="100"></td>
            <td><input type="date" name="items[${itemIndex}][expiry_date]" class="form-control form-control-sm"></td>
            <td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        initProduct($('#itemsBody .product-select').last());
        itemIndex++;
        syncProductOptions();
    });

    $(document).on('click', '.remove-row', function () {
        if ($('.item-row').length > 1) {
            const $select = $(this).closest('tr').find('.product-select');
            if ($.fn.select2 && $select.data('select2')) $select.select2('destroy');
            $(this).closest('tr').remove();
            syncProductOptions();
        }
    });

    $(document).on('change', '.product-select', syncProductOptions);

    $('#adjustForm').on('submit', function (e) {
        let validIdx = 0;
        $('.item-row').each(function () {
            const $row = $(this);
            const productEl = $row.find('.product-select')[0];
            const productVal = productEl ? productEl.value : '';
            const qty = parseFloat($row.find('input[name$="[quantity]"]').val()) || 0;
            if (!productVal || qty <= 0) {
                $row.find('input, select').prop('disabled', true);
            } else {
                $row.find('input, select').prop('disabled', false);
                $row.find('[name]').each(function () {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + validIdx + ']');
                });
                validIdx++;
            }
        });
        if (validIdx === 0) {
            e.preventDefault();
            $('.item-row').find('input, select').prop('disabled', false);
            alert('Add at least one line with a product and quantity.');
            return false;
        }
    });

    syncProductOptions();
});
</script>
@endpush
