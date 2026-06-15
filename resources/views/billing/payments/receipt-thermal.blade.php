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
        $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode(' · ');
        $inv = $payment->invoice;
        $method = $payment->payment_method instanceof \App\Enums\PaymentMethod
            ? $payment->payment_method->translatedLabel()
            : (\App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->translatedLabel() ?? $payment->payment_method);
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { background: #e9ecef; }
        body { font-family: 'Segoe UI', 'DejaVu Sans Mono', monospace, sans-serif; color: #000; font-size: 12px; line-height: 1.4; }
        .roll { width: 80mm; margin: 16px auto; background: #fff; padding: 6mm 5mm; box-shadow: 0 1px 6px rgba(0,0,0,.15); }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { color: #444; }
        .sm { font-size: 11px; }
        .lg { font-size: 15px; }
        .org { font-size: 16px; font-weight: 700; letter-spacing: .5px; }
        .logo { max-width: 60mm; max-height: 18mm; margin: 0 auto 3px; display: block; }
        .hr { border: none; border-top: 1px dashed #000; margin: 7px 0; }
        .doc { font-size: 13px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row .k { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        td { font-size: 11px; padding: 2px 0; vertical-align: top; }
        .items td { border-bottom: 1px dotted #bbb; }
        .items td.dept { border-bottom: 1px solid #000; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding-top: 4px; }
        .qty { white-space: nowrap; }
        .total-line { font-size: 14px; font-weight: 700; }
        .banner { border: 1px dashed #000; padding: 6px; margin: 7px 0; text-align: center; }
        .banner .amt { font-size: 18px; font-weight: 700; }
        .stamp { display: inline-block; border: 1.5px solid #000; padding: 2px 8px; font-weight: 700; font-size: 11px; margin-top: 4px; }
        .foot { text-align: center; font-size: 10px; margin-top: 8px; }

        .toolbar { text-align: center; margin: 14px auto; }
        .toolbar button { padding: 8px 18px; font-size: 13px; border: none; border-radius: 4px; margin: 0 3px; cursor: pointer; font-weight: 600; }
        .toolbar .print { background: #15803d; color: #fff; }
        .toolbar .close { background: #64748b; color: #fff; }

        @media print {
            @page { size: 80mm auto; margin: 0; }
            html, body { background: #fff; width: 80mm; }
            .roll { width: 80mm; margin: 0; padding: 2mm 3mm; box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print toolbar">
        <button class="print" onclick="window.print()">{{ __('payments.print_receipt') }}</button>
        <button class="close" onclick="window.close()">{{ __('common.close') }}</button>
    </div>

    <div class="roll">
        <div class="center">
            @if($orgLogo)
                <img src="{{ $orgLogo }}" alt="{{ $orgName }}" class="logo">
            @else
                <div class="org">{{ $orgName }}</div>
            @endif
            @if($orgAddress)<div class="sm muted">{{ $orgAddress }}</div>@endif
            @if($orgContact)<div class="sm muted">{{ $orgContact }}</div>@endif
        </div>

        <hr class="hr">

        <div class="center">
            <div class="doc">{{ __('payments.payment_receipt_label') }}</div>
            <div class="sm bold">{{ $payment->payment_number }}</div>
            @if($inv)
                @if($inv->balance <= 0)
                    <div class="stamp">{{ __('payments.paid_in_full') }}</div>
                @else
                    <div class="stamp">{{ __('payments.part_payment_label') }}</div>
                @endif
            @endif
        </div>

        <hr class="hr">

        <div class="row sm"><span class="k">{{ __('payments.date_label') }}</span><span>{{ $payment->paid_at->format('d/m/y H:i') }}</span></div>
        <div class="row sm">
            <span class="k">{{ __('payments.received_from') }}</span>
            <span class="right">{{ $payment->patient?->full_name ?? $payment->invoice?->external_party_name ?? __('invoices.external_recipient') }}</span>
        </div>
        @if($payment->patient?->patient_number)
        <div class="row sm"><span class="k">{{ __('common.patient_no') }}</span><span>{{ $payment->patient->patient_number }}</span></div>
        @endif
        <div class="row sm"><span class="k">{{ __('payments.method_label') }}</span><span>{{ $method }}</span></div>
        @if($payment->reference_number)
        <div class="row sm"><span class="k">{{ __('payments.reference_label') }}</span><span>{{ $payment->reference_number }}</span></div>
        @endif
        <div class="row sm"><span class="k">{{ __('payments.cashier_label') }}</span><span>{{ $payment->receivedBy->name ?? '—' }}</span></div>

        @if($inv && $inv->items->isNotEmpty())
        <hr class="hr">
        <table class="items">
            <tbody>
                @php $currentDept = null; @endphp
                @foreach($inv->items->sortBy(fn ($i) => $i->department?->name ?? 'zzz') as $item)
                @php
                    $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                    $lineTotal     = round($selectedPrice * (int) $item->quantity, 2);
                    $deptName      = $item->department?->name ?? __('payments.unassigned_department');
                @endphp
                @if($currentDept !== $deptName)
                <tr><td colspan="2" class="dept">{{ $deptName }}</td></tr>
                @php $currentDept = $deptName; @endphp
                @endif
                <tr>
                    <td>{{ $item->description }}<br><span class="muted qty">{{ $item->quantity }} × &#8373;{{ number_format($selectedPrice, 2) }}</span></td>
                    <td class="right">&#8373;{{ number_format($lineTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($inv)
        <hr class="hr">
        <div class="row sm"><span class="k">{{ __('payments.invoice_subtotal') }}</span><span>&#8373;{{ number_format($inv->subtotal, 2) }}</span></div>
        @if($inv->discount_amount > 0)
        <div class="row sm"><span class="k">{{ __('payments.discount_col') }}</span><span>-&#8373;{{ number_format($inv->discount_amount, 2) }}</span></div>
        @endif
        @if($inv->nhis_amount > 0)
        <div class="row sm"><span class="k">{{ __('payments.insurance_covered_col') }}</span><span>&#8373;{{ number_format($inv->nhis_amount, 2) }}</span></div>
        @endif
        <div class="row"><span class="bold">{{ __('payments.invoice_total') }}</span><span class="bold">&#8373;{{ number_format($inv->total_amount, 2) }}</span></div>
        @endif

        <div class="banner">
            <div class="sm">{{ __('payments.amount_received') }}</div>
            <div class="amt">&#8373;{{ number_format($payment->amount, 2) }}</div>
        </div>

        @if($inv)
        <div class="row sm"><span class="k">{{ __('payments.total_paid_to_date') }}</span><span>&#8373;{{ number_format($inv->amount_paid, 2) }}</span></div>
        <div class="row total-line"><span>{{ __('payments.outstanding_balance_col') }}</span><span>&#8373;{{ number_format($inv->balance, 2) }}</span></div>
        @endif

        @if($payment->notes)
        <hr class="hr">
        <div class="sm"><span class="bold">{{ __('payments.notes') }}:</span> {{ $payment->notes }}</div>
        @endif

        <hr class="hr">
        <div class="foot">
            <div class="bold">{{ __('payments.thank_you_payment') }}</div>
            <div>{{ __('payments.computer_generated_receipt') }}</div>
            <div>{{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</body>
</html>
