<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StockValuationExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = DB::table('stock_balances as sb')
            ->join('products as p', 'p.id', '=', 'sb.product_id')
            ->join('stock_locations as sl', 'sl.id', '=', 'sb.stock_location_id')
            ->leftJoin('drugs as d', 'd.product_id', '=', 'p.id')
            ->where('sb.quantity_on_hand', '>', 0)
            ->whereNull('p.deleted_at')
            ->select(
                'p.name as product_name',
                DB::raw('COALESCE(d.name, p.name) as drug_name'),
                'sl.name as location',
                DB::raw('SUM(sb.quantity_on_hand) as total_qty'),
                DB::raw('SUM(sb.quantity_on_hand * COALESCE(p.default_cost, 0)) as cost_value'),
                DB::raw('SUM(sb.quantity_on_hand * COALESCE(p.base_price, 0)) as retail_value')
            )
            ->groupBy('p.id', 'p.name', 'd.name', 'sl.id', 'sl.name');

        if (! empty($this->filters['location'])) {
            $query->where('sl.id', $this->filters['location']);
        }

        if (! empty($this->filters['search'])) {
            $query->where(function ($query) {
                $query->where('p.name', 'like', '%'.$this->filters['search'].'%')
                    ->orWhere('p.code', 'like', '%'.$this->filters['search'].'%');
            });
        }

        return $query->orderBy('p.name')->get()->map(function ($row) {
            $cost = (float) $row->cost_value;
            $retail = (float) $row->retail_value;

            return [
                'Drug/Product' => $row->drug_name,
                'Location' => $row->location,
                'Total Qty' => $row->total_qty,
                'Cost Value (₵)' => number_format($cost, 2),
                'Retail Value (₵)' => number_format($retail, 2),
                'Margin (₵)' => number_format($retail - $cost, 2),
            ];
        });
    }

    public function headings(): array
    {
        return ['Drug/Product', 'Location', 'Total Qty', 'Cost Value (₵)', 'Retail Value (₵)', 'Margin (₵)'];
    }

    public function title(): string
    {
        return 'Stock Valuation';
    }
}
