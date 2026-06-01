<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AR Aging Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: top; }
        .logo { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .logo small { display: block; font-size: 9px; color: #666; font-weight: normal; }
        .title { font-size: 16px; font-weight: bold; text-align: right; }
        .sub { font-size: 10px; color: #666; text-align: right; }
        .buckets { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .buckets td { width: 25%; padding: 8px; border: 1px solid #dee2e6; text-align: center; }
        .buckets .bk-label { font-size: 9px; color: #888; text-transform: uppercase; }
        .buckets .bk-total { font-size: 14px; font-weight: bold; margin-top: 3px; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows th, table.rows td { padding: 5px 6px; border: 1px solid #dee2e6; text-align: left; font-size: 9px; }
        table.rows th { background: #f8f9fa; font-weight: 600; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .grand { font-weight: bold; background: #f1f5ff; }
        .footer { margin-top: 18px; text-align: center; color: #888; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><div class="logo">UHMS<small>Ultimate Hospital Management System</small></div></td>
            <td>
                <div class="title">ACCOUNTS RECEIVABLE AGING</div>
                <div class="sub">Generated {{ $generatedAt->format('d M Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="buckets">
        <tr>
            @foreach($aging['buckets'] as $bucket)
            <td>
                <div class="bk-label">{{ $bucket['label'] }} ({{ $bucket['count'] }})</div>
                <div class="bk-total">&#8373;{{ number_format($bucket['total'], 2) }}</div>
            </td>
            @endforeach
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Patient</th>
                <th>Sponsor</th>
                <th>Billing Type</th>
                <th>Due Date</th>
                <th class="text-center">Days Overdue</th>
                <th>Bucket</th>
                <th class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aging['rows'] as $row)
            <tr>
                <td>{{ $row['invoice_number'] }}</td>
                <td>{{ $row['patient_name'] }}<br><span style="color:#888;">{{ $row['patient_number'] }}</span></td>
                <td>{{ $row['sponsor_name'] ?? '—' }}</td>
                <td>{{ $row['billing_type'] ?? '—' }}</td>
                <td>{{ $row['due_date'] ?? '—' }}</td>
                <td class="text-center">{{ $row['days_overdue'] }}</td>
                <td>{{ $aging['buckets'][$row['bucket']]['label'] ?? $row['bucket'] }}</td>
                <td class="text-end">&#8373;{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center" style="padding:14px;">No outstanding invoices.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="grand">
                <td colspan="7" class="text-end">Grand Total ({{ $aging['grand_count'] }} invoices)</td>
                <td class="text-end">&#8373;{{ number_format($aging['grand_total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">UHMS · Computer-generated report.</div>
</body>
</html>
