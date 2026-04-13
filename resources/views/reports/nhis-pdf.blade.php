<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>NHIS Claims Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-success { color: #198754; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - NHIS Claims Report</h1>
        <p>Generated: {{ now()->format('d M Y H:i') }} | Total Claims: {{ $stats['total_claims'] ?? 0 }} | Amount: ₵{{ number_format($stats['total_nhis_amount'] ?? 0, 2) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Patient</th>
                <th>Department</th>
                <th class="text-right">Total</th>
                <th class="text-right">NHIS Amount</th>
                <th class="text-right">Patient Pays</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->patient->full_name ?? '—' }}</td>
                <td>{{ $invoice->visit?->department?->name ?? '—' }}</td>
                <td class="text-right">₵{{ number_format($invoice->total_amount, 2) }}</td>
                <td class="text-right text-success">₵{{ number_format($invoice->nhis_amount, 2) }}</td>
                <td class="text-right">₵{{ number_format($invoice->total_amount - $invoice->nhis_amount, 2) }}</td>
                <td>{{ $invoice->status->label() }}</td>
                <td>{{ $invoice->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>University Hospital Management System (UHMS) &bull; Confidential</p>
    </div>
</body>
</html>
