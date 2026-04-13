<?php

namespace App\Exports;

use App\Models\DrugStock;
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
        $query = DrugStock::with('drug')
            ->where('quantity', '>', 0);

        $scope = $this->filters['scope'] ?? 'expired';
        if ($scope === 'expiring_soon') {
            $query->expiringSoon();
        } else {
            $query->expired();
        }

        if (!empty($this->filters['location'])) {
            $query->where('location', $this->filters['location']);
        }

        return $query->orderBy('expiry_date');
    }

    public function headings(): array
    {
        return [
            'Drug',
            'Batch Number',
            'Location',
            'Quantity',
            'Unit Cost (₵)',
            'Total Value (₵)',
            'Expiry Date',
            'Days Until Expiry',
        ];
    }

    public function map($stock): array
    {
        $daysUntilExpiry = now()->diffInDays($stock->expiry_date, false);

        return [
            $stock->drug?->name ?? '—',
            $stock->batch_number,
            ucfirst(str_replace('_', ' ', $stock->location)),
            $stock->quantity,
            number_format($stock->unit_cost, 2),
            number_format($stock->quantity * $stock->unit_cost, 2),
            $stock->expiry_date?->format('d/m/Y'),
            $daysUntilExpiry <= 0 ? 'EXPIRED' : $daysUntilExpiry . ' days',
        ];
    }

    public function title(): string
    {
        return 'Expired / Expiring Stock';
    }
}
