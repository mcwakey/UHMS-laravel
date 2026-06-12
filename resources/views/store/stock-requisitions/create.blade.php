@extends('layouts.app')
@section('title', __('stock.new_dept_requisition'))

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <h4 class="fw-bold mb-0">{{ __('stock.new_dept_requisition') }}</h4>
    <a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
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

<form method="POST" action="{{ route('admin.store.stock-requisitions.store') }}" class="card" id="reqForm">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('stock.requesting_department') }} <span class="text-danger">*</span></label>
                <select name="department_id" class="form-select select2" required>
                    <option value="">{{ __('stock.select_department') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $defaultDepartmentId) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('stock.notes') }}</label>
                <input type="text" name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}">
            </div>
        </div>

        <hr>
        <h6 class="mb-2">{{ __('stock.requested_products') }} <span class="text-danger">*</span></h6>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 320px;">{{ __('stock.product') }}</th>
                        <th style="width: 160px;">{{ __('stock.qty_requested') }}</th>
                        <th>{{ __('stock.line_notes') }}</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <tr class="item-row">
                        <td>
                            <select name="items[0][product_id]" class="form-select form-select-sm product-select">
                                <option value="">{{ __('stock.select_product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}
                                        @if($product->code) ({{ $product->code }})@endif
                                        @if($product->departments->isNotEmpty()) — {{ $product->departments->pluck('name')->join(', ') }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[0][quantity_requested]" class="form-control form-control-sm qty-input" step="0.0001" min="0.0001"></td>
                        <td><input type="text" name="items[0][notes]" class="form-control form-control-sm" maxlength="500"></td>
                        <td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
            <i class="ti ti-plus me-1"></i>{{ __('stock.add_item') }}
        </button>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary">{{ __('stock.cancel') }}</a>
        <button class="btn btn-primary">{{ __('stock.submit_requisition') }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let itemIndex = 1;

    // Snapshot the server-rendered product options so new rows share the catalog.
    const productOptionsHtml = $('#itemsBody .product-select').first().html();

    function initProductSelect($select) {
        if ($.fn.select2 && !$select.data('select2')) {
            $select.select2({ width: '100%', placeholder: '{{ __('stock.select_product') }}', allowClear: false });
        }
    }

    function renumberRows() {
        $('.item-row').each(function (index) {
            $(this).find('[name]').each(function () {
                this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
        itemIndex = $('.item-row').length;
    }

    // Prevent picking the same product twice across rows.
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

    $('.product-select').each(function () { initProductSelect($(this)); });

    $('#addItemBtn').on('click', function () {
        const row = `<tr class="item-row">
            <td><select name="items[${itemIndex}][product_id]" class="form-select form-select-sm product-select">${productOptionsHtml}</select></td>
            <td><input type="number" name="items[${itemIndex}][quantity_requested]" class="form-control form-control-sm qty-input" step="0.0001" min="0.0001"></td>
            <td><input type="text" name="items[${itemIndex}][notes]" class="form-control form-control-sm" maxlength="500"></td>
            <td class="text-center"><button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        initProductSelect($('#itemsBody .product-select').last());
        itemIndex++;
        syncProductOptions();
    });

    $(document).on('click', '.remove-row', function () {
        if ($('.item-row').length > 1) {
            const $select = $(this).closest('tr').find('.product-select');
            if ($.fn.select2 && $select.data('select2')) $select.select2('destroy');
            $(this).closest('tr').remove();
            renumberRows();
            syncProductOptions();
        } else {
            // Keep at least one row; just clear it.
            const $row = $(this).closest('tr');
            $row.find('input').val('');
            const $select = $row.find('.product-select');
            $select.val('').trigger('change');
            syncProductOptions();
        }
    });

    $(document).on('change', '.product-select', function () {
        syncProductOptions();
    });

    // On submit, drop rows without a product and renumber to contiguous indexes.
    $('#reqForm').on('submit', function (e) {
        let validIdx = 0;
        $('.item-row').each(function () {
            const $row = $(this);
            const selectEl = $row.find('.product-select')[0];
            const productVal = selectEl ? selectEl.value : '';
            if (!productVal) {
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
            alert('Add at least one product to request.');
            return false;
        }
    });

    syncProductOptions();
});
</script>
@endpush
