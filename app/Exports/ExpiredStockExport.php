<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExpiredStockExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = StockMovement::with(['product', 'stockLocation'])
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0);

        $scope = $this->filters['scope'] ?? 'expired';
        if ($scope === 'expiring_soon') {
            $query->where('expiry_date', '>', now())
                  ->where('expiry_date', '<=', now()->addDays(90));
        } else {
            $query->where('expiry_date', '<', now());
        }

        if (! empty($this->filters['location'])) {
            $query->whereHas('stockLocation', fn ($q) => $q->where('name', $this->filters['location']));
        }

        return $query->orderBy('expiry_date');
    }

    public function headings(): array
    {
        return [
            'Product',
            'Batch Number',
            'Location',
            'Quantity',
            'Unit Cost (\u20b5)',
            'Total Value (\u20b5)',
            'Expiry Date',
            'Days Until Expiry',
        ];
    }

    public function map($movement): array
    {
        $daysUntilExpiry = now()->diffInDays($movement->expiry_date, false);

        return [
            $movement->product?->name ?? '\u2014',
            $movement->batch_no ?? '\u2014',
            $movement->stockLocation?->name ?? '\u2014',
            $movement->quantity,
            number_format($movement->unit_cost ?? 0, 2),
            number_format($movement->quantity * ($movement->unit_cost ?? 0), 2),
            $movement->expiry_date?->format('d/m/Y'),
            $daysUntilExpiry <= 0 ? 'EXPIRED' : $daysUntilExpiry . ' days',
        ];
    }

    public function title(): string
    {
        return 'Expired / Expiring Stock';
    }
}
