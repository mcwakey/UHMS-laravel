<?php

namespace App\Exports;

use App\Models\DispensingRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PharmacySalesSummaryExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = DispensingRecord::join('drug_stock', 'dispensing_records.drug_stock_id', '=', 'drug_stock.id')
            ->join('prescription_items', 'dispensing_records.prescription_item_id', '=', 'prescription_items.id')
            ->select(
                'prescription_items.drug_name',
                DB::raw('SUM(dispensing_records.quantity_dispensed) as total_qty'),
                DB::raw('AVG(drug_stock.selling_price) as avg_price'),
                DB::raw('SUM(dispensing_records.quantity_dispensed * drug_stock.selling_price) as total_revenue')
            );

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('dispensing_records.dispensed_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('dispensing_records.dispensed_at', '<=', $this->filters['date_to']);
        }

        return $query->groupBy('prescription_items.drug_name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [
                $row->drug_name,
                $row->total_qty,
                number_format($row->avg_price, 2),
                number_format($row->total_revenue, 2),
            ]);
    }

    public function headings(): array
    {
        return ['Drug Name', 'Total Qty Dispensed', 'Avg Unit Price (₵)', 'Total Revenue (₵)'];
    }

    public function title(): string
    {
        return 'Pharmacy Sales (Summary)';
    }
}
