@extends('layouts.app')
@section('title', 'Create Stock Transfer')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Create Stock Transfer</h4>
    </div>
    <div>
        <a href="{{ route('admin.store.transfers.index') }}" class="btn btn-outline-secondary">
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
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Transfer Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.store.transfers.store') }}" id="transferForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">From Location <span class="text-danger">*</span></label>
                    <select name="from_location" class="form-select" id="fromLocation" required>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->value }}" {{ old('from_location', 'store') == $loc->value ? 'selected' : '' }}>
                                {{ $loc->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Location <span class="text-danger">*</span></label>
                    <select name="to_location" class="form-select" id="toLocation" required>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->value }}" {{ old('to_location', 'pharmacy') == $loc->value ? 'selected' : '' }}>
                                {{ $loc->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="transfer_date" class="form-control" value="{{ old('transfer_date', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional notes..." value="{{ old('notes') }}">
                </div>
            </div>

            <!-- Transfer Items -->
            <h6 class="mb-2">Transfer Items <span class="text-danger">*</span></h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Drug</th>
                            <th style="width:120px;">Available</th>
                            <th style="width:120px;">Quantity</th>
                            <th style="width:150px;">Batch #</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][drug_id]" class="form-select form-select-sm drug-select" required>
                                    <option value="">Select Drug...</option>
                                    @foreach($drugs as $drug)
                                        <option value="{{ $drug->id }}" data-stock="{{ $storeStock[$drug->id] ?? 0 }}">
                                            {{ $drug->name }} ({{ $drug->generic_name }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="available-qty text-center align-middle text-muted">-</td>
                            <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
                            <td><input type="text" name="items[0][batch_number]" class="form-control form-control-sm" placeholder="Batch #"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addItemBtn">
                <i class="ti ti-plus me-1"></i>Add Item
            </button>

            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-transfer me-1"></i>Create Transfer
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

    const drugOptions = `<option value="">Select Drug...</option>@foreach($drugs as $drug)<option value="{{ $drug->id }}" data-stock="{{ $storeStock[$drug->id] ?? 0 }}">{{ $drug->name }} ({{ $drug->generic_name }})</option>@endforeach`;

    // Show available stock when drug selected
    $(document).on('change', '.drug-select', function() {
        const row = $(this).closest('tr');
        const selected = $(this).find(':selected');
        const stock = selected.data('stock') || 0;
        row.find('.available-qty').text(stock > 0 ? stock : '-').toggleClass('text-success fw-medium', stock > 0).toggleClass('text-muted', stock <= 0);
        row.find('.qty-input').attr('max', stock);
    });

    $('#addItemBtn').on('click', function() {
        const row = `<tr class="item-row">
            <td><select name="items[${itemIndex}][drug_id]" class="form-select form-select-sm drug-select" required>${drugOptions}</select></td>
            <td class="available-qty text-center align-middle text-muted">-</td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
            <td><input type="text" name="items[${itemIndex}][batch_number]" class="form-control form-control-sm" placeholder="Batch #"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        itemIndex++;
    });

    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });
});
</script>
@endpush
