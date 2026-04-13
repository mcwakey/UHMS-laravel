<?php

namespace App\Exports;

use App\Models\DrugStock;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StockValuationExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = DrugStock::select(
                'drug_stocks.drug_id',
                'drug_stocks.location',
                DB::raw("SUM(drug_stocks.quantity) as total_qty"),
                DB::raw("SUM(drug_stocks.quantity * drug_stocks.unit_cost) as cost_value"),
                DB::raw("SUM(drug_stocks.quantity * drug_stocks.selling_price) as retail_value")
            )
            ->join('drugs', 'drugs.id', '=', 'drug_stocks.drug_id')
            ->where('drug_stocks.quantity', '>', 0)
            ->groupBy('drug_stocks.drug_id', 'drug_stocks.location');

        if (!empty($this->filters['location'])) {
            $query->where('drug_stocks.location', $this->filters['location']);
        }

        $rows = $query->orderBy('drugs.name')->get();

        return $rows->map(function ($row) {
            $drug = $row->drug;
            return [
                'Drug'           => $drug?->name ?? '—',
                'Location'       => ucfirst(str_replace('_', ' ', $row->location)),
                'Total Qty'      => $row->total_qty,
                'Cost Value (₵)' => number_format($row->cost_value, 2),
                'Retail Value (₵)' => number_format($row->retail_value, 2),
                'Margin (₵)'    => number_format($row->retail_value - $row->cost_value, 2),
            ];
        });
    }

    public function headings(): array
    {
        return ['Drug', 'Location', 'Total Qty', 'Cost Value (₵)', 'Retail Value (₵)', 'Margin (₵)'];
    }

    public function title(): string
    {
        return 'Stock Valuation';
    }
}
