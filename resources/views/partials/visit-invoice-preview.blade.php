@php
    $invoice = $invoice ?? $visit?->latestInvoice ?? null;
    $invoiceItems = $items ?? ($invoice?->items ?? collect());
    $title = $title ?? __('admissions.visit_invoice');
    $emptyText = $emptyText ?? __('admissions.no_invoice_items');
    $openInvoiceUrl = $openInvoiceUrl ?? ($invoice ? route('admin.billing.invoices.show', $invoice) : null);

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
        'emergency_service' => 'emergency',
        'emergency_medication_order' => 'emergency_medication',
        'emergency_consumable' => 'emergency_consumable',
        'emergency_investigation' => 'emergency_investigation',
        'emergency_procedure' => 'emergency_procedure',
    ];

    $sourceTypeLabels = [
        'consultation_visit_services' => __('admissions.consultation_visit_services'),
        'ward_admission' => __('admissions.ward_admission'),
        'emergency' => __('emergency.billing_group_services'),
        'emergency_medication' => __('emergency.billing_group_medications'),
        'emergency_consumable' => __('menu.emergency_consumables'),
        'emergency_investigation' => __('emergency.billing_group_investigations'),
        'emergency_procedure' => __('emergency.billing_group_procedures'),
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
    ];

    $sourceKeyFor = fn ($item) => $sourceTypeGroups[
        $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other')
    ] ?? ($item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other'));

    $departmentFor = fn ($item) => $item->department ?? $item->serviceCatalog?->department;
    $groupedItems = $invoiceItems->sortBy(fn ($item) => implode('|', [
        $sourceKeyFor($item),
        $departmentFor($item)?->name ?? 'zz_unassigned',
        str_pad((string) $item->id, 10, '0', STR_PAD_LEFT),
    ]));

    $coveredTotal = 0.0;
    $patientTotal = 0.0;
    $balanceTotal = 0.0;
    $currentGroup = null;
@endphp

@once
@push('styles')
<style>
    .visit-invoice-table th,
    .visit-invoice-table td { padding:.85rem 1rem; }
    .visit-invoice-table thead th { font-size:.72rem; text-transform:uppercase; color:var(--bs-heading-color); }
    .visit-invoice-group-row th { background:#10c7b7 !important; color:#fff; border-color:#10c7b7; font-size:.74rem; text-transform:uppercase; letter-spacing:0; }
    .visit-invoice-group-badge { background:#e9ffff; color:#0f172a; font-size:.62rem; }
</style>
@endpush
@endonce

<div class="card visit-invoice-preview">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0">
            <i class="ti ti-receipt me-1"></i>{{ $title }}
            @if($invoice)
                <span class="badge bg-secondary ms-2">{{ $invoice->invoice_number }}</span>
            @endif
        </h5>
        <div class="d-flex flex-wrap gap-2">
            {{ $actions ?? '' }}
            @if($openInvoiceUrl)
                <a href="{{ $openInvoiceUrl }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-external-link me-1"></i>{{ __('admissions.open_invoice') }}
                </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        @if($invoiceItems->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 visit-invoice-table">
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
                                <tr class="visit-invoice-group-row">
                                    <th colspan="6">
                                        <i class="ti ti-folder me-1"></i>{{ $groupLabel }}
                                        <span class="badge visit-invoice-group-badge ms-2">{{ $departmentLabel }}</span>
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
                                </td>
                                <td>
                                    <span class="badge bg-{{ $meta[1] }}">{{ $meta[0] }}</span>
                                    <div class="small text-muted mt-1">
                                        <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ str($payer)->replace('_', ' ')->title() }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    @if((float) ($item->cash_price ?? 0) > $selectedPrice)
                                        <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format((float) $item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    @if($covered > 0)
                                        <span class="text-success">&#8373;{{ number_format($covered, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if((float) ($item->discount_amount ?? 0) > 0)
                                        <div class="small text-danger">-&#8373;{{ number_format((float) $item->discount_amount, 2) }}</div>
                                    @endif
                                    <span class="fw-medium">&#8373;{{ number_format($patientPays, 2) }}</span>
                                </td>
                                <td class="text-end {{ $balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                                    &#8373;{{ number_format($balance, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end text-muted">{{ __('admissions.subtotal_label') }}:</td>
                            <td class="text-end text-success">&#8373;{{ number_format($coveredTotal, 2) }}</td>
                            <td class="text-end text-muted">&#8373;{{ number_format($patientTotal, 2) }}</td>
                            <td class="text-end {{ $balanceTotal > 0 ? 'text-danger' : 'text-success' }}">&#8373;{{ number_format($balanceTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="ti ti-receipt-off fs-1 d-block mb-2"></i>{{ $emptyText }}
            </div>
        @endif
    </div>
</div>
