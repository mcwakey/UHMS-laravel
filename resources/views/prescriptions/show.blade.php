@extends('layouts.app')
@section('title', 'Prescription ' . $prescription->prescription_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Prescription {{ $prescription->prescription_number }}</h4>
        <small class="text-muted">Created {{ $prescription->created_at->format('d M Y, h:i A') }} by Dr. {{ $prescription->doctor->full_name }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.prescriptions.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Prescriptions
        </a>
        @if($prescription->visit)
        @can('prescriptions.create')
        <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#newPrescriptionModal">
            <i class="ti ti-plus me-1"></i>New Rx
        </button>
        @endcan
        @endif
        <a data-no-inertia href="{{ route('admin.prescriptions.print', $prescription) }}" target="_blank" class="btn btn-outline-dark btn-md">
            <i class="ti ti-printer me-1"></i>Print Rx
        </a>
        @if($prescription->status->value === 'pending')
        @can('prescriptions.create')
        <form method="POST" action="{{ route('admin.prescriptions.cancel', $prescription) }}" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-danger btn-md" onclick="return confirm('Cancel this prescription?')">
                <i class="ti ti-x me-1"></i>Cancel Prescription
            </button>
        </form>
        @endcan
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <!-- Main Info -->
    <div class="col-lg-8">
        <!-- Status -->
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-5">{{ $prescription->prescription_number }}</span>
                </div>
                <x-status-badge :status="$prescription->status" class="fs-14 px-3 py-2" />
            </div>
        </div>

        <!-- Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-pill me-1"></i>Prescription Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('prescriptions.drug_name') }}</th>
                                <th>{{ __('pharmacy.dosage') }}</th>
                                <th>{{ __('pharmacy.frequency') }}</th>
                                <th>{{ __('pharmacy.duration') }}</th>
                                <th>{{ __('pharmacy.route') }}</th>
                                <th>{{ __('common.qty') }}</th>
                                <th>{{ __('pharmacy.dispensed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescription->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="fw-medium">{{ $item->drug_name }}</td>
                                <td>{{ $item->dosage }}</td>
                                <td>{{ $item->frequency }}</td>
                                <td>{{ $item->duration }}</td>
                                <td>{{ $item->route }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>
                                    @if($item->is_dispensed)
                                        <span class="badge bg-success"><i class="ti ti-check"></i> Yes</span>
                                    @else
                                        <span class="badge bg-warning">No</span>
                                    @endif
                                </td>
                            </tr>
                            @if($item->instructions)
                            <tr>
                                <td></td>
                                <td colspan="7"><small class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ $item->instructions }}</small></td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pharmacy Billing (moved here from dispensing; dispense only after settlement) -->
        @can('pharmacy.dispensing.create')
        @php
            $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4, '.', ''), '0'), '.') ?: '0';
            $billableItems = $prescription->items->filter(fn ($item) => ($item->remaining_prescribed_to_bill ?? 0) > 0 && $item->drug?->product_id);
        @endphp
        <div class="card mb-3">
            <div class="card-header  bg-primary-subtle d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="ti ti-receipt me-1"></i>Bill for Dispensing</h6>
                <span class="badge bg-light text-dark">Patient pays before the pharmacy dispenses</span>
            </div>
            <div class="card-body">
                @if(!empty($billingError))
                    <div class="alert alert-warning mb-0 py-2"><i class="ti ti-alert-triangle me-1"></i>{{ $billingError }}</div>
                @elseif($billableItems->isEmpty())
                    <p class="text-muted mb-0">All prescribed items have been billed. Once payment is settled, they can be dispensed at the pharmacy.</p>
                @else
                <form method="POST" action="{{ route('admin.prescriptions.bill', $prescription) }}" id="pharmacyBillForm">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="pharmacyBillTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:48px">{{ __('prescriptions.bill') }}</th>
                                    <th>{{ __('common.drug') }}</th>
                                    <th class="text-end">{{ __('prescriptions.prescribed') }}</th>
                                    <th class="text-end">{{ __('pharmacy.billed') }}</th>
                                    <th class="text-end">{{ __('store.remaining') }}</th>
                                    <th class="text-end">{{ __('prescriptions.pharmacy_qty') }}</th>
                                    <th class="text-end">{{ __('pharmacy.unit_price') }}</th>
                                    <th style="width:130px">{{ __('prescriptions.selected_qty') }}</th>
                                    <th class="text-end">{{ __('store.line_total') }}</th>
                                    <th>{{ __('common.notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billableItems as $item)
                                    @php
                                        $remainingToBill = (float) ($item->remaining_prescribed_to_bill ?? 0);
                                        $pharmacyQty = (float) ($item->pharmacy_available_quantity ?? 0);
                                        $defaultQty = min($remainingToBill, $pharmacyQty);
                                        $pharmacyStatus = $item->pharmacy_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                                        $unitPrice = (float) ($item->drug?->product?->base_price ?? 0);
                                    @endphp
                                    <tr class="bill-row" data-price="{{ $unitPrice }}">
                                        <td><input type="checkbox" class="form-check-input bill-check" name="items[{{ $item->id }}][selected]" value="1" {{ $defaultQty > 0 ? '' : 'disabled' }}></td>
                                        <td>
                                            <span class="fw-medium">{{ $item->drug_name }}</span>
                                            @if($item->drug)<br><small class="text-muted">{{ $item->drug->dosage_form }} {{ $item->drug->strength }}</small>@endif
                                        </td>
                                        <td class="text-end">{{ $formatQty($item->quantity) }}</td>
                                        <td class="text-end">{{ $formatQty($item->billed_quantity ?? 0) }}</td>
                                        <td class="text-end fw-semibold text-primary">{{ $formatQty($remainingToBill) }}</td>
                                        <td class="text-end">{{ $formatQty($pharmacyQty) }} <span class="badge bg-{{ $pharmacyStatus['class'] }} ms-1">{{ $pharmacyStatus['label'] }}</span></td>
                                        <td class="text-end">GH₵ {{ number_format($unitPrice, 2) }}</td>
                                        <td><input type="number" name="items[{{ $item->id }}][quantity]" class="form-control form-control-sm bill-qty" value="{{ $formatQty($defaultQty) }}" min="0" max="{{ $formatQty($defaultQty) }}"></td>
                                        <td class="text-end fw-medium line-total">GH₵ 0.00</td>
                                        <td><input type="text" name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" placeholder="Optional"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="8" class="text-end fw-bold">Total to bill:</td>
                                    <td class="text-end fw-bold" id="pharmacyBillTotal">GH₵ 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-receipt me-1"></i>Bill Selected</button>
                    </div>
                </form>
                @push('scripts')
                <script>
                (function () {
                    const table = document.getElementById('pharmacyBillTable');
                    if (!table) return;
                    const fmt = n => 'GH₵ ' + (parseFloat(n) || 0).toFixed(2);
                    function recalc() {
                        let grand = 0;
                        table.querySelectorAll('.bill-row').forEach(function (row) {
                            const price = parseFloat(row.dataset.price) || 0;
                            const qty = parseFloat(row.querySelector('.bill-qty')?.value) || 0;
                            const checked = row.querySelector('.bill-check')?.checked;
                            row.querySelector('.line-total').textContent = fmt(qty * price);
                            if (checked) grand += qty * price;
                        });
                        document.getElementById('pharmacyBillTotal').textContent = fmt(grand);
                    }
                    table.addEventListener('input', function (e) { if (e.target.classList.contains('bill-qty')) recalc(); });
                    table.addEventListener('change', function (e) { if (e.target.classList.contains('bill-check')) recalc(); });
                    recalc();
                })();
                </script>
                @endpush
                @endif
            </div>
        </div>
        @endcan

        <!-- Notes -->
        @if($prescription->notes)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>Notes</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $prescription->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Patient</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-bold">{{ $prescription->patient->full_name }}</h6>
                <small class="text-muted d-block">{{ $prescription->patient->patient_number }}</small>
                <small class="text-muted d-block">{{ $prescription->patient->age }}y &middot; {{ $prescription->patient->gender->value }}</small>
                @if($prescription->patient->allergies)
                <div class="alert alert-danger py-1 mt-2 mb-0">
                    <small><strong>{{ __('prescriptions.allergies') }}:</strong> {{ $prescription->patient->allergies }}</small>
                </div>
                @endif
            </div>
        </div>

        <!-- Visit Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-calendar-check me-1"></i>Visit</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.visits.show', $prescription->visit) }}" class="fw-medium">{{ $prescription->visit->visit_number }}</a>
                <small class="text-muted d-block">{{ $prescription->visit->visit_date->format('d M Y') }}</small>
                <x-status-badge :status="$prescription->visit->status" />
            </div>
        </div>

        <!-- Doctor Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1"></i>Prescribing Doctor</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-medium">Dr. {{ $prescription->doctor->full_name }}</h6>
                <small class="text-muted">{{ $prescription->doctor->department?->name ?? '—' }}</small>
            </div>
        </div>
    </div>
</div>

@if($prescription->visit)
@can('prescriptions.create')
<div class="modal fade" id="newPrescriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.prescriptions.another', $prescription) }}" id="newPrescriptionForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0"><i class="ti ti-prescription me-1"></i>New Prescription</h5>
                        <small class="text-muted">{{ $prescription->patient->full_name }} &middot; {{ $prescription->visit->visit_number }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="newPrescriptionItems">
                        <div class="prescription-modal-item border rounded p-2 mb-2" data-index="0">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small">{{ __('common.drug') }} <span class="text-danger">*</span></label>
                                    <select name="items[0][drug_id]" class="form-select form-select-sm prescription-drug-select" required>
                                        <option value="">-- Search drug --</option>
                                        @foreach($drugs as $drug)
                                            <option value="{{ $drug->id }}"
                                                data-name="{{ $drug->name }}"
                                                data-strength="{{ $drug->strength ?? '' }}"
                                                data-unit="{{ $drug->unit ?? '' }}">
                                                {{ $drug->name }}{{ $drug->generic_name ? ' ('.$drug->generic_name.')' : '' }}{{ $drug->strength ? ' - '.$drug->strength : '' }}{{ $drug->dosage_form ? ' ['.$drug->dosage_form.']' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="items[0][drug_name]" class="prescription-drug-name">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">{{ __('pharmacy.dosage') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="items[0][dosage]" class="form-control form-control-sm" required placeholder="500mg">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">{{ __('pharmacy.frequency') }} <span class="text-danger">*</span></label>
                                    <select name="items[0][frequency]" class="form-select form-select-sm" required>
                                        <option value="OD">OD</option>
                                        <option value="BD">BD</option>
                                        <option value="TDS" selected>TDS</option>
                                        <option value="QDS">QDS</option>
                                        <option value="STAT">STAT</option>
                                        <option value="PRN">PRN</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">{{ __('pharmacy.duration') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="items[0][duration]" class="form-control form-control-sm" required placeholder="5 days">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">{{ __('common.qty') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm" required min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">{{ __('pharmacy.route') }}</label>
                                    <select name="items[0][route]" class="form-select form-select-sm" required>
                                        <option value="oral">{{ __('consultations.medicine_route.oral') }}</option>
                                        <option value="IV">IV</option>
                                        <option value="IM">IM</option>
                                        <option value="SC">SC</option>
                                        <option value="topical">{{ __('consultations.medicine_route.topical') }}</option>
                                        <option value="rectal">{{ __('consultations.medicine_route.rectal') }}</option>
                                        <option value="sublingual">{{ __('consultations.medicine_route.sublingual') }}</option>
                                        <option value="inhaled">{{ __('consultations.medicine_route.inhaled') }}</option>
                                        <option value="nasal">{{ __('consultations.medicine_route.nasal') }}</option>
                                        <option value="ophthalmic">{{ __('consultations.medicine_route.ophthalmic') }}</option>
                                        <option value="otic">{{ __('consultations.medicine_route.otic') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small">{{ __('common.notes') }}</label>
                                    <input type="text" name="items[0][instructions]" class="form-control form-control-sm" placeholder="Special instructions...">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-prescription-modal-item d-none" title="{{ __('common.delete') }}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addPrescriptionModalItem">
                            <i class="ti ti-plus me-1"></i>Add Medication
                        </button>
                        <input type="text" name="notes" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Rx notes...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Create Rx</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const wrapper = document.getElementById('newPrescriptionItems');
    const addButton = document.getElementById('addPrescriptionModalItem');
    const form = document.getElementById('newPrescriptionForm');
    if (!wrapper || !addButton || !form) return;

    function syncDrugNames(root) {
        root.querySelectorAll('.prescription-drug-select').forEach(function (select) {
            const selected = select.options[select.selectedIndex];
            const hidden = select.closest('.prescription-modal-item').querySelector('.prescription-drug-name');
            if (hidden) hidden.value = selected && selected.value ? (selected.dataset.name || selected.textContent.trim()) : '';
        });
    }

    function initDrugSelect(select) {
        if (!select || !window.jQuery || !window.jQuery.fn.select2) return;

        const $select = window.jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            dropdownParent: window.jQuery('#newPrescriptionModal'),
            theme: 'default',
            width: '100%',
            placeholder: '-- Search drug --',
            allowClear: true,
        });
    }

    const freqMap = { OD: 1, BD: 2, TDS: 3, QDS: 4, STAT: 1, PRN: 1 };

    function parseMg(str) {
        if (!str) return null;
        const match = String(str).match(/([\d.]+)\s*(mg|mcg|g|ml|iu|units?)?/i);
        if (!match) return null;

        let value = parseFloat(match[1]);
        const unit = (match[2] || 'mg').toLowerCase();
        if (unit === 'g') value *= 1000;
        if (unit === 'mcg') value /= 1000;

        return Number.isNaN(value) ? null : value;
    }

    function parseDoseUnits(str) {
        if (!str) return null;
        const text = String(str).toLowerCase().trim();
        const unitDose = text.match(/^(\d+(?:\.\d+)?)\s*(tab|tabs|tablet|tablets|cap|caps|capsule|capsules|amp|amps|ampoule|ampoules|vial|vials|drop|drops|puff|puffs|sachet|sachets|unit|units)\b/);
        if (unitDose) {
            const unitValue = parseFloat(unitDose[1]);
            return Number.isNaN(unitValue) ? null : unitValue;
        }

        const plainNumber = text.match(/^(\d+(?:\.\d+)?)$/);
        if (plainNumber) {
            const plainValue = parseFloat(plainNumber[1]);
            return Number.isNaN(plainValue) ? null : plainValue;
        }

        return null;
    }

    function parseDays(str) {
        if (!str) return null;
        const match = String(str).toLowerCase().trim().match(/^(\d+(?:\.\d+)?)\s*(day|days|week|weeks|month|months|wk|wks)?/);
        if (!match) return null;

        let days = parseFloat(match[1]);
        const unit = match[2] || 'day';
        if (unit.startsWith('week') || unit === 'wk' || unit === 'wks') days *= 7;
        if (unit.startsWith('month')) days *= 30;

        return Number.isNaN(days) ? null : Math.round(days);
    }

    function calcPrescriptionQty(row) {
        const drugSelect = row.querySelector('.prescription-drug-select');
        const dosageInput = row.querySelector('[name$="[dosage]"]');
        const frequencySelect = row.querySelector('[name$="[frequency]"]');
        const durationInput = row.querySelector('[name$="[duration]"]');
        const quantityInput = row.querySelector('[name$="[quantity]"]');

        if (!drugSelect || !dosageInput || !frequencySelect || !durationInput || !quantityInput) return;

        const selected = drugSelect.options[drugSelect.selectedIndex];
        const strength = selected ? selected.dataset.strength : null;
        const dosage = dosageInput.value.trim();
        const frequency = frequencySelect.value;
        const days = parseDays(durationInput.value);
        const dosesPerDay = freqMap[frequency] || 1;

        if (!days) return;

        const doseUnits = parseDoseUnits(dosage);
        let unitsPerDose = doseUnits || 1;
        const dosageMg = parseMg(dosage);
        const strengthMg = parseMg(strength);

        if (!doseUnits && dosageMg && strengthMg && strengthMg > 0) {
            unitsPerDose = Math.ceil(dosageMg / strengthMg);
        }

        let total = frequency === 'STAT' ? unitsPerDose : unitsPerDose * dosesPerDay * days;
        total = Math.ceil(total);

        if (total > 0) {
            quantityInput.value = total;
            quantityInput.style.background = '#fffbe6';
            window.setTimeout(function () {
                quantityInput.style.background = '';
            }, 1000);
        }
    }

    function bindPrescriptionRow(row) {
        if (!row || row.dataset.rxModalBound === '1') return;
        row.dataset.rxModalBound = '1';

        ['input', 'change'].forEach(function (eventName) {
            row.querySelector('[name$="[dosage]"]')?.addEventListener(eventName, function () {
                calcPrescriptionQty(row);
            });
            row.querySelector('[name$="[duration]"]')?.addEventListener(eventName, function () {
                calcPrescriptionQty(row);
            });
        });

        row.querySelector('[name$="[frequency]"]')?.addEventListener('change', function () {
            calcPrescriptionQty(row);
        });

        if (window.jQuery) {
            window.jQuery(row).find('.prescription-drug-select')
                .off('.rxModal')
                .on('select2:select.rxModal select2:clear.rxModal change.rxModal', function () {
                    syncDrugNames(row);
                    calcPrescriptionQty(row);
                });
        }
    }

    function renumberRows() {
        wrapper.querySelectorAll('.prescription-modal-item').forEach(function (row, index) {
            row.dataset.index = index;
            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            const remove = row.querySelector('.remove-prescription-modal-item');
            if (remove) remove.classList.toggle('d-none', index === 0);
        });
    }

    addButton.addEventListener('click', function () {
        const source = wrapper.querySelector('.prescription-modal-item');
        const clone = source.cloneNode(true);
        clone.removeAttribute('data-rx-modal-bound');
        clone.querySelectorAll('.select2-container').forEach(function (container) {
            container.remove();
        });
        clone.querySelectorAll('input').forEach(function (input) {
            input.value = input.type === 'number' ? '1' : '';
        });
        clone.querySelectorAll('select').forEach(function (select) {
            select.selectedIndex = 0;
            if (select.classList.contains('prescription-drug-select')) {
                select.classList.remove('select2-hidden-accessible');
                select.removeAttribute('data-select2-id');
                select.removeAttribute('aria-hidden');
                select.removeAttribute('tabindex');
            }
        });
        clone.querySelectorAll('option[data-select2-id]').forEach(function (option) {
            option.removeAttribute('data-select2-id');
        });
        wrapper.appendChild(clone);
        renumberRows();
        initDrugSelect(clone.querySelector('.prescription-drug-select'));
        bindPrescriptionRow(clone);
    });

    wrapper.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-prescription-modal-item');
        if (!button) return;
        button.closest('.prescription-modal-item').remove();
        renumberRows();
    });

    wrapper.addEventListener('change', function (event) {
        if (event.target.classList.contains('prescription-drug-select')) {
            syncDrugNames(event.target.closest('.prescription-modal-item'));
            calcPrescriptionQty(event.target.closest('.prescription-modal-item'));
        }
    });

    form.addEventListener('submit', function () {
        syncDrugNames(form);
    });

    wrapper.querySelectorAll('.prescription-modal-item').forEach(function (row) {
        initDrugSelect(row.querySelector('.prescription-drug-select'));
        bindPrescriptionRow(row);
    });
}());
</script>
@endpush
@endcan
@endif
@endsection
