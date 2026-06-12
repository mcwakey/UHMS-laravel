@extends('layouts.app')
@section('title', __('invoices.create_invoice'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-0 d-flex align-items-center">
            <a href="{{ route('admin.billing.invoices.index') }}"><i class="ti ti-chevron-left me-1 fs-14"></i>{{ __('invoices.title') }}</a>
        </h6>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold m-0"><i class="ti ti-file-invoice me-2"></i>{{ __('invoices.new_invoice_title') }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.billing.invoices.store') }}" id="invoiceForm">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">{{ __('invoices.patient_label') }} <span class="text-danger">*</span></label>
                    @if($visit)
                        <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                        <input type="hidden" name="patient_id" value="{{ $visit->patient_id }}">
                        <input type="text" class="form-control" value="{{ $visit->patient->full_name }} ({{ $visit->patient->patient_number }})" readonly>
                        <small class="text-muted">{{ __('invoices.visit') }}: {{ $visit->visit_number }} — {{ $visit->visit_date->format('d M Y') }}</small>
                    @else
                        <select name="visit_id" id="visitSelect" class="form-select @error('visit_id') is-invalid @enderror" required>
                            <option value="">{{ __('invoices.select_visit') }}</option>
                            @foreach($billableVisits as $billableVisit)
                            <option value="{{ $billableVisit->id }}" data-patient-id="{{ $billableVisit->patient_id }}">
                                {{ $billableVisit->visit_number }} — {{ $billableVisit->patient?->full_name }} — {{ $billableVisit->visit_date?->format('d M Y') }} ({{ $billableVisit->visitServices->count() }} service{{ $billableVisit->visitServices->count() === 1 ? '' : 's' }})
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="patient_id" id="patientIdInput">
                        @error('visit_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">{{ __('invoices.billing_type_label') }} <span class="text-danger">*</span></label>
                    <select name="billing_type" class="form-select @error('billing_type') is-invalid @enderror" required>
                        @foreach($billingTypes as $type)
                        <option value="{{ $type->value }}" {{ old('billing_type', 'cash') === $type->value ? 'selected' : '' }}>
                            {{ $type->translatedLabel() }}
                        </option>
                        @endforeach
                    </select>
                    @error('billing_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">{{ __('invoices.due_date_label') }}</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
                </div>
            </div>

            <!-- Invoice Items -->
            <h6 class="fw-bold mb-3"><i class="ti ti-list-details me-1"></i>{{ __('invoices.invoice_items_title') }}</h6>
            <div class="mb-3">
                <div class="table-responsive"><table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30%">{{ __('invoices.description_col') }} <span class="text-danger">*</span></th>
                            <th style="width:15%">{{ __('invoices.service_col') }}</th>
                            <th style="width:8%">{{ __('invoices.qty_col') }}</th>
                            <th style="width:12%">{{ __('invoices.unit_price_col') }}</th>
                            <th style="width:12%">{{ __('invoices.total_col') }}</th>
                            <th style="width:8%">{{ __('invoices.covered_col') }}</th>
                            <th style="width:12%">{{ __('invoices.covered_amt_col') }}</th>
                            <th style="width:3%"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @if(count($suggestedItems) > 0)
                            @foreach($suggestedItems as $i => $item)
                            <tr class="item-row">
                                <td>
                                    <input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $item['description'] }}" required>
                                </td>
                                <td>
                                    <select name="items[{{ $i }}][service_catalog_id]" class="form-select form-select-sm service-select">
                                        <option value="">—</option>
                                        @foreach($services as $svc)
                                        <option value="{{ $svc->id }}" data-price="{{ $svc->price }}" data-nhis-price="{{ $svc->nhis_price }}" data-nhis="{{ $svc->is_nhis_covered ? 1 : 0 }}"
                                            {{ ($item['service_catalog_id'] ?? '') == $svc->id ? 'selected' : '' }}>
                                            {{ $svc->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm qty-input" value="{{ $item['quantity'] ?? 1 }}" min="1" required>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm price-input" value="{{ $item['unit_price'] }}" step="0.01" min="0" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm line-total" readonly value="{{ number_format(($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1), 2) }}">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="items[{{ $i }}][is_nhis_covered]" class="form-check-input nhis-check" value="1" {{ !empty($item['is_nhis_covered']) ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][nhis_approved_amount]" class="form-control form-control-sm nhis-amount" value="{{ $item['nhis_approved_amount'] ?? 0 }}" step="0.01" min="0">
                                </td>
                                <td>
                                    <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr class="item-row">
                                <td>
                                    <input type="text" name="items[0][description]" class="form-control form-control-sm" required>
                                </td>
                                <td>
                                    <select name="items[0][service_catalog_id]" class="form-select form-select-sm service-select">
                                        <option value="">—</option>
                                        @foreach($services as $svc)
                                        <option value="{{ $svc->id }}" data-price="{{ $svc->price }}" data-nhis-price="{{ $svc->nhis_price }}" data-nhis="{{ $svc->is_nhis_covered ? 1 : 0 }}">
                                            {{ $svc->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required>
                                </td>
                                <td>
                                    <input type="number" name="items[0][unit_price]" class="form-control form-control-sm price-input" value="0" step="0.01" min="0" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm line-total" readonly value="0.00">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="items[0][is_nhis_covered]" class="form-check-input nhis-check" value="1">
                                </td>
                                <td>
                                    <input type="number" name="items[0][nhis_approved_amount]" class="form-control form-control-sm nhis-amount" value="0" step="0.01" min="0">
                                </td>
                                <td>
                                    <button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table></div>
            </div>

            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addItemBtn">
                <i class="ti ti-plus me-1"></i>{{ __('invoices.add_item_btn') }}
            </button>

            <!-- Totals -->
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="table-responsive"><table class="table table-sm table-borderless">
                        <tr>
                            <td class="fw-medium">{{ __('invoices.subtotal') }}:</td>
                            <td class="text-end" id="subtotalDisplay">&#8373;0.00</td>
                        </tr>
                        <tr>
                            <td class="fw-medium">{{ __('invoices.tax_label') }}:</td>
                            <td class="text-end">
                                <input type="number" name="tax_amount" class="form-control form-control-sm text-end" id="taxInput" value="0" step="0.01" min="0" style="max-width:150px;margin-left:auto;">
                            </td>
                        </tr>
                        <tr class="d-none">
                            <td class="fw-medium">{{ __('invoices.discount') }} (&#8373;):</td>
                            <td class="text-end">
                                <input type="number" name="discount_amount" class="form-control form-control-sm text-end" id="discountInput" value="0" step="0.01" min="0" style="max-width:150px;margin-left:auto;">
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-medium text-primary">{{ __('invoices.insurance_covered') }}:</td>
                            <td class="text-end text-primary fw-bold" id="nhisDisplay">&#8373;0.00</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold fs-5">{{ __('invoices.total') }}:</td>
                            <td class="text-end fw-bold fs-5" id="totalDisplay">&#8373;0.00</td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-danger">{{ __('invoices.patient_pays') }}:</td>
                            <td class="text-end fw-bold text-danger" id="patientPaysDisplay">&#8373;0.00</td>
                        </tr>
                    </table></div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-3">
                <label class="form-label fw-medium">{{ __('invoices.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('invoices.optional_notes') }}">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-file-invoice me-1"></i>{{ __('invoices.create_invoice_btn') }}
                </button>
                <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    let rowIndex = {{ count($suggestedItems) > 0 ? count($suggestedItems) : 1 }};

    function renumberRows() {
        $('#itemsBody .item-row').each(function(index) {
            $(this).find('[name]').each(function() {
                this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
        rowIndex = $('#itemsBody .item-row').length;
    }

    function completeRowFromService(row) {
        let select = row.find('.service-select');
        let opt = select.find(':selected');
        let desc = row.find('[name$="[description]"]');

        if (opt.val() && !desc.val()) {
            desc.val(opt.text().trim());
        }
    }

    function recalculate() {
        let subtotal = 0, nhisTotal = 0;
        $('#itemsBody .item-row').each(function() {
            let qty = parseFloat($(this).find('.qty-input').val()) || 0;
            let price = parseFloat($(this).find('.price-input').val()) || 0;
            let lineTotal = qty * price;
            $(this).find('.line-total').val(lineTotal.toFixed(2));
            subtotal += lineTotal;

            if ($(this).find('.nhis-check').is(':checked')) {
                nhisTotal += parseFloat($(this).find('.nhis-amount').val()) || 0;
            }
        });

        let tax = parseFloat($('#taxInput').val()) || 0;
        let discount = parseFloat($('#discountInput').val()) || 0;
        let total = subtotal + tax - discount;
        let patientPays = total - nhisTotal;

        $('#subtotalDisplay').text('₵' + subtotal.toFixed(2));
        $('#nhisDisplay').text('₵' + nhisTotal.toFixed(2));
        $('#totalDisplay').text('₵' + total.toFixed(2));
        $('#patientPaysDisplay').text('₵' + Math.max(0, patientPays).toFixed(2));
    }

    // Recalculate on input changes
    $(document).on('input change', '.qty-input, .price-input, .nhis-amount, .nhis-check, #taxInput, #discountInput', recalculate);

    $('#visitSelect').on('change', function() {
        if (this.value) {
            var url = '{{ route("admin.billing.invoices.create") }}?visit_id=' + encodeURIComponent(this.value);
            if (window.UhmsInertia) {
                window.UhmsInertia.visit(url, { preserveScroll: true });
            } else {
                window.location.href = url;
            }
        }
    });

    // Service select auto-fills price
    $(document).on('change', '.service-select', function() {
        let opt = $(this).find(':selected');
        let row = $(this).closest('.item-row');
        if (opt.val()) {
            completeRowFromService(row);
            row.find('.price-input').val(opt.data('price') || 0);
            if (opt.data('nhis') == 1) {
                row.find('.nhis-check').prop('checked', true);
                row.find('.nhis-amount').val(opt.data('nhis-price') || opt.data('price') || 0);
            }
        }
        recalculate();
    });

    // Add item row
    $('#addItemBtn').on('click', function() {
        let serviceOptions = '';
        @foreach($services as $svc)
        serviceOptions += '<option value="{{ $svc->id }}" data-price="{{ $svc->price }}" data-nhis-price="{{ $svc->nhis_price }}" data-nhis="{{ $svc->is_nhis_covered ? 1 : 0 }}">{{ addslashes($svc->name) }}</option>';
        @endforeach

        let row = `<tr class="item-row">
            <td><input type="text" name="items[${rowIndex}][description]" class="form-control form-control-sm" required></td>
            <td><select name="items[${rowIndex}][service_catalog_id]" class="form-select form-select-sm service-select"><option value="">—</option>${serviceOptions}</select></td>
            <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm qty-input" value="1" min="1" required></td>
            <td><input type="number" name="items[${rowIndex}][unit_price]" class="form-control form-control-sm price-input" value="0" step="0.01" min="0" required></td>
            <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
            <td class="text-center"><input type="checkbox" name="items[${rowIndex}][is_nhis_covered]" class="form-check-input nhis-check" value="1"></td>
            <td><input type="number" name="items[${rowIndex}][nhis_approved_amount]" class="form-control form-control-sm nhis-amount" value="0" step="0.01" min="0"></td>
            <td><button aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}" type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-trash"></i></button></td>
        </tr>`;
        $('#itemsBody').append(row);
        rowIndex++;
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#itemsBody .item-row').length > 1) {
            $(this).closest('.item-row').remove();
            renumberRows();
            recalculate();
        }
    });

    $('#invoiceForm').on('submit', function() {
        $('#itemsBody .item-row').each(function() {
            let row = $(this);
            completeRowFromService(row);

            let hasDescription = row.find('[name$="[description]"]').val();
            let hasService = row.find('.service-select').val();
            let isOnlyRow = $('#itemsBody .item-row').length === 1;
            if (!hasDescription && !hasService && !isOnlyRow) {
                row.remove();
            }
        });

        renumberRows();
        recalculate();
    });

    // Initial calculation
    recalculate();
});
</script>
@endsection
