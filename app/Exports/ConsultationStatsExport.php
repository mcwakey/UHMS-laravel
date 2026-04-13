<?php

namespace App\Exports;

use App\Models\MedicalRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConsultationStatsExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = MedicalRecord::join('visits', 'medical_records.visit_id', '=', 'visits.id')
            ->join('users', 'medical_records.doctor_id', '=', 'users.id')
            ->leftJoin('departments', 'visits.department_id', '=', 'departments.id')
            ->select(
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as doctor_name"),
                'departments.name as department',
                DB::raw('COUNT(medical_records.id) as total_consultations'),
                DB::raw('COUNT(DISTINCT medical_records.patient_id) as unique_patients')
            );

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('visits.visit_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('visits.visit_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['department_id'])) {
            $query->where('visits.department_id', $this->filters['department_id']);
        }

        return $query->groupBy('users.id', 'users.first_name', 'users.last_name', 'departments.name')
            ->orderByDesc('total_consultations')
            ->get()
            ->map(fn ($row) => [
                $row->doctor_name,
                $row->department ?? '—',
                $row->total_consultations,
                $row->unique_patients,
            ]);
    }

    public function headings(): array
    {
        return ['Doctor', 'Department', 'Total Consultations', 'Unique Patients'];
    }

    public function title(): string
    {
        return 'Consultation Statistics';
    }
}
