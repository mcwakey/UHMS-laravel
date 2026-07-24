@extends('layouts.app')
@section('title', __('prescriptions.prescriptions'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('prescriptions.prescriptions') }}</h4>
        <small class="text-muted">{{ __('prescriptions.manage_subtitle') }}</small>
    </div>
    @can('prescriptions.create')
    <button type="button" class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#newRxModal">
        <i class="ti ti-plus me-1"></i>New Rx
    </button>
    @endcan
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

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">{{ __('common.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('prescriptions.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('common.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach(\App\Enums\PrescriptionStatus::cases() as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search me-1"></i>{{ __('common.filter') }}</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.prescriptions.index') }}" class="btn btn-outline-secondary w-100">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Prescriptions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('pharmacy.rx_number_short') }}</th>
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('common.doctor') }}</th>
                        <th>{{ __('pharmacy.items') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $prescription)
                    <tr>
                        <td><span class="fw-medium">{{ $prescription->prescription_number }}</span></td>
                        <td>
                            <div class="fw-medium">{{ $prescription->patient->full_name }}</div>
                            <small class="text-muted">{{ $prescription->patient->patient_number }}</small>
                        </td>
                        <td>Dr. {{ $prescription->doctor->full_name }}</td>
                        <td><span class="badge bg-secondary">{{ __('prescriptions.items_count', ['count' => $prescription->items->count()]) }}</span></td>
                        <td><x-status-badge :status="$prescription->status" /></td>
                        <td><small>{{ $prescription->created_at->format('d M Y, h:i A') }}</small></td>
                        <td>
                            <a aria-label="{{ __('common.view') }}" title="{{ __('common.view') }}" href="{{ route('admin.prescriptions.show', $prescription) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="ti ti-prescription fs-1 d-block mb-2"></i>
                            {{ __('prescriptions.no_prescriptions_found') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-3">
    {{ $prescriptions->withQueryString()->links() }}
</div>

@can('prescriptions.create')
<div class="modal fade" id="newRxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.prescriptions.store') }}" id="newRxForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold mb-0"><i class="ti ti-prescription me-1"></i>New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Visit <span class="text-danger">*</span></label>
                        <select name="visit_id" id="newRxVisit" class="form-select" required style="width:100%">
                            <option value="">-- Search visit number or patient name/number --</option>
                        </select>
                        <div class="form-text">Start typing a visit number, patient name, or patient number.</div>
                    </div>

                    <div id="newRxItems">
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
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addRxModalItem">
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
    const wrapper = document.getElementById('newRxItems');
    const addButton = document.getElementById('addRxModalItem');
    const form = document.getElementById('newRxForm');
    const modalEl = document.getElementById('newRxModal');
    if (!wrapper || !addButton || !form || !modalEl) return;

    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#newRxVisit').select2({
            dropdownParent: jQuery(modalEl),
            width: '100%',
            placeholder: '-- Search visit number or patient name/number --',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route('admin.prescriptions.visit-search') }}',
                dataType: 'json',
                delay: 300,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) {
                    return { results: (data || []).map(function (v) {
                        v.text = v.visit_number + ' — ' + (v.patient_name || 'Patient') + ' (' + (v.patient_number || '') + ')';
                        v.id = String(v.id);
                        return v;
                    }) };
                },
                cache: true
            }
        });
    }

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
            dropdownParent: window.jQuery('#newRxModal'),
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
@endsection
