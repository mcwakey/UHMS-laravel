<?php

namespace App\Exports;

use App\Models\LabRequest;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvestigationRevenueExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function collection()
    {
        $query = DB::table('lab_request_items')
            ->join('lab_requests', 'lab_request_items.lab_request_id', '=', 'lab_requests.id')
            ->join('lab_tests', 'lab_request_items.lab_test_id', '=', 'lab_tests.id')
            ->leftJoin('lab_test_categories', 'lab_tests.category_id', '=', 'lab_test_categories.id')
            ->select(
                'lab_test_categories.name as category',
                'lab_tests.name as test_name',
                DB::raw('COUNT(lab_request_items.id) as total_requests'),
                DB::raw('SUM(lab_tests.price) as total_revenue')
            );

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('lab_requests.created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('lab_requests.created_at', '<=', $this->filters['date_to']);
        }

        $query->where('lab_request_items.status', 'completed');

        return $query->groupBy('lab_test_categories.name', 'lab_tests.name', 'lab_tests.price')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($row) => [
                $row->category ?? 'Uncategorized',
                $row->test_name,
                $row->total_requests,
                number_format($row->total_revenue, 2),
            ]);
    }

    public function headings(): array
    {
        return ['Category', 'Test Name', 'Total Requests', 'Total Revenue (₵)'];
    }

    public function title(): string
    {
        return 'Investigation Revenue';
    }
}
