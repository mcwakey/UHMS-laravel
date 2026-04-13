<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
            Print Invoice
        </button>
        <button onclick="window.close()" style="padding:10px 30px; font-size:16px; cursor:pointer; background:#6c757d; color:white; border:none; border-radius:5px; margin-left:10px;">
            Close
        </button>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                UHMS
                <small>Ultimate Hospital Management System</small>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px; font-weight:bold; margin-bottom:5px;">INVOICE</div>
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
                <h5>Invoice Details</h5>
                <p><span class="label">Date:</span> {{ $invoice->created_at->format('d M Y') }}</p>
                <p><span class="label">Due Date:</span> {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="label">Billing Type:</span> {{ $invoice->billing_type->label() }}</p>
            </div>
            <div class="info-col">
                <h5>Patient</h5>
                <p style="font-weight:bold;">{{ $invoice->patient->full_name }}</p>
                <p>{{ $invoice->patient->patient_number }}</p>
                <p>{{ $invoice->patient->phone }}</p>
            </div>
            <div class="info-col" style="text-align:right;">
                <h5>Visit</h5>
                <p>{{ $invoice->visit->visit_number }}</p>
                <p>{{ $invoice->visit->department->name ?? '—' }}</p>
                <p>{{ $invoice->visit->visit_date->format('d M Y') }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">NHIS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">&#8373;{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end">&#8373;{{ number_format($item->total_price, 2) }}</td>
                    <td class="text-center">
                        @if($item->is_nhis_covered)
                        &#8373;{{ number_format($item->nhis_approved_amount, 2) }}
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="text-end">&#8373;{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="text-end">&#8373;{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="text-end" style="color:red;">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->nhis_amount > 0)
                <tr>
                    <td class="label">NHIS Covered</td>
                    <td class="text-end" style="color:#0d6efd;">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>Total (GHS)</strong></td>
                    <td class="text-end"><strong>&#8373;{{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Amount Paid</td>
                    <td class="text-end" style="color:green;">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Balance Due</strong></td>
                    <td class="text-end" style="color:red; font-weight:bold; font-size:16px;">&#8373;{{ number_format($invoice->balance, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($invoice->notes)
        <div style="margin-top:15px; padding:10px; background:#f8f9fa; border-radius:4px;">
            <strong>Notes:</strong> {{ $invoice->notes }}
        </div>
        @endif

        <!-- Payment History -->
        @if($invoice->payments->count() > 0)
        <div style="margin-top:20px;">
            <h6 style="font-weight:bold; margin-bottom:10px;">Payment History</h6>
            <table>
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
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
            <p>Thank you for choosing our hospital. Get well soon!</p>
            <p>Generated on {{ now()->format('d M Y H:i') }} | UHMS — Ultimate Hospital Management System</p>
        </div>
    </div>
</body>
</html>
