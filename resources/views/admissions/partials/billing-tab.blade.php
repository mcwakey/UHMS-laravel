@php
    $invoice = $admission->visit->latestInvoice;
    $invoiceItems = $invoice ? $invoice->items : collect();
    $billingServices = $admissionBillingServices ?? collect();

    $sourceLabels = [
        'cash_and_carry' => ['Cash & Carry', 'secondary'],
        'cash_price' => ['Cash & Carry', 'secondary'],
        'provider_specific' => ['Provider Rate', 'success'],
        'payer_specific_price' => ['Provider Rate', 'success'],
        'insurance_type' => ['Insurance Type', 'info'],
        'insurance_type_default' => ['Insurance Type', 'info'],
        'base_price' => ['Base Price', 'light text-dark'],
        'drug_price' => ['Drug Price', 'light text-dark'],
    ];

    $sourceTypeGroups = [
        'visit_service' => 'consultation_visit_services',
        'service_catalog' => 'consultation_visit_services',
        'consultation_service' => 'consultation_visit_services',
        'visit_consultation_route_service' => 'consultation_visit_services',
        'ward_charge' => 'ward_admission',
        'ward_consumable' => 'ward_admission',
        'admission_fee' => 'ward_admission',
        'admission_bed_charge' => 'ward_admission',
        'admission_daily_consumable_charge' => 'ward_admission',
    ];

    $sourceTypeLabels = [
        'consultation_visit_services' => __('admissions.consultation_visit_services'),
        'ward_admission' => __('admissions.ward_admission'),
        'lab_request_item' => __('admissions.investigations_group'),
        'investigation_service' => __('admissions.investigations_group'),
        'investigation_consumable' => __('admissions.investigation_consumables_group'),
        'prescription_item' => __('admissions.pharmacy_group'),
        'pharmacy_product' => __('admissions.pharmacy_group'),
        'pharmacy_billing_selection' => __('admissions.pharmacy_group'),
        'scan_request_item' => __('admissions.scans_group'),
        'xray_request_item' => __('admissions.xray_group'),
        'procedure' => __('admissions.procedures_group'),
        'procedure_service' => __('admissions.procedures_group'),
        'procedure_consumable' => __('admissions.procedure_consumables_group'),
        'emergency_consumable' => __('admissions.emergency_group'),
        'emergency_bed_charge' => __('admissions.emergency_group'),
        'emergency_daily_consumable_charge' => __('admissions.emergency_group'),
    ];

    $sourceKeyFor = fn ($item) => $sourceTypeGroups[
        $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other')
    ] ?? ($item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other'));

    $departmentFor = function ($item) use ($admission) {
        return $item->department
            ?? $item->serviceCatalog?->department
            ?? $admission->bed?->ward?->department;
    };

    $groupedItems = $invoiceItems->sortBy(fn ($item) => implode('|', [
        $sourceKeyFor($item),
        $departmentFor($item)?->name ?? 'zz_unassigned',
        str_pad((string) $item->id, 10, '0', STR_PAD_LEFT),
    ]));

    $grossCharges = (float) $invoiceItems->sum('total_price');
    $coveredTotal = 0.0;
    $patientTotal = 0.0;
    $balanceTotal = 0.0;
    $currentGroup = null;
@endphp

<div class="card adm-invoice-preview">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0">
            <i class="ti ti-receipt me-1"></i>{{ __('admissions.visit_invoice') }}
            @if($invoice)
                <span class="badge bg-secondary ms-2 adm-num">{{ $invoice->invoice_number }}</span>
            @endif
        </h5>
        <div class="d-flex flex-wrap gap-2">
            @if($admission->status->value === 'admitted')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#billAdmissionServiceModal">
                    <i class="ti ti-plus me-1"></i>{{ __('admissions.bill_admission_service') }}
                </button>
            @endif
            @if($invoice)
                <a href="{{ $workspaceRoutes->route('admin.billing.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-external-link me-1"></i>{{ __('admissions.open_invoice') }}
                </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        @if($invoiceItems->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 adm-invoice-table">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admissions.service_description') }}</th>
                            <th>{{ __('admissions.pricing_col') }}</th>
                            <th class="text-end">{{ __('admissions.price_col') }}</th>
                            <th class="text-end">{{ __('admissions.covered_col') }}</th>
                            <th class="text-end">{{ __('admissions.patient_pays_col') }}</th>
                            <th class="text-end">{{ __('admissions.balance_col') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedItems as $item)
                            @php
                                $src = $item->pricing_source ?: 'cash_and_carry';
                                $meta = $sourceLabels[$src] ?? [str($src)->replace('_', ' ')->title()->toString(), 'light text-dark'];
                                $payer = $item->payer_type ?: 'cash';
                                $rawSourceKey = $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other');
                                $sourceKey = $sourceTypeGroups[$rawSourceKey] ?? $rawSourceKey;
                                $department = $departmentFor($item);
                                $departmentLabel = $department?->name ?? __('admissions.unassigned_department');
                                $groupKey = $sourceKey.'|'.($department?->id ?? 'none');
                                $groupLabel = $sourceTypeLabels[$sourceKey] ?? str($sourceKey)->replace('_', ' ')->title()->toString();
                                $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                                $covered = max((float) ($item->insurance_covered ?? 0), (float) ($item->nhis_approved_amount ?? 0));
                                $patientPays = (float) ($item->patient_payable ?? max(0, (float) $item->total_price - $covered));
                                $balance = (float) ($item->balance ?? $patientPays);
                                $coveredTotal += $covered;
                                $patientTotal += $patientPays;
                                $balanceTotal += $balance;
                            @endphp
                            @if($currentGroup !== $groupKey)
                                <tr class="adm-invoice-group-row">
                                    <th colspan="6">
                                        <i class="ti ti-folder me-1"></i>{{ $groupLabel }}
                                        <span class="badge adm-invoice-group-badge ms-2">{{ $departmentLabel }}</span>
                                    </th>
                                </tr>
                                @php $currentGroup = $groupKey; @endphp
                            @endif
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $item->description }}</div>
                                    @if($department)
                                        <span class="badge bg-light text-dark mt-1">{{ $department->name }}</span>
                                    @endif
                                    @if($item->serviceCatalog?->code)
                                        <!-- <div class="small text-muted adm-num mt-1">{{ $item->serviceCatalog->code }}</div> -->
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $meta[1] }}">{{ $meta[0] }}</span>
                                    <div class="small text-muted mt-1">
                                        <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ str($payer)->replace('_', ' ')->title() }}
                                    </div>
                                </td>
                                <td class="text-end adm-num">
                                    @if((float) ($item->cash_price ?? 0) > $selectedPrice)
                                        <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format((float) $item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                </td>
                                <td class="text-end adm-num">
                                    @if($covered > 0)
                                        <span class="text-success">&#8373;{{ number_format($covered, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end adm-num">
                                    @if((float) ($item->discount_amount ?? 0) > 0)
                                        <div class="small text-danger">-&#8373;{{ number_format((float) $item->discount_amount, 2) }}</div>
                                    @endif
                                    <span class="fw-medium">&#8373;{{ number_format($patientPays, 2) }}</span>
                                </td>
                                <td class="text-end adm-num {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                                    &#8373;{{ number_format($balance, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end text-muted">{{ __('admissions.subtotal_label') }}:</td>
                            <td class="text-end text-success adm-num">&#8373;{{ number_format($coveredTotal, 2) }}</td>
                            <td class="text-end text-muted adm-num">&#8373;{{ number_format($patientTotal, 2) }}</td>
                            <td class="text-end {{ $balanceTotal > 0 ? 'text-danger' : 'text-success' }} adm-num">&#8373;{{ number_format($balanceTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="ti ti-receipt-off fs-1 d-block mb-2"></i>{{ __('admissions.no_invoice_items') }}
            </div>
        @endif
    </div>
</div>

@if($admission->status->value === 'admitted')
<div class="modal fade" id="billAdmissionServiceModal" tabindex="-1" aria-labelledby="billAdmissionServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.services.store', $admission) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="billAdmissionServiceModalLabel"><i class="ti ti-receipt me-1"></i>{{ __('admissions.bill_admission_service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                @if($billingServices->isNotEmpty())
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('admissions.service_field') }} <span class="text-danger">*</span></label>
                            <select name="service_catalog_id" class="form-select select2" required>
                                <option value="">{{ __('admissions.select_service') }}</option>
                                @foreach($billingServices as $svc)
                                    <option value="{{ $svc->id }}" @selected(old('service_catalog_id') == $svc->id)>
                                        {{ $svc->name }}@if($svc->price) - GH&#8373; {{ number_format((float) $svc->price, 2) }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('admissions.qty_field') }}</label>
                            <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1" max="99">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('admissions.notes_optional_field') }}</label>
                            <input type="text" name="notes" class="form-control" maxlength="500" placeholder="{{ __('admissions.optional_ph') }}" value="{{ old('notes') }}">
                        </div>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-receipt-off fs-1 d-block mb-2"></i>{{ __('admissions.no_admission_services_available') }}
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary" @disabled($billingServices->isEmpty())>
                    <i class="ti ti-plus me-1"></i>{{ __('admissions.add_to_invoice') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif
