<?php

namespace App\Exports;

use App\Models\Claim;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClaimsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = Claim::with(['insuranceProvider', 'patient', 'assignedDoctor'])
            ->latest('claim_date');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('claim_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('claim_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['status'])) {
            $query->byStatus($this->filters['status']);
        }
        if (!empty($this->filters['provider_id'])) {
            $query->byProvider($this->filters['provider_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Claim #',
            'Provider',
            'Patient',
            'Claim Date',
            'Period From',
            'Period To',
            'Total Amount (₵)',
            'Approved Amount (₵)',
            'Status',
            'Doctor',
            'Submitted At',
        ];
    }

    public function map($claim): array
    {
        return [
            $claim->claim_number,
            $claim->insuranceProvider?->name ?? '—',
            $claim->patient?->full_name ?? '—',
            $claim->claim_date?->format('d/m/Y'),
            $claim->period_from?->format('d/m/Y'),
            $claim->period_to?->format('d/m/Y'),
            number_format($claim->total_amount, 2),
            $claim->approved_amount ? number_format($claim->approved_amount, 2) : '—',
            ucfirst(str_replace('_', ' ', $claim->status->value ?? $claim->status)),
            $claim->assignedDoctor?->full_name ?? '—',
            $claim->submitted_at?->format('d/m/Y') ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Claims Report';
    }
}
