<?php

namespace App\Exports;

use App\Models\Admission;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AdmissionsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy'])
            ->latest('admission_date');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('admission_date', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $query->whereDate('admission_date', '<=', $this->filters['date_to']);
        }
        if (! empty($this->filters['status'])) {
            $query->byStatus($this->filters['status']);
        }
        if (! empty($this->filters['ward_id'])) {
            $query->byWard($this->filters['ward_id']);
        }
        if (! empty($this->filters['department_id'])) {
            $query->whereHas('bed.ward', fn ($ward) => $ward->where('department_id', $this->filters['department_id']));
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Admission #',
            'Patient',
            'Ward',
            'Bed',
            'Admitted By',
            'Diagnosis',
            'Admission Date',
            'Discharge Date',
            'Length of Stay',
            'Status',
        ];
    }

    public function map($admission): array
    {
        return [
            $admission->admission_number,
            $admission->patient?->full_name ?? '—',
            $admission->bed?->ward?->name ?? '—',
            $admission->bed?->bed_number ?? '—',
            $admission->admittedBy?->full_name ?? '—',
            $admission->admitting_diagnosis ?? '—',
            $admission->admission_date?->format('d/m/Y H:i'),
            $admission->actual_discharge_date?->format('d/m/Y H:i') ?? '—',
            $admission->length_of_stay ? $admission->length_of_stay.' days' : 'Ongoing',
            ucfirst($admission->status->value ?? $admission->status),
        ];
    }

    public function title(): string
    {
        return 'Admissions Report';
    }
}
