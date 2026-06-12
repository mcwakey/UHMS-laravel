<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.invoice') }} {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0d6efd; padding-bottom: 15px; margin-bottom: 20px; }
        .header .logo { font-size: 24px; font-weight: bold; color: #0d6efd; }
        .header .logo small { display: block; font-size: 12px; color: #666; font-weight: normal; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #d1e7dd; color: #0f5132; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-partial { background: #cff4fc; color: #055160; }
        .badge-cancelled { background: #f8d7da; color: #842029; }
        .info-row { display: flex; gap: 20px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .info-col { flex: 1; }
        .info-col h5 { font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #0d6efd; }
        .info-col p { margin-bottom: 4px; font-size: 13px; }
        .info-col .label { color: #888; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #dee2e6; text-align: left; }
        th { background: #f8f9fa; font-weight: 600; font-size: 13px; }
        td { font-size: 13px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-table td { border: none; padding: 4px 8px; }
        .totals-table .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 16px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; text-align: center; color: #888; font-size: 12px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom:20px;">
        <button onclick="window.print()" style="padding:10px 30px; font-size:16px; cursor:pointer; background:#0d6efd; color:white; border:none; border-radius:5px;">
            {{ __('invoices.print_invoice_btn') }}
        </button>
        <button onclick="window.close()" style="padding:10px 30px; font-size:16px; cursor:pointer; background:#6c757d; color:white; border:none; border-radius:5px; margin-left:10px;">
            {{ __('common.close') }}
        </button>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                UHMS
                <small>{{ __('common.app_tagline') }}</small>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px; font-weight:bold; margin-bottom:5px;">{{ __('invoices.invoice_label') }}</div>
                <div style="font-size:16px; color:#0d6efd; font-weight:bold;">{{ $invoice->invoice_number }}</div>
                @php
                    $badgeClass = match($invoice->status->value) {
                        'paid' => 'badge-paid',
                        'pending' => 'badge-pending',
                        'partially_paid' => 'badge-partial',
                        'cancelled' => 'badge-cancelled',
                        default => 'badge-pending',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span>
            </div>
        </div>

        <!-- Info Row -->
        <div class="info-row">
            <div class="info-col">
                <h5>{{ __('invoices.invoice_details') }}</h5>
                <p><span class="label">{{ __('invoices.date_label') }}:</span> {{ $invoice->created_at->format('d M Y') }}</p>
                <p><span class="label">{{ __('invoices.due_date_col') }}:</span> {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="label">{{ __('invoices.billing_type_col') }}:</span> {{ $invoice->billing_type->label() }}</p>
            </div>
            <div class="info-col">
                <h5>{{ $invoice->patient ? __('invoices.patient_label') : __('invoices.recipient') }}</h5>
                @if($invoice->patient)
                    <p style="font-weight:bold;">{{ $invoice->patient->full_name }}</p>
                    <p>{{ $invoice->patient->patient_number }}</p>
                    <p>{{ $invoice->patient->phone }}</p>
                @else
                    <p style="font-weight:bold;">{{ $invoice->external_party_name ?? __('invoices.external_recipient') }}</p>
                    <p>{{ __('invoices.external_referral') }}</p>
                    @if($invoice->bloodRequest)<p>{{ $invoice->bloodRequest->request_number }}</p>@endif
                @endif
            </div>
            <div class="info-col" style="text-align:right;">
                <h5>{{ __('invoices.visit') }}</h5>
                @if($invoice->visit)
                    <p>{{ $invoice->visit->visit_number }}</p>
                    <p>{{ $invoice->visit->status->label() }}</p>
                    <p>{{ $invoice->visit->visit_date->format('d M Y') }}</p>
                @else
                    <p>—</p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        @php
            $sourceLabels = [
                'cash_and_carry'         => 'Cash & Carry',
                'cash_price'             => 'Cash & Carry',
                'provider_specific'      => 'Provider Rate',
                'payer_specific_price'   => 'Provider Rate',
                'insurance_type'         => 'Insurance Type',
                'insurance_type_default' => 'Insurance Type',
                'base_price'             => 'Base Price',
            ];
        @endphp
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('invoices.description') }}</th>
                    {{-- <th>{{ __('invoices.pricing') }}</th> --}}
                    {{-- <th class="text-center">{{ __('invoices.quantity') }}</th> --}}
                    <th class="text-end">{{ __('invoices.price') }}</th>
                    {{-- <th class="text-end">{{ __('invoices.total') }}</th> --}}
                    {{-- <th class="text-end">{{ __('invoices.insurance_covered') }}</th> --}}
                    <th class="text-end">{{ __('invoices.patient_payable_col') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                @php
                    $cashPrice     = $item->cash_price !== null ? (float) $item->cash_price : (float) ($item->selected_price ?? $item->unit_price ?? 0);
                    $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                    $lineTotal     = round($selectedPrice * (int) $item->quantity, 2);
                    $src           = $item->pricing_source ?? 'cash_and_carry';
                    $label         = $sourceLabels[$src] ?? ucwords(str_replace('_',' ', (string) $src));
                    $payer         = $item->payer_type ?? 'cash';
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->serviceCatalog)
                        <div style="font-size:11px; color:#666;">{{ $item->serviceCatalog->code }}</div>
                        @endif
                    </td>
                    {{-- <td style="font-size:11px;">
                        {{ $label }}<br>
                        <span style="color:#666;">{{ ucfirst($payer) }}</span>
                    </td> --}}
                    {{-- <td class="text-center">{{ $item->quantity }}</td> --}}
                    <td class="text-end">&#8373;{{ number_format($selectedPrice, 2) }}</td>
                    {{-- <td class="text-end">&#8373;{{ number_format($lineTotal, 2) }}</td> --}}
                    {{-- <td class="text-end">
                        @if((float) $item->insurance_covered > 0)
                        &#8373;{{ number_format($item->insurance_covered, 2) }}
                        @else
                        —
                        @endif
                    </td> --}}
                    <td class="text-end">&#8373;{{ number_format($item->patient_payable, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="label">{{ __('invoices.subtotal') }}</td>
                    <td class="text-end">&#8373;{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td class="label">{{ __('invoices.tax') }}</td>
                    <td class="text-end">&#8373;{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">{{ __('invoices.discount') }}</td>
                    <td class="text-end" style="color:red;">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->nhis_amount > 0)
                <tr>
                    <td class="label">{{ __('invoices.insurance_covered') }}</td>
                    <td class="text-end" style="color:#0d6efd;">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>{{ __('invoices.total_ghs') }}</strong></td>
                    <td class="text-end"><strong>&#8373;{{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td class="label">{{ __('invoices.amount_paid_col') }}</td>
                    <td class="text-end" style="color:green;">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>{{ __('invoices.balance_due') }}</strong></td>
                    <td class="text-end" style="color:red; font-weight:bold; font-size:16px;">&#8373;{{ number_format($invoice->balance, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($invoice->notes)
        <div style="margin-top:15px; padding:10px; background:#f8f9fa; border-radius:4px;">
            <strong>{{ __('invoices.notes') }}:</strong> {{ $invoice->notes }}
        </div>
        @endif

        <!-- Payment History -->
        @if($invoice->payments->count() > 0)
        <div style="margin-top:20px;">
            <h6 style="font-weight:bold; margin-bottom:10px;">{{ __('invoices.payment_history_print') }}</h6>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('invoices.payment_num_col') }}</th>
                        <th>{{ __('invoices.date_label') }}</th>
                        <th>{{ __('invoices.method') }}</th>
                        <th>{{ __('invoices.reference') }}</th>
                        <th class="text-end">{{ __('invoices.amount_paid') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_number }}</td>
                        <td>{{ $payment->paid_at->format('d M Y H:i') }}</td>
                        <td>{{ $payment->payment_method->label() }}</td>
                        <td>{{ $payment->reference_number ?? '—' }}</td>
                        <td class="text-end" style="color:green; font-weight:bold;">&#8373;{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('invoices.thank_you_hospital') }}</p>
            <p>{{ __('invoices.generated_footer', ['date' => now()->format('d M Y H:i')]) }}</p>
        </div>
    </div>
</body>
</html>
