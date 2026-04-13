<?php

namespace App\Exports;

use App\Models\DispensingRecord;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PharmacySalesExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = DispensingRecord::with(['prescriptionItem.drug', 'drugStock', 'patient', 'dispensedBy'])
            ->latest('dispensed_at');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('dispensed_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('dispensed_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Patient',
            'Drug',
            'Batch #',
            'Qty Dispensed',
            'Unit Price',
            'Total',
            'Dispensed By',
        ];
    }

    public function map($record): array
    {
        $unitPrice = $record->drugStock->selling_price ?? 0;
        return [
            $record->dispensed_at?->format('d/m/Y H:i'),
            $record->patient?->full_name ?? '—',
            $record->prescriptionItem?->drug_name ?? '—',
            $record->drugStock?->batch_number ?? '—',
            $record->quantity_dispensed,
            number_format($unitPrice, 2),
            number_format($record->quantity_dispensed * $unitPrice, 2),
            $record->dispensedBy?->full_name ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Pharmacy Sales (Detailed)';
    }
}
