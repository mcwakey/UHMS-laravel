@extends('layouts.app')
@section('title', __('store.create_purchase_order'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('store.create_purchase_order') }}</h4>
    </div>
    <div>
        <a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}
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
        <h5 class="card-title mb-0">{{ __('store.purchase_order_details') }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.store.purchase-orders.store') }}" id="poForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('stock.supplier') }} <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select select2" required>
                        <option value="">{{ __('store.select_supplier') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('stock.order_date') }} <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('store.expected_delivery_date') }}</label>
                    <input type="date" name="expected_date" class="form-control" value="{{ old('expected_date') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('stock.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('store.optional_notes') }}">{{ old('notes') }}</textarea>
            </div>

            <!-- Order Items -->
            <h6 class="mb-2">{{ __('store.order_items') }} <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('stock.product') }}</th>
                            <th style="width: 120px;">{{ __('stock.quantity') }}</th>
                            <th style="width: 140px;">{{ __('store.unit_cost_cedis') }}</th>
                            <th style="width: 140px;">{{ __('stock.total') }}</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                    <option value="">{{ __('store.select_product') }}</option>
                                    @foreach($products as $product)
                                        @php $typeLabel = $product->product_type instanceof \App\Enums\ProductType ? $product->product_type->value : (string) $product->product_type; @endphp
                                        <option value="{{ $product->id }}"
                                            data-cost="{{ $product->default_cost ?? $product->base_price ?? '' }}"
                                            data-type="{{ $typeLabel }}">
                                            {{ $product->name }} @if($product->code) [{{ $product->code }}]@endif @if($typeLabel) — {{ ucfirst(str_replace('_', ' ', $typeLabel)) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" name="items[0][quantity_ordered]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
                            <td><input type="number" name="items[0][unit_cost]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
                            <td class="row-total text-end align-middle fw-medium">0.00</td>
                            <td class="text-center"><button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">{{ __('store.grand_total') }}</td>
                            <td class="text-end fw-bold" id="grandTotal">GH₵ 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn">
                <i class="ti ti-plus me-1"></i>{{ __('stock.add_item') }}
            </button>

            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-plus me-1"></i>{{ __('store.create_purchase_order') }}
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

    // Snapshot the server-rendered <option> list so newly-added rows have the
    // same product catalog as row 0.
    const productOptionsHtml = $('#itemsBody .product-select').first().html();

    function initProductSelect($select) {
        if ($.fn.select2 && !$select.data('select2')) {
            $select.select2({ width: '100%', placeholder: @json(__('store.select_product')), allowClear: false });
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

    function syncProductOptions() {
        const selected = $('.product-select').map(function() { return $(this).val(); }).get().filter(Boolean);

        $('.product-select').each(function() {
            const current = $(this).val();
            $(this).find('option').each(function() {
                const value = $(this).attr('value');
                $(this).prop('disabled', value && value !== current && selected.includes(value));
            });
        });
    }

    $('.product-select').each(function() { initProductSelect($(this)); });

    $('#addItemBtn').on('click', function() {
        const row = `<tr class="item-row">
            <td><select name="items[${itemIndex}][product_id]" class="form-select form-select-sm product-select" required>${productOptionsHtml}</select></td>
            <td><input type="number" name="items[${itemIndex}][quantity_ordered]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
            <td><input type="number" name="items[${itemIndex}][unit_cost]" class="form-control form-control-sm price-input" step="0.01" min="0" required></td>
            <td class="row-total text-end align-middle fw-medium">0.00</td>
            <td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        initProductSelect($('#itemsBody .product-select').last());
        itemIndex++;
        syncProductOptions();
    });

    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            const $select = $(this).closest('tr').find('.product-select');
            if ($.fn.select2 && $select.data('select2')) $select.select2('destroy');
            $(this).closest('tr').remove();
            renumberRows();
            syncProductOptions();
            calculateTotal();
        }
    });

    $(document).on('change', '.product-select', function() {
        syncProductOptions();
        // Auto-fill unit cost from product default if the field is empty.
        const $row = $(this).closest('tr');
        const $cost = $row.find('.price-input');
        if (!$cost.val()) {
            const def = $(this).find('option:selected').data('cost');
            if (def) { $cost.val(parseFloat(def).toFixed(2)); calculateTotal(); }
        }
    });

    $(document).on('input', '.qty-input, .price-input', function() {
        calculateTotal();
    });

    $('#poForm').on('submit', function(e) {
        let validIdx = 0;

        $('.item-row').each(function() {
            const $row = $(this);
            // Read value directly from native DOM to avoid any Select2 quirks.
            const selectEl = $row.find('.product-select')[0];
            const productVal = selectEl ? selectEl.value : '';

            if (!productVal) {
                // Disable inputs in empty rows — disabled fields are never submitted.
                $row.find('input, select').prop('disabled', true);
            } else {
                $row.find('input, select').prop('disabled', false);
                // Renumber to produce contiguous items[0], items[1], …
                $row.find('[name]').each(function() {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + validIdx + ']');
                });
                validIdx++;
            }
        });

        if (validIdx === 0) {
            e.preventDefault();
            // Re-enable so the user can still interact with the form.
            $('.item-row').find('input, select').prop('disabled', false);
            alert(@json(__('store.select_one_product')));
            return false;
        }
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

    syncProductOptions();
});
</script>
@endpush
