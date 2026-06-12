@extends('layouts.app')
@section('title', __('stock.new_stock_adjustment'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('stock.new_stock_adjustment') }}</h4>
        <small class="text-muted">{{ __('stock.adjustment_description') }}</small>
    </div>
    <div>
        <a href="{{ route('admin.store.stock.adjustments.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
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
                    <label class="form-label">{{ __('stock.location') }} <span class="text-danger">*</span></label>
                    <select name="stock_location_id" class="form-select" required>
                        <option value="">{{ __('stock.select_location') }}</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('stock_location_id') == $location->id)>{{ $location->name }}@if($location->is_main) ({{ __('stock.main_store') }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('stock.reason') }} <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" placeholder="{{ __('stock.reason_placeholder') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('stock.notes') }}</label>
                    <input type="text" name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}" placeholder="{{ __('stock.notes_optional') }}">
                </div>
            </div>

            <h6 class="mb-2">{{ __('stock.adjustment_lines') }} <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 260px;">{{ __('stock.product') }}</th>
                            <th style="width: 170px;">{{ __('stock.adjustment_type') }}</th>
                            <th style="width: 120px;">{{ __('stock.quantity') }}</th>
                            <th style="width: 140px;">{{ __('stock.unit_cost') }}</th>
                            <th style="width: 130px;">{{ __('stock.batch_number') }}</th>
                            <th style="width: 150px;">{{ __('stock.expiry_date') }}</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select">
                                    <option value="">{{ __('stock.select_product') }}</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="items[0][type]" class="form-select form-select-sm">
                                    <option value="out">{{ __('stock.type_out_remove') }}</option>
                                    <option value="in">{{ __('stock.type_in_add') }}</option>
                                    <option value="damaged">{{ __('stock.type_damaged') }}</option>
                                    <option value="expired">{{ __('stock.type_expired') }}</option>
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

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn"><i class="ti ti-plus me-1"></i>{{ __('stock.add_line') }}</button>

            <div>
                <button type="submit" class="btn btn-primary"><i class="ti ti-adjustments me-1"></i>{{ __('stock.record_adjustments') }}</button>
                <a href="{{ route('admin.store.stock.adjustments.index') }}" class="btn btn-light">{{ __('stock.cancel') }}</a>
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
            $select.select2({ width: '100%', placeholder: '{{ __('stock.select_product') }}' });
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
            alert(@json(__('stock.alert_add_stock_line')));
            return false;
        }
    });

    syncProductOptions();
});
</script>
@endpush
