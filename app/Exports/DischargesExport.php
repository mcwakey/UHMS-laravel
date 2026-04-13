<?php

namespace App\Exports;

use App\Models\Admission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DischargesExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = Admission::with(['patient', 'bed.ward', 'dischargedBy'])
            ->whereNotNull('actual_discharge_date')
            ->latest('actual_discharge_date');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('actual_discharge_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('actual_discharge_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['ward_id'])) {
            $query->byWard($this->filters['ward_id']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Admission #',
            'Patient',
            'Ward',
            'Admitted',
            'Discharged',
            'Length of Stay (Days)',
            'Discharged By',
            'Diagnosis',
            'Discharge Summary',
        ];
    }

    public function map($admission): array
    {
        return [
            $admission->admission_number,
            $admission->patient?->full_name ?? '—',
            $admission->bed?->ward?->name ?? '—',
            $admission->admission_date?->format('d/m/Y'),
            $admission->actual_discharge_date?->format('d/m/Y'),
            $admission->length_of_stay ?? '—',
            $admission->dischargedBy?->full_name ?? '—',
            $admission->admitting_diagnosis ?? '—',
            $admission->discharge_summary ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Discharges Report';
    }
}
