<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('invoices.invoice') }} {{ $invoice->invoice_number }}</title>
    @php
        $org = \App\Models\Setting::getGroup('organization');
        $orgName = $org['name'] ?? config('app.name', 'UHMS');
        $orgLogo = !empty($org['logo']) ? asset('storage/'.$org['logo']) : null;
        $orgAddress = collect([$org['address'] ?? null, $org['city'] ?? null, $org['region'] ?? null])->filter()->implode(', ');
        $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode('  ·  ');
        $accent = '#1d4ed8';
        $statusColors = [
            'paid' => ['#dcfce7', '#166534'],
            'partially_paid' => ['#dbeafe', '#1e40af'],
            'pending' => ['#fef9c3', '#854d0e'],
            'cancelled' => ['#fee2e2', '#991b1b'],
            'refunded' => ['#fee2e2', '#991b1b'],
        ];
        $sc = $statusColors[$invoice->status->value] ?? ['#f1f5f9', '#334155'];
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #1e293b; background: #f1f5f9; padding: 24px; line-height: 1.5; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 36px 40px; border-radius: 6px; box-shadow: 0 1px 4px rgba(15,23,42,.08); }
        .muted { color: #64748b; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .topbar { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid {{ $accent }}; padding-bottom: 18px; margin-bottom: 22px; }
        .org-name { font-size: 20px; font-weight: 700; color: #0f172a; }
        .org-meta { font-size: 12px; color: #64748b; margin-top: 3px; }
        .doc-title { font-size: 26px; font-weight: 700; color: {{ $accent }}; letter-spacing: 1px; }
        .doc-num { font-size: 14px; font-weight: 600; margin-top: 2px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: 6px; }

        .meta-grid { display: flex; gap: 16px; margin-bottom: 24px; }
        .meta-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; }
        .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
        .meta-name { font-weight: 700; font-size: 14px; color: #0f172a; }
        .meta-line { font-size: 12.5px; margin-top: 2px; }
        .meta-line .k { color: #94a3b8; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items th { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; padding: 10px 12px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        table.items td { padding: 9px 12px; border-bottom: 1px solid #eef2f7; font-size: 12.5px; }
        table.items tbody tr:nth-child(even) { background: #fafbfc; }
        table.items .dept-row td { background: #eef2f7 !important; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: {{ $accent }}; padding: 6px 12px; }
        .item-code { font-size: 11px; color: #94a3b8; }

        .totals-wrap { display: flex; justify-content: flex-end; }
        .totals { width: 320px; }
        .totals .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .totals .row .k { color: #64748b; }
        .totals .grand { border-top: 2px solid #334155; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16px; color: #0f172a; }
        .totals .due { font-weight: 700; font-size: 16px; }

        .section-title { font-size: 13px; font-weight: 700; color: {{ $accent }}; text-transform: uppercase; letter-spacing: .5px; margin: 26px 0 10px; }
        .notes { margin-top: 18px; padding: 12px 14px; background: #f8fafc; border-left: 3px solid {{ $accent }}; border-radius: 4px; font-size: 12.5px; }
        .footer { margin-top: 34px; padding-top: 14px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 11.5px; line-height: 1.6; }

        .toolbar { max-width: 820px; margin: 0 auto 16px; text-align: center; }
        .toolbar button { padding: 9px 26px; font-size: 14px; cursor: pointer; border: none; border-radius: 5px; margin: 0 4px; font-weight: 600; }
        .toolbar .print { background: {{ $accent }}; color: #fff; }
        .toolbar .close { background: #64748b; color: #fff; }

        @media print {
            body { padding: 0; background: #fff; }
            .sheet { box-shadow: none; border-radius: 0; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print toolbar">
        <button class="print" onclick="window.print()"><span>&#128424;</span> {{ __('invoices.print_invoice_btn') }}</button>
        <button class="close" onclick="window.close()">{{ __('common.close') }}</button>
    </div>

    <div class="sheet">
        <div class="topbar">
            <div>
                @if($orgLogo)
                    <img src="{{ $orgLogo }}" alt="{{ $orgName }}" style="max-height:54px; margin-bottom:4px;">
                    <div class="org-meta">{{ $orgAddress }}</div>
                @else
                    <div class="org-name">{{ $orgName }}</div>
                    <div class="org-meta">{{ $orgAddress ?: __('common.app_tagline') }}</div>
                @endif
                @if($orgContact)<div class="org-meta">{{ $orgContact }}</div>@endif
            </div>
            <div class="text-end">
                <div class="doc-title">{{ __('invoices.invoice_label') }}</div>
                <div class="doc-num">{{ $invoice->invoice_number }}</div>
                <span class="badge" style="background: {{ $sc[0] }}; color: {{ $sc[1] }};">{{ $invoice->status->translatedLabel() }}</span>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ $invoice->patient ? __('invoices.patient_label') : __('invoices.recipient') }}</div>
                @if($invoice->patient)
                    <div class="meta-name">{{ $invoice->patient->full_name }}</div>
                    <div class="meta-line">{{ $invoice->patient->patient_number }}</div>
                    @if($invoice->patient->phone)<div class="meta-line"><x-patient-protected-field field="phone" :value="$invoice->patient->phone" mode="export" /></div>@endif
                @else
                    <div class="meta-name">{{ $invoice->external_party_name ?? __('invoices.external_recipient') }}</div>
                    <div class="meta-line muted">{{ __('invoices.external_referral') }}</div>
                    @if($invoice->bloodRequest)<div class="meta-line">{{ $invoice->bloodRequest->request_number }}</div>@endif
                @endif
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('invoices.invoice_details') }}</div>
                <div class="meta-line"><span class="k">{{ __('invoices.date_label') }}:</span> {{ $invoice->created_at->format('d M Y') }}</div>
                <div class="meta-line"><span class="k">{{ __('invoices.due_date_col') }}:</span> {{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
                <div class="meta-line"><span class="k">{{ __('invoices.billing_type_col') }}:</span> {{ $invoice->billing_type->translatedLabel() }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('invoices.visit') }}</div>
                @if($invoice->visit)
                    <div class="meta-line"><span class="k">{{ __('common.visit_no') }}:</span> {{ $invoice->visit->visit_number }}</div>
                    <div class="meta-line">{{ $invoice->visit->visit_date->format('d M Y') }}</div>
                @else
                    <div class="meta-line muted">—</div>
                @endif
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th>{{ __('invoices.description') }}</th>
                    <th class="text-center" style="width:10%;">{{ __('invoices.quantity') }}</th>
                    <th class="text-end" style="width:18%;">{{ __('invoices.price') }}</th>
                    <th class="text-end" style="width:20%;">{{ __('invoices.patient_payable_col') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $rowNum = 0;
                    $deptGroups = $invoice->items
                        ->sortBy(fn ($i) => $i->department?->name ?? 'zzz')
                        ->groupBy(fn ($i) => $i->department?->name ?? __('invoices.unassigned_department'));
                @endphp
                @foreach($deptGroups as $deptName => $deptItems)
                @php $deptTotal = $deptItems->sum(fn ($i) => (float) $i->patient_payable); @endphp
                <tr class="dept-row"><td colspan="4">{{ $deptName }}</td><td class="text-end">&#8373;{{ number_format($deptTotal, 2) }}</td></tr>
                @foreach($deptItems as $item)
                @php
                    $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                    $rowNum++;
                @endphp
                <tr>
                    <td>{{ $rowNum }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->serviceCatalog)<div class="item-code">{{ $item->serviceCatalog->code }}</div>@endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">&#8373;{{ number_format($selectedPrice, 2) }}</td>
                    <td class="text-end">&#8373;{{ number_format($item->patient_payable, 2) }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="totals-wrap">
            <div class="totals">
                <div class="row"><span class="k">{{ __('invoices.subtotal') }}</span><span>&#8373;{{ number_format($invoice->subtotal, 2) }}</span></div>
                @if($invoice->tax_amount > 0)
                <div class="row"><span class="k">{{ __('invoices.tax') }}</span><span>&#8373;{{ number_format($invoice->tax_amount, 2) }}</span></div>
                @endif
                @if($invoice->discount_amount > 0)
                <div class="row"><span class="k">{{ __('invoices.discount') }}</span><span style="color:#dc2626;">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</span></div>
                @endif
                @if($invoice->nhis_amount > 0)
                <div class="row"><span class="k">{{ __('invoices.insurance_covered') }}</span><span style="color:{{ $accent }};">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</span></div>
                @endif
                <div class="row grand"><span>{{ __('invoices.total_ghs') }}</span><span>&#8373;{{ number_format($invoice->total_amount, 2) }}</span></div>
                <div class="row"><span class="k">{{ __('invoices.amount_paid_col') }}</span><span style="color:#16a34a;">&#8373;{{ number_format($invoice->amount_paid, 2) }}</span></div>
                <div class="row due"><span>{{ __('invoices.balance_due') }}</span><span style="color:{{ $invoice->balance > 0 ? '#dc2626' : '#16a34a' }};">&#8373;{{ number_format($invoice->balance, 2) }}</span></div>
            </div>
        </div>

        @if($invoice->notes)
        <div class="notes"><strong>{{ __('invoices.notes') }}:</strong> {{ $invoice->notes }}</div>
        @endif

        @if($invoice->payments->count() > 0)
        <div class="section-title">{{ __('invoices.payment_history_print') }}</div>
        <table class="items">
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
                    <td>{{ $payment->payment_method->translatedLabel() }}</td>
                    <td>{{ $payment->reference_number ?? '—' }}</td>
                    <td class="text-end" style="color:#16a34a; font-weight:600;">&#8373;{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="footer">
            <div>{{ __('invoices.thank_you_hospital') }}</div>
            <div>{{ $orgName }} · {{ __('invoices.generated_footer', ['date' => now()->format('d M Y H:i')]) }}</div>
        </div>
    </div>
</body>
</html>
