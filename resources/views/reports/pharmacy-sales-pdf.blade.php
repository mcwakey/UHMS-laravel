<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pharmacy Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #198754; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #198754; }
        .header p { margin: 5px 0 0; color: #666; }
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; }
        .stat-box .value { font-size: 18px; font-weight: bold; }
        .stat-box .label { font-size: 10px; color: #666; }
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
        <h1>UHMS - Pharmacy Sales Report</h1>
        <p>Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="label">Total Revenue</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_dispensed']) }}</div>
            <div class="label">Total Dispensed</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_items']) }}</div>
            <div class="label">Items</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['unique_patients']) }}</div>
            <div class="label">Patients</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Drug</th>
                <th>Batch</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $rec)
            <tr>
                <td>{{ $rec->created_at->format('d/m/Y') }}</td>
                <td>{{ $rec->prescription?->visit?->patient?->full_name ?? '—' }}</td>
                <td>{{ $rec->drugStock?->drug?->name ?? '—' }}</td>
                <td>{{ $rec->drugStock?->batch_number ?? '—' }}</td>
                <td class="text-right">{{ $rec->quantity_dispensed }}</td>
                <td class="text-right">₵{{ number_format($rec->unit_price ?? 0, 2) }}</td>
                <td class="text-right text-success">₵{{ number_format(($rec->unit_price ?? 0) * $rec->quantity_dispensed, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>University Hospital Management System (UHMS) &bull; Confidential</p>
    </div>
</body>
</html>
