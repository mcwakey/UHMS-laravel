<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeaveExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = LeaveRequest::with(['employee', 'approvedByUser'])
            ->latest('start_date');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('start_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('start_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['status'])) {
            $query->byStatus($this->filters['status']);
        }
        if (!empty($this->filters['leave_type'])) {
            $query->where('leave_type', $this->filters['leave_type']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Department',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days',
            'Status',
            'Approved By',
            'Reason',
        ];
    }

    public function map($leave): array
    {
        return [
            $leave->employee?->first_name . ' ' . $leave->employee?->last_name,
            $leave->employee?->department?->name ?? '—',
            ucfirst(str_replace('_', ' ', $leave->leave_type->value ?? $leave->leave_type)),
            $leave->start_date?->format('d/m/Y'),
            $leave->end_date?->format('d/m/Y'),
            $leave->days,
            ucfirst($leave->status->value ?? $leave->status),
            $leave->approvedByUser?->full_name ?? '—',
            $leave->reason ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Leave Report';
    }
}
