<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement — {{ $patient->patient_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: top; }
        .logo { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .logo small { display: block; font-size: 9px; color: #666; font-weight: normal; }
        .title { font-size: 16px; font-weight: bold; text-align: right; }
        .sub { font-size: 10px; color: #666; text-align: right; }
        .info-table { width: 100%; margin-bottom: 12px; }
        .info-table td { width: 50%; vertical-align: top; }
        .info-table h5 { font-size: 10px; color: #0d6efd; text-transform: uppercase; margin-bottom: 4px; }
        .info-table p { margin-bottom: 2px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { width: 25%; padding: 8px; border: 1px solid #dee2e6; text-align: center; }
        .summary .s-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .summary .s-val { font-size: 13px; font-weight: bold; margin-top: 3px; }
        table.ledger { width: 100%; border-collapse: collapse; }
        table.ledger th, table.ledger td { padding: 5px 7px; border: 1px solid #dee2e6; text-align: left; font-size: 10px; }
        table.ledger th { background: #f8f9fa; font-weight: 600; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; background: #f1f5ff; }
        .footer { margin-top: 18px; text-align: center; color: #888; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><div class="logo">UHMS<small>Ultimate Hospital Management System</small></div></td>
            <td>
                <div class="title">PATIENT STATEMENT</div>
                <div class="sub">Generated {{ $generatedAt->format('d M Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <h5>Patient</h5>
                <p style="font-weight:bold;">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</p>
                <p>{{ $patient->patient_number }}</p>
                <p>{{ $patient->phone }}</p>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="s-label">Total Charges</div><div class="s-val">&#8373;{{ number_format($summary['total_charges'], 2) }}</div></td>
            <td><div class="s-label">Total Payments</div><div class="s-val" style="color:#198754;">&#8373;{{ number_format($summary['total_payments'], 2) }}</div></td>
            <td><div class="s-label">Balance Due</div><div class="s-val" style="color:{{ $summary['balance_due'] > 0 ? '#dc3545' : '#198754' }};">&#8373;{{ number_format($summary['balance_due'], 2) }}</div></td>
            <td><div class="s-label">Invoices / Payments</div><div class="s-val">{{ $summary['invoice_count'] }} / {{ $summary['payment_count'] }}</div></td>
        </tr>
    </table>

    <table class="ledger">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-end">Charges</th>
                <th class="text-end">Payments</th>
                <th class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $entry)
            <tr>
                <td>{{ optional($entry['date'])->format('d M Y') }}</td>
                <td>{{ ucfirst($entry['type']) }}</td>
                <td>{{ $entry['reference'] }}</td>
                <td>{{ $entry['description'] }}</td>
                <td class="text-end">@if($entry['charges'] > 0)&#8373;{{ number_format($entry['charges'], 2) }}@else—@endif</td>
                <td class="text-end" style="color:#198754;">@if($entry['payments'] > 0)&#8373;{{ number_format($entry['payments'], 2) }}@else—@endif</td>
                <td class="text-end" style="font-weight:600;">&#8373;{{ number_format($entry['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-end" style="text-align:center; padding:14px;">No transactions.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-end">Totals</td>
                <td class="text-end">&#8373;{{ number_format($summary['total_charges'], 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($summary['total_payments'], 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($summary['balance_due'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">UHMS · Computer-generated statement.</div>
</body>
</html>
