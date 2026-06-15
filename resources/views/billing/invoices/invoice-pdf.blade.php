<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('invoices.invoice') }} {{ $invoice->invoice_number }}</title>
    @php
        $org = \App\Models\Setting::getGroup('organization');
        $orgName = $org['name'] ?? config('app.name', 'UHMS');
        $orgLogoPath = !empty($org['logo']) && is_file(public_path('storage/'.$org['logo'])) ? public_path('storage/'.$org['logo']) : null;
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
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; }
        .accent { color: {{ $accent }}; }
        .muted { color: #64748b; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }

        .topbar { border-bottom: 2px solid {{ $accent }}; padding-bottom: 12px; margin-bottom: 14px; }
        .topbar td { vertical-align: top; }
        .org-name { font-size: 17px; font-weight: bold; color: #0f172a; }
        .org-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
        .doc-title { font-size: 22px; font-weight: bold; color: {{ $accent }}; letter-spacing: 1px; text-align: right; }
        .doc-num { font-size: 12px; font-weight: bold; text-align: right; margin-top: 2px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }

        .meta-table { width: 100%; margin-bottom: 16px; }
        .meta-table td { width: 33.33%; vertical-align: top; padding-right: 14px; }
        .meta-card { border: 1px solid #e2e8f0; border-radius: 5px; padding: 9px 11px; }
        .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 4px; font-weight: bold; }
        .meta-name { font-weight: bold; font-size: 12px; color: #0f172a; }
        .meta-line { font-size: 10.5px; margin-top: 1px; }
        .meta-line .k { color: #94a3b8; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th { background: #f1f5f9; color: #475569; font-weight: bold; font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; padding: 7px 9px; text-align: left; border-bottom: 1.5px solid #cbd5e1; }
        table.items td { padding: 7px 9px; border-bottom: 1px solid #eef2f7; font-size: 10.5px; }
        table.items tbody tr:nth-child(even) { background: #fafbfc; }
        table.items tr.dept-row td { background: #eef2f7; font-weight: bold; font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; color: {{ $accent }}; padding: 6px 9px; }
        .item-code { font-size: 9px; color: #94a3b8; }

        .totals { width: 270px; float: right; }
        .totals td { padding: 4px 2px; font-size: 11px; }
        .totals .k { color: #64748b; }
        .totals .grand td { border-top: 1.5px solid #334155; padding-top: 7px; font-weight: bold; font-size: 13px; color: #0f172a; }
        .totals .due td { font-weight: bold; font-size: 13px; }

        .section-title { font-size: 11px; font-weight: bold; color: {{ $accent }}; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .notes { clear: both; margin-top: 16px; padding: 9px 11px; background: #f8fafc; border-left: 3px solid {{ $accent }}; border-radius: 3px; font-size: 10.5px; }
        .footer { clear: both; margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 9.5px; }
    </style>
</head>
<body>
    <table class="topbar">
        <tr>
            <td>
                @if($orgLogoPath)
                    <img src="{{ $orgLogoPath }}" alt="{{ $orgName }}" style="max-height:46px; margin-bottom:4px;">
                    <div class="org-meta">{{ $orgAddress }}</div>
                @else
                    <div class="org-name">{{ $orgName }}</div>
                    <div class="org-meta">{{ $orgAddress ?: __('common.app_tagline') }}</div>
                @endif
                @if($orgContact)<div class="org-meta">{{ $orgContact }}</div>@endif
            </td>
            <td>
                <div class="doc-title">{{ __('invoices.invoice_label') }}</div>
                <div class="doc-num">{{ $invoice->invoice_number }}</div>
                <div style="text-align:right; margin-top:5px;">
                    <span class="badge" style="background: {{ $sc[0] }}; color: {{ $sc[1] }};">{{ $invoice->status->translatedLabel() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-card">
                    <div class="meta-label">{{ $invoice->patient ? __('invoices.patient_label') : __('invoices.recipient') }}</div>
                    @if($invoice->patient)
                        <div class="meta-name">{{ $invoice->patient->full_name }}</div>
                        <div class="meta-line">{{ $invoice->patient->patient_number }}</div>
                        @if($invoice->patient->phone)<div class="meta-line">{{ $invoice->patient->phone }}</div>@endif
                    @else
                        <div class="meta-name">{{ $invoice->external_party_name ?? __('invoices.external_recipient') }}</div>
                        <div class="meta-line muted">{{ __('invoices.external_referral') }}</div>
                        @if($invoice->bloodRequest)<div class="meta-line">{{ $invoice->bloodRequest->request_number }}</div>@endif
                    @endif
                </div>
            </td>
            <td>
                <div class="meta-card">
                    <div class="meta-label">{{ __('invoices.invoice_details') }}</div>
                    <div class="meta-line"><span class="k">{{ __('invoices.date_label') }}:</span> {{ $invoice->created_at->format('d M Y') }}</div>
                    <div class="meta-line"><span class="k">{{ __('invoices.due_date_col') }}:</span> {{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
                    <div class="meta-line"><span class="k">{{ __('invoices.billing_type_col') }}:</span> {{ $invoice->billing_type?->translatedLabel() }}</div>
                    @if($invoice->sponsor)<div class="meta-line"><span class="k">{{ __('invoices.sponsor_label') }}:</span> {{ $invoice->sponsor->name }}</div>@endif
                </div>
            </td>
            <td>
                <div class="meta-card">
                    <div class="meta-label">{{ __('invoices.visit') }}</div>
                    @if($invoice->visit)
                        <div class="meta-line"><span class="k">{{ __('common.visit_no') }}:</span> {{ $invoice->visit->visit_number }}</div>
                        @if($invoice->visit->department)<div class="meta-line">{{ $invoice->visit->department->name }}</div>@endif
                        <div class="meta-line">{{ $invoice->visit->visit_date?->format('d M Y') }}</div>
                    @else
                        <div class="meta-line muted">—</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
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

    <table class="totals">
        <tr>
            <td class="k">{{ __('invoices.subtotal') }}</td>
            <td class="text-end">&#8373;{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_amount > 0)
        <tr><td class="k">{{ __('invoices.tax') }}</td><td class="text-end">&#8373;{{ number_format($invoice->tax_amount, 2) }}</td></tr>
        @endif
        @if($invoice->discount_amount > 0)
        <tr><td class="k">{{ __('invoices.discount') }}</td><td class="text-end" style="color:#dc2626;">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</td></tr>
        @endif
        @if(($invoice->adjustment_amount ?? 0) > 0)
        <tr><td class="k">{{ __('invoices.credit_write_off') }}</td><td class="text-end" style="color:{{ $accent }};">-&#8373;{{ number_format($invoice->adjustment_amount, 2) }}</td></tr>
        @endif
        @if($invoice->nhis_amount > 0)
        <tr><td class="k">{{ __('invoices.insurance_covered') }}</td><td class="text-end" style="color:{{ $accent }};">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>{{ __('invoices.total_ghs') }}</td><td class="text-end">&#8373;{{ number_format($invoice->total_amount, 2) }}</td></tr>
        <tr><td class="k">{{ __('invoices.amount_paid_col') }}</td><td class="text-end" style="color:#16a34a;">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td></tr>
        <tr class="due"><td>{{ __('invoices.balance_due') }}</td><td class="text-end" style="color:{{ $invoice->balance > 0 ? '#dc2626' : '#16a34a' }};">&#8373;{{ number_format($invoice->balance, 2) }}</td></tr>
    </table>

    @if($invoice->notes)
    <div class="notes"><strong>{{ __('invoices.notes') }}:</strong> {{ $invoice->notes }}</div>
    @endif

    @if($invoice->creditNotes->where('status', 'issued')->count() > 0)
    <div style="clear:both; margin-top:18px;">
        <div class="section-title">{{ __('invoices.credit_notes_section') }}</div>
        <table class="items">
            <thead><tr><th>{{ __('invoices.credit_note_num') }}</th><th>{{ __('common.type') }}</th><th>{{ __('common.reason') }}</th><th class="text-end">{{ __('invoices.amount_paid') }}</th></tr></thead>
            <tbody>
                @foreach($invoice->creditNotes->where('status', 'issued') as $cn)
                <tr>
                    <td>{{ $cn->credit_note_number }}</td>
                    <td>{{ $cn->type?->translatedLabel() }}</td>
                    <td>{{ $cn->reason }}</td>
                    <td class="text-end">&#8373;{{ number_format($cn->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        {{ $orgName }} · {{ __('invoices.generated_footer', ['date' => now()->format('d M Y H:i')]) }} · {{ __('invoices.computer_generated') }}
    </div>
</body>
</html>
