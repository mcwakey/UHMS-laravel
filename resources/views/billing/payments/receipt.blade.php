<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payments.receipt') }} {{ $payment->payment_number }}</title>
    @php
        $org = \App\Models\Setting::getGroup('organization');
        $orgName = $org['name'] ?? config('app.name', 'UHMS');
        $orgLogo = !empty($org['logo']) ? asset('storage/'.$org['logo']) : null;
        $orgAddress = collect([$org['address'] ?? null, $org['city'] ?? null, $org['region'] ?? null])->filter()->implode(', ');
        $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode('  ·  ');
        $accent = '#15803d';
        $inv = $payment->invoice;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #1e293b; background: #f1f5f9; padding: 24px; line-height: 1.5; }
        .receipt { max-width: 720px; margin: 0 auto; background: #fff; padding: 32px 36px; border-radius: 6px; box-shadow: 0 1px 4px rgba(15,23,42,.08); }
        .muted { color: #64748b; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .topbar { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid {{ $accent }}; padding-bottom: 16px; margin-bottom: 20px; }
        .org-name { font-size: 19px; font-weight: 700; color: #0f172a; }
        .org-meta { font-size: 12px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 20px; font-weight: 700; letter-spacing: 1px; color: {{ $accent }}; }
        .doc-num { font-size: 13px; font-weight: 600; margin-top: 3px; }
        .stamp { display: inline-block; margin-top: 7px; padding: 4px 11px; border: 2px solid {{ $accent }}; color: {{ $accent }}; font-weight: 700; font-size: 11px; letter-spacing: .5px; border-radius: 3px; }

        .info-grid { display: flex; gap: 20px; margin-bottom: 18px; }
        .info-grid .col { flex: 1; }
        .info-label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; font-weight: 700; margin-bottom: 5px; }
        .info-name { font-weight: 700; font-size: 14px; color: #0f172a; }
        .info-line { font-size: 12.5px; margin-top: 2px; }
        .info-line .k { color: #94a3b8; display: inline-block; min-width: 72px; }

        .banner { background: #dcfce7; color: #166534; padding: 16px 20px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin: 18px 0; }
        .banner .lbl { font-size: 13px; font-weight: 700; }
        .banner .amt { font-size: 26px; font-weight: 700; }

        .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: {{ $accent }}; margin: 18px 0 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 9px 11px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        table.items td { padding: 8px 11px; border-bottom: 1px solid #eef2f7; font-size: 12.5px; }
        table.items .dept-row td { background: #f8fafc; font-weight: 700; font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: {{ $accent }}; padding: 5px 11px; border-bottom: 1px solid #e2e8f0; }

        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 10px; }
        .totals { width: 320px; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .totals .row .k { color: #64748b; }
        .totals .sep { border-top: 1px solid #e2e8f0; margin-top: 4px; padding-top: 8px; }
        .totals .highlight { color: {{ $accent }}; font-weight: 700; }
        .totals .due { border-top: 2px solid #334155; margin-top: 6px; padding-top: 9px; font-weight: 700; font-size: 16px; }

        .notes { margin-top: 16px; padding: 11px 13px; background: #f8fafc; border-left: 3px solid {{ $accent }}; border-radius: 4px; font-size: 12.5px; }
        .footer { margin-top: 28px; padding-top: 14px; border-top: 1px dashed #cbd5e1; text-align: center; color: #94a3b8; font-size: 11.5px; line-height: 1.6; }
        .footer .thanks { font-size: 13px; color: {{ $accent }}; font-weight: 700; margin-bottom: 3px; }

        .toolbar { max-width: 720px; margin: 0 auto 16px; text-align: center; }
        .toolbar button, .toolbar a { padding: 9px 24px; font-size: 14px; cursor: pointer; border: none; border-radius: 5px; margin: 0 4px; font-weight: 600; text-decoration: none; display: inline-block; }
        .toolbar .print { background: {{ $accent }}; color: #fff; }
        .toolbar .thermal { background: #fff; color: {{ $accent }}; border: 1px solid {{ $accent }}; }
        .toolbar .close { background: #64748b; color: #fff; }

        @media print {
            body { padding: 0; background: #fff; }
            .receipt { box-shadow: none; border-radius: 0; padding: 14px; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print toolbar">
        <button class="print" onclick="window.print()">{{ __('payments.print_receipt') }}</button>
        <a class="thermal" href="{{ route('admin.billing.payments.receipt-thermal', $payment) }}">{{ __('payments.print_receipt_80mm') }}</a>
        <button class="close" onclick="window.close()">{{ __('common.close') }}</button>
    </div>

    <div class="receipt">
        <div class="topbar">
            <div>
                @if($orgLogo)
                    <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height:50px; margin-bottom:3px;">
                    <div class="org-meta">{{ $orgAddress }}</div>
                @else
                    <div class="org-name">{{ $orgName }}</div>
                    <div class="org-meta">{{ $orgAddress ?: __('common.app_tagline') }}</div>
                @endif
                @if($orgContact)<div class="org-meta">{{ $orgContact }}</div>@endif
            </div>
            <div class="text-end">
                <div class="doc-title">{{ __('payments.payment_receipt_label') }}</div>
                <div class="doc-num">{{ $payment->payment_number }}</div>
                @if($inv && $inv->balance <= 0)
                    <div class="stamp">{{ __('payments.paid_in_full') }}</div>
                @else
                    <div class="stamp" style="border-color:#ea580c; color:#ea580c;">{{ __('payments.part_payment_label') }}</div>
                @endif
            </div>
        </div>

        <div class="info-grid">
            <div class="col">
                <div class="info-label">{{ __('payments.received_from') }}</div>
                @if($payment->patient)
                    <div class="info-name">{{ $payment->patient->full_name }}</div>
                    <div class="info-line">{{ $payment->patient->patient_number }}</div>
                    @if($payment->patient->phone)<div class="info-line">{{ $payment->patient->phone }}</div>@endif
                @else
                    <div class="info-name">{{ $payment->invoice?->external_party_name ?? __('invoices.external_recipient') }}</div>
                    <div class="info-line muted">{{ __('payments.external_referral') }}</div>
                @endif
            </div>
            <div class="col">
                <div class="info-label">{{ __('payments.payment_details') }}</div>
                <div class="info-line"><span class="k">{{ __('payments.date_label') }}:</span> {{ $payment->paid_at->format('d M Y, h:i A') }}</div>
                <div class="info-line"><span class="k">{{ __('payments.method_label') }}:</span> {{ $payment->payment_method instanceof \App\Enums\PaymentMethod ? $payment->payment_method->translatedLabel() : (\App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->translatedLabel() ?? $payment->payment_method) }}</div>
                @if($payment->reference_number)
                <div class="info-line"><span class="k">{{ __('payments.reference_label') }}:</span> {{ $payment->reference_number }}</div>
                @endif
                <div class="info-line"><span class="k">{{ __('payments.cashier_label') }}:</span> {{ $payment->receivedBy->name ?? '—' }}</div>
            </div>
        </div>

        <div class="banner">
            <div class="lbl">{{ __('payments.amount_received') }}</div>
            <div class="amt">&#8373;{{ number_format($payment->amount, 2) }}</div>
        </div>

        @if($inv)
        @if($inv->items->isNotEmpty())
        <div class="section-title">{{ __('payments.items_section') }} · {{ $inv->invoice_number }}</div>
        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('payments.description_col') }}</th>
                    <th class="text-center" style="width:10%;">{{ __('payments.qty_col') }}</th>
                    <th class="text-end" style="width:18%;">{{ __('payments.unit_col') }}</th>
                    <th class="text-end" style="width:18%;">{{ __('payments.insurance_col') }}</th>
                    <th class="text-end" style="width:18%;">{{ __('payments.total_col_items') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $deptGroups = $inv->items
                        ->sortBy(fn ($i) => $i->department?->name ?? 'zzz')
                        ->groupBy(fn ($i) => $i->department?->name ?? __('payments.unassigned_department'));
                @endphp
                @foreach($deptGroups as $deptName => $deptItems)
                @php
                    $deptTotal = $deptItems->sum(fn ($i) => round(($i->selected_price !== null ? (float) $i->selected_price : (float) ($i->unit_price ?? 0)) * (int) $i->quantity, 2));
                @endphp
                <tr class="dept-row"><td colspan="4">{{ $deptName }}</td><td class="text-end">&#8373;{{ number_format($deptTotal, 2) }}</td></tr>
                @foreach($deptItems as $item)
                @php
                    $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                    $lineTotal     = round($selectedPrice * (int) $item->quantity, 2);
                @endphp
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">&#8373;{{ number_format($selectedPrice, 2) }}</td>
                    <td class="text-end">
                        @if((float) $item->insurance_covered > 0)&#8373;{{ number_format($item->insurance_covered, 2) }}@else—@endif
                    </td>
                    <td class="text-end">&#8373;{{ number_format($lineTotal, 2) }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="totals-wrap">
            <div class="totals">
                <div class="row"><span class="k">{{ __('payments.invoice_subtotal') }}</span><span>&#8373;{{ number_format($inv->subtotal, 2) }}</span></div>
                @if($inv->discount_amount > 0)
                <div class="row"><span class="k">{{ __('payments.discount_col') }}</span><span style="color:#dc2626;">-&#8373;{{ number_format($inv->discount_amount, 2) }}</span></div>
                @endif
                @if($inv->nhis_amount > 0)
                <div class="row"><span class="k">{{ __('payments.insurance_covered_col') }}</span><span style="color:#1d4ed8;">&#8373;{{ number_format($inv->nhis_amount, 2) }}</span></div>
                @endif
                <div class="row sep" style="font-weight:600;"><span>{{ __('payments.invoice_total') }}</span><span>&#8373;{{ number_format($inv->total_amount, 2) }}</span></div>
                <div class="row highlight"><span>{{ __('payments.this_receipt') }}</span><span>&#8373;{{ number_format($payment->amount, 2) }}</span></div>
                <div class="row"><span class="k">{{ __('payments.total_paid_to_date') }}</span><span>&#8373;{{ number_format($inv->amount_paid, 2) }}</span></div>
                <div class="row due"><span>{{ __('payments.outstanding_balance_col') }}</span><span style="color:{{ $inv->balance > 0 ? '#dc2626' : $accent }};">&#8373;{{ number_format($inv->balance, 2) }}</span></div>
            </div>
        </div>
        @endif

        @if($payment->notes)
        <div class="notes"><strong>{{ __('payments.notes') }}:</strong> {{ $payment->notes }}</div>
        @endif

        <div class="footer">
            <div class="thanks">{{ __('payments.thank_you_payment') }}</div>
            <div>{{ __('payments.computer_generated_receipt') }}</div>
            <div>{{ $orgName }} · {{ __('payments.issued_footer', ['date' => now()->format('d M Y, h:i A')]) }}</div>
        </div>
    </div>
</body>
</html>
