<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('payments.receipt') }} {{ $payment->payment_number }}</title>
    @php
        $org = \App\Models\Setting::getGroup('organization');
        $orgName = $org['name'] ?? config('app.name', 'UHMS');
        $orgLogoPath = !empty($org['logo']) && is_file(public_path('storage/'.$org['logo'])) ? public_path('storage/'.$org['logo']) : null;
        $orgAddress = collect([$org['address'] ?? null, $org['city'] ?? null, $org['region'] ?? null])->filter()->implode(', ');
        $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode('  ·  ');
        $accent = $payment->is_reversal ? '#dc2626' : '#15803d';
        $method = $payment->payment_method instanceof \App\Enums\PaymentMethod
            ? $payment->payment_method->translatedLabel()
            : (\App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->translatedLabel() ?? $payment->payment_method);
        $inv = $payment->invoice;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; }
        .muted { color: #64748b; }
        .text-end { text-align: right; }

        .topbar { border-bottom: 2px solid {{ $accent }}; padding-bottom: 10px; margin-bottom: 12px; }
        .topbar td { vertical-align: top; }
        .org-name { font-size: 15px; font-weight: bold; color: #0f172a; }
        .org-meta { font-size: 9px; color: #64748b; margin-top: 1px; }
        .doc-title { font-size: 16px; font-weight: bold; color: {{ $accent }}; text-align: right; letter-spacing: .5px; }
        .doc-num { font-size: 11px; font-weight: 600; text-align: right; margin-top: 2px; }
        .stamp { display:inline-block; border: 2px solid {{ $accent }}; color: {{ $accent }}; padding: 2px 8px; font-size: 9px; font-weight: bold; letter-spacing: .5px; }

        .info-table { width: 100%; margin-bottom: 12px; }
        .info-table td { width: 50%; vertical-align: top; padding-right: 12px; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: bold; margin-bottom: 3px; }
        .info-name { font-weight: bold; font-size: 11.5px; color: #0f172a; }
        .info-line { font-size: 10.5px; margin-top: 1px; }
        .info-line .k { color: #94a3b8; }

        .banner { background: {{ $payment->is_reversal ? '#fee2e2' : '#dcfce7' }}; padding: 11px 14px; border-radius: 5px; margin: 12px 0; }
        .banner table { width: 100%; }
        .banner .lbl { font-size: 11px; font-weight: bold; color: {{ $payment->is_reversal ? '#991b1b' : '#166534' }}; }
        .banner .amt { font-size: 22px; font-weight: bold; text-align: right; color: {{ $payment->is_reversal ? '#991b1b' : '#166534' }}; }

        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: {{ $accent }}; margin-bottom: 5px; }
        table.inv { width: 100%; border-collapse: collapse; }
        table.inv th { background: #f1f5f9; color: #475569; font-weight: bold; font-size: 9px; text-transform: uppercase; padding: 6px 8px; text-align: left; border-bottom: 1.5px solid #cbd5e1; }
        table.inv td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; font-size: 10.5px; }

        .reason { font-size: 10px; color: #991b1b; margin-bottom: 10px; }
        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px dashed #cbd5e1; text-align: center; color: #94a3b8; font-size: 9px; line-height: 1.5; }
        .thanks { font-size: 11px; color: {{ $accent }}; font-weight: bold; margin-bottom: 2px; }
    </style>
</head>
<body>
    <table class="topbar">
        <tr>
            <td>
                @if($orgLogoPath)
                    <img src="{{ $orgLogoPath }}" alt="{{ $orgName }}" style="max-height:40px; margin-bottom:3px;">
                    <div class="org-meta">{{ $orgAddress }}</div>
                @else
                    <div class="org-name">{{ $orgName }}</div>
                    <div class="org-meta">{{ $orgAddress ?: __('common.app_tagline') }}</div>
                @endif
                @if($orgContact)<div class="org-meta">{{ $orgContact }}</div>@endif
            </td>
            <td>
                <div class="doc-title">{{ __('payments.payment_receipt_label') }}</div>
                <div class="doc-num">{{ $payment->payment_number }}</div>
                @if($payment->is_reversal)
                    <div style="text-align:right; margin-top:4px;"><span class="stamp">{{ __('payments.reversal_stamp') }}</span></div>
                @elseif($inv && $inv->balance <= 0)
                    <div style="text-align:right; margin-top:4px;"><span class="stamp">{{ __('payments.paid_in_full') }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <div class="info-label">{{ __('payments.received_from') }}</div>
                @if($payment->patient)
                    <div class="info-name">{{ $payment->patient->full_name }}</div>
                    <div class="info-line">{{ $payment->patient->patient_number }}</div>
                    @if($payment->patient->phone)<div class="info-line">{{ $payment->patient->phone }}</div>@endif
                @else
                    <div class="info-name">{{ $payment->invoice?->external_party_name ?? __('invoices.external_recipient') }}</div>
                    <div class="info-line muted">{{ __('payments.external_referral') }}</div>
                @endif
            </td>
            <td>
                <div class="info-label">{{ __('payments.payment_details') }}</div>
                <div class="info-line"><span class="k">{{ __('payments.date_label') }}:</span> {{ $payment->paid_at?->format('d M Y, h:i A') }}</div>
                <div class="info-line"><span class="k">{{ __('payments.method_label') }}:</span> {{ $method }}</div>
                @if($payment->reference_number)<div class="info-line"><span class="k">{{ __('payments.reference_label') }}:</span> {{ $payment->reference_number }}</div>@endif
                <div class="info-line"><span class="k">{{ __('payments.cashier_label') }}:</span> {{ $payment->receivedBy->name ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <div class="banner">
        <table>
            <tr>
                <td class="lbl">{{ $payment->is_reversal ? __('payments.amount_reversed') : __('payments.amount_received') }}</td>
                <td class="amt">&#8373;{{ number_format(abs($payment->amount), 2) }}</td>
            </tr>
        </table>
    </div>

    @if($payment->reversal_reason)
    <p class="reason"><strong>{{ __('common.reason') }}:</strong> {{ $payment->reversal_reason }}</p>
    @endif

    @if($inv)
    <div class="section-title">{{ __('payments.applied_to_invoice') }}</div>
    <table class="inv">
        <thead>
            <tr>
                <th>{{ __('payments.invoice_num_col') }}</th>
                <th>{{ __('payments.date_label') }}</th>
                <th class="text-end">{{ __('payments.total_col_rcpt') }}</th>
                <th class="text-end">{{ __('payments.paid_col_rcpt') }}</th>
                <th class="text-end">{{ __('payments.balance_col_rcpt') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:600;">{{ $inv->invoice_number }}</td>
                <td>{{ $inv->created_at->format('d M Y') }}</td>
                <td class="text-end">&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                <td class="text-end" style="color:#16a34a;">&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                <td class="text-end" style="color:{{ $inv->balance > 0 ? '#dc2626' : '#16a34a' }}; font-weight:bold;">&#8373;{{ number_format($inv->balance, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        <div class="thanks">{{ __('payments.thank_you_short') }}</div>
        {{ $orgName }} · {{ __('payments.generated_footer_pdf', ['date' => now()->format('d M Y H:i')]) }}
    </div>
</body>
</html>
