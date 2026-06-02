<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\OperationalReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalReportController extends Controller
{
    public function __construct(private OperationalReportService $reports) {}

    public function dashboard(Request $request)
    {
        return view('reports.dashboard', $this->reports->dashboard($request->only(['date_from', 'date_to'])));
    }

    public function show(Request $request, string $report)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'department_id', 'user_id', 'patient_id', 'visit_type', 'blood_group']);

        if ($request->query('export') === 'csv') {
            return $this->csv($report, $filters);
        }

        return view('reports.operational', array_merge(
            $this->reports->build($report, $filters),
            [
                'catalogue' => $this->reports->catalogue(),
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'users' => User::orderBy('first_name')->orderBy('last_name')->limit(200)->get(['id', 'first_name', 'last_name']),
            ]
        ));
    }

    protected function csv(string $report, array $filters): StreamedResponse
    {
        $data = $this->reports->build($report, $filters, true);
        $filename = $report.'-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $data['columns']);
            foreach ($data['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
