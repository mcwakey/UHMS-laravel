<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->payment_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; padding: 20px; background: #f4f4f4; }
        .receipt { max-width: 720px; margin: 0 auto; background: #fff; padding: 28px 32px; border: 1px solid #e0e0e0; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #198754; padding-bottom: 14px; margin-bottom: 20px; }
        .header .logo { font-size: 22px; font-weight: bold; color: #198754; }
        .header .logo small { display: block; font-size: 11px; color: #666; font-weight: normal; }
        .header .meta { text-align: right; }
        .header .meta .title { font-size: 18px; font-weight: bold; letter-spacing: 1px; color: #198754; }
        .header .meta .num { font-size: 14px; color: #333; margin-top: 4px; font-weight: 600; }
        .header .meta .stamp { display: inline-block; margin-top: 6px; padding: 4px 10px; border: 2px solid #198754; color: #198754; font-weight: bold; font-size: 11px; letter-spacing: 1px; transform: rotate(-3deg); }
        .info-grid { display: flex; gap: 24px; margin-bottom: 18px; }
        .info-grid .col { flex: 1; }
        .info-grid h5 { font-size: 12px; color: #198754; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .info-grid p { margin-bottom: 3px; font-size: 13px; }
        .info-grid p .label { color: #888; display: inline-block; min-width: 80px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { padding: 7px 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
        th { background: #f8f9fa; color: #555; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .amount-banner { background: #d1e7dd; color: #0f5132; padding: 14px 18px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin: 16px 0; }
        .amount-banner .lbl { font-size: 13px; font-weight: 600; }
        .amount-banner .amt { font-size: 24px; font-weight: 700; }
        .summary { display: flex; justify-content: flex-end; margin-top: 8px; }
        .summary table { width: 320px; }
        .summary td { border: none; padding: 4px 8px; }
        .summary .total td { border-top: 1px solid #333; font-weight: bold; }
        .footer { margin-top: 26px; padding-top: 14px; border-top: 1px dashed #ccc; text-align: center; color: #888; font-size: 11px; line-height: 1.6; }
        .footer .thanks { font-size: 13px; color: #198754; font-weight: 600; margin-bottom: 4px; }
        .actions { text-align: center; margin-bottom: 16px; }
        .actions button { padding: 8px 22px; font-size: 14px; cursor: pointer; border: none; border-radius: 4px; margin: 0 4px; }
        .actions .print { background: #198754; color: #fff; }
        .actions .close { background: #6c757d; color: #fff; }
        @media print {
            body { padding: 0; background: #fff; }
            .receipt { box-shadow: none; border: none; padding: 14px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print actions">
        <button class="print" onclick="window.print()">Print Receipt</button>
        <button class="close" onclick="window.close()">Close</button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                UHMS
                <small>Ultimate Hospital Management System</small>
            </div>
            <div class="meta">
                <div class="title">PAYMENT RECEIPT</div>
                <div class="num">{{ $payment->payment_number }}</div>
                @if($payment->invoice && $payment->invoice->balance <= 0)
                    <div class="stamp">PAID IN FULL</div>
                @else
                    <div class="stamp" style="border-color:#fd7e14; color:#fd7e14;">PART PAYMENT</div>
                @endif
            </div>
        </div>

        <!-- Info -->
        <div class="info-grid">
            <div class="col">
                <h5>Received From</h5>
                <p style="font-weight:bold;">{{ $payment->patient->full_name }}</p>
                <p>{{ $payment->patient->patient_number }}</p>
                <p>{{ $payment->patient->phone }}</p>
            </div>
            <div class="col">
                <h5>Payment Details</h5>
                <p><span class="label">Date:</span> {{ $payment->paid_at->format('d M Y, h:i A') }}</p>
                <p><span class="label">Method:</span> {{ $payment->payment_method instanceof \App\Enums\PaymentMethod ? $payment->payment_method->label() : (\App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->label() ?? $payment->payment_method) }}</p>
                @if($payment->reference_number)
                <p><span class="label">Reference:</span> {{ $payment->reference_number }}</p>
                @endif
                <p><span class="label">Cashier:</span> {{ $payment->receivedBy->name ?? '—' }}</p>
            </div>
        </div>

        <!-- Amount Banner -->
        <div class="amount-banner">
            <div class="lbl">Amount Received</div>
            <div class="amt">&#8373;{{ number_format($payment->amount, 2) }}</div>
        </div>

        <!-- Invoice Snapshot -->
        @if($payment->invoice)
        @php $inv = $payment->invoice; @endphp
        <h5 style="font-size:12px; text-transform:uppercase; color:#198754; margin-bottom:6px;">Applied To Invoice</h5>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Visit</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight:600;">{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->created_at->format('d M Y') }}</td>
                    <td>{{ $inv->visit?->visit_number ?? '—' }}</td>
                    <td class="text-end">&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                    <td class="text-end" style="color:#198754;">&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                    <td class="text-end" style="color:{{ $inv->balance > 0 ? '#dc3545' : '#198754' }}; font-weight:bold;">&#8373;{{ number_format($inv->balance, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($inv->items->isNotEmpty())
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
        <h5 style="font-size:12px; text-transform:uppercase; color:#198754; margin:14px 0 6px;">Items</h5>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Pricing</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit</th>
                    <th class="text-end">Insurance</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inv->items as $item)
                @php
                    $src           = $item->pricing_source ?? 'cash_and_carry';
                    $label         = $sourceLabels[$src] ?? ucwords(str_replace('_',' ', (string) $src));
                    $payer         = $item->payer_type ?? 'cash';
                    $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                    $lineTotal     = round($selectedPrice * (int) $item->quantity, 2);
                @endphp
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="font-size:11px;">
                        {{ $label }}<br>
                        <span style="color:#666;">{{ ucfirst($payer) }}</span>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">&#8373;{{ number_format($selectedPrice, 2) }}</td>
                    <td class="text-end">
                        @if((float) $item->insurance_covered > 0)
                            &#8373;{{ number_format($item->insurance_covered, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-end">&#8373;{{ number_format($lineTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="summary">
            <table>
                <tr>
                    <td style="color:#888;">Invoice Subtotal</td>
                    <td class="text-end">&#8373;{{ number_format($inv->subtotal, 2) }}</td>
                </tr>
                @if($inv->discount_amount > 0)
                <tr>
                    <td style="color:#888;">Discount</td>
                    <td class="text-end" style="color:#dc3545;">-&#8373;{{ number_format($inv->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($inv->nhis_amount > 0)
                <tr>
                    <td style="color:#888;">Insurance Covered</td>
                    <td class="text-end" style="color:#0d6efd;">&#8373;{{ number_format($inv->nhis_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total">
                    <td>Invoice Total</td>
                    <td class="text-end">&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="color:#198754;">This Receipt</td>
                    <td class="text-end" style="color:#198754; font-weight:bold;">&#8373;{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="color:#888;">Total Paid To Date</td>
                    <td class="text-end">&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                </tr>
                <tr class="total">
                    <td>Outstanding Balance</td>
                    <td class="text-end" style="color:{{ $inv->balance > 0 ? '#dc3545' : '#198754' }};">&#8373;{{ number_format($inv->balance, 2) }}</td>
                </tr>
            </table>
        </div>
        @endif

        @if($payment->notes)
        <div style="margin-top:14px; padding:10px; background:#f8f9fa; border-radius:4px; font-size:12px;">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p class="thanks">Thank you for your payment.</p>
            <p>This is a computer-generated receipt and is valid without a signature.</p>
            <p>Issued on {{ now()->format('d M Y, h:i A') }} | UHMS — Ultimate Hospital Management System</p>
        </div>
    </div>
</body>
</html>
